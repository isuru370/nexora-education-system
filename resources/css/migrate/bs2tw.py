#!/usr/bin/env python3
"""Bootstrap -> Tailwind helper for the page-by-page move (same look).

1) Build the class map from Bootstrap's own CSS (once):
     python3 resources/css/migrate/bs2tw.py build-map path/to/bootstrap-5.3.3.css
   Every Bootstrap utility class (.mb-3, .d-md-flex, .text-muted ...) gets the
   Tailwind classes that set exactly the same CSS. Each one is checked by
   compiling it with Tailwind (twdecls.mjs). Result: bs2tw-map.json

2) Convert views:
     python3 resources/css/migrate/bs2tw.py convert [--nx-scope] FILE...
   - utility classes -> tw: classes (with ! where Bootstrap used !important)
   - components (card, btn, table ...) -> ui-* classes (resources/css/nexora/ui-kit.css,
     made from Bootstrap's CSS by: bs2tw.py build-kit path/to/bootstrap.css)
   - grid (row / col-* / g-*) -> flex + widths on the row and its direct children
   - class names in the page <style> and in JS are renamed too
   Anything it cannot move is listed at the end (fix by hand).

--nx-scope: the page is inside .dashboard-page; the old nx theme changes a few
Bootstrap colours there (legacy.css "BOOTSTRAP OVERRIDES"), keep those colours.

Goes away with Bootstrap at the end of the move.
"""
import json
import os
import re
import subprocess
import sys
from fractions import Fraction

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '../../..'))
MAP_FILE = os.path.join(HERE, 'bs2tw-map.json')

BREAKPOINTS = {'576px': 'sm', '768px': 'md', '992px': 'lg', '1200px': 'xl', '1400px': '2xl'}
BP_ORDER = ['', 'sm', 'md', 'lg', 'xl', '2xl']
BS_BP = {'sm': 'sm', 'md': 'md', 'lg': 'lg', 'xl': 'xl', 'xxl': '2xl'}


# ---------------------------------------------------------------- CSS parsing

def parse_css(css):
    """[(media, selector, [(prop, value, important)])] for flat rules and one level of @media."""
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    css = re.sub(r'@(charset|import)[^;]*;', '', css)
    out = []
    i = 0
    media = ''
    depth_media = None
    depth = 0
    buf = ''
    while i < len(css):
        ch = css[i]
        if ch == '{':
            head = buf.strip()
            buf = ''
            if head.startswith('@media'):
                media = head
                depth_media = depth
                depth += 1
            elif head.startswith('@'):
                # @keyframes / @supports ...: skip the whole block
                level = 1
                i += 1
                while i < len(css) and level:
                    level += {'{': 1, '}': -1}.get(css[i], 0)
                    i += 1
                continue
            else:
                end = css.index('}', i)
                body = css[i + 1:end]
                decls = []
                for d in split_decls(body):
                    if ':' not in d:
                        continue
                    p, v = d.split(':', 1)
                    v = v.strip()
                    imp = v.endswith('!important')
                    v = re.sub(r'\s*!important$', '', v)
                    decls.append((p.strip(), v, imp))
                out.append((media, head, decls))
                i = end + 1
                continue
        elif ch == '}':
            depth -= 1
            if depth_media is not None and depth == depth_media:
                media = ''
                depth_media = None
            buf = ''
        else:
            buf += ch
        i += 1
    return out


def split_decls(body):
    parts, cur, level, quote = [], '', 0, None
    for ch in body:
        if quote:
            cur += ch
            if ch == quote:
                quote = None
            continue
        if ch in '"\'':
            quote = ch
        elif ch == '(':
            level += 1
        elif ch == ')':
            level -= 1
        if ch == ';' and level == 0:
            parts.append(cur.strip())
            cur = ''
        else:
            cur += ch
    if cur.strip():
        parts.append(cur.strip())
    return parts


def resolve(value, local, root):
    """Replace var(--x, fallback) with values (local custom props first)."""
    for _ in range(10):
        m = re.search(r'var\((--[\w-]+)\s*(?:,\s*([^()]*(?:\([^()]*\))?[^()]*))?\)', value)
        if not m:
            break
        name, fallback = m.group(1), m.group(2)
        rep = local.get(name, root.get(name, fallback if fallback is not None else ''))
        value = value[:m.start()] + rep.strip() + value[m.end():]
    return normalize_color(value)


def normalize_color(v):
    def rgba(m):
        nums = [x.strip() for x in m.group(1).split(',')]
        if len(nums) == 4 and float(nums[3]) == 1:
            nums = nums[:3]
        if len(nums) == 3:
            return '#' + ''.join('%02x' % int(float(n)) for n in nums)
        return 'rgba(%s)' % ','.join(nums)
    v = re.sub(r'rgba?\(([^()]*)\)', rgba, v, flags=re.I)
    return re.sub(r'#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3\b', r'#\1\2\3', v)


# ------------------------------------------------------------- map building

def num(v):
    m = re.fullmatch(r'(-?[\d.]+)(rem|px|em|%)?', v)
    return (Fraction(m.group(1)), m.group(2) or '') if m else None


COLOR_PREFIX = {'color': 'text', 'background-color': 'bg', 'border-color': 'border'}

SPACING = {'margin': 'm', 'margin-top': 'mt', 'margin-right': 'mr', 'margin-bottom': 'mb', 'margin-left': 'ml',
           'padding': 'p', 'padding-top': 'pt', 'padding-right': 'pr', 'padding-bottom': 'pb', 'padding-left': 'pl',
           'gap': 'gap', 'row-gap': 'gap-y', 'column-gap': 'gap-x',
           'top': 'top', 'right': 'right', 'bottom': 'bottom', 'left': 'left'}

NICE = {
    ('display', 'block'): 'block', ('display', 'flex'): 'flex', ('display', 'grid'): 'grid', ('display', 'none'): 'hidden',
    ('flex-direction', 'row'): 'flex-row', ('flex-direction', 'column'): 'flex-col',
    ('flex-direction', 'row-reverse'): 'flex-row-reverse', ('flex-direction', 'column-reverse'): 'flex-col-reverse',
    ('flex-wrap', 'wrap'): 'flex-wrap', ('flex-wrap', 'nowrap'): 'flex-nowrap', ('flex-wrap', 'wrap-reverse'): 'flex-wrap-reverse',
    ('flex-grow', '1'): 'grow', ('flex-grow', '0'): 'grow-0', ('flex-shrink', '1'): 'shrink', ('flex-shrink', '0'): 'shrink-0',
    ('flex', '1 1 auto'): 'flex-auto',
    ('justify-content', 'flex-start'): 'justify-start', ('justify-content', 'flex-end'): 'justify-end',
    ('justify-content', 'center'): 'justify-center', ('justify-content', 'space-between'): 'justify-between',
    ('justify-content', 'space-around'): 'justify-around', ('justify-content', 'space-evenly'): 'justify-evenly',
    ('align-items', 'flex-start'): 'items-start', ('align-items', 'flex-end'): 'items-end', ('align-items', 'center'): 'items-center',
    ('align-items', 'baseline'): 'items-baseline', ('align-items', 'stretch'): 'items-stretch',
    ('align-self', 'auto'): 'self-auto', ('align-self', 'flex-start'): 'self-start', ('align-self', 'flex-end'): 'self-end',
    ('align-self', 'center'): 'self-center', ('align-self', 'baseline'): 'self-baseline', ('align-self', 'stretch'): 'self-stretch',
    ('text-align', 'left'): 'text-left', ('text-align', 'right'): 'text-right', ('text-align', 'center'): 'text-center',
    ('font-weight', '700'): 'font-bold', ('font-weight', '600'): 'font-semibold', ('font-weight', '500'): 'font-medium',
    ('font-weight', '400'): 'font-normal', ('font-weight', '300'): 'font-light',
    ('font-style', 'italic'): 'italic', ('font-style', 'normal'): 'not-italic',
    ('text-transform', 'uppercase'): 'uppercase', ('text-transform', 'lowercase'): 'lowercase',
    ('text-transform', 'capitalize'): 'capitalize', ('white-space', 'nowrap'): 'whitespace-nowrap',
    ('white-space', 'normal'): 'whitespace-normal',
    ('position', 'static'): 'static', ('position', 'relative'): 'relative', ('position', 'absolute'): 'absolute',
    ('position', 'fixed'): 'fixed', ('position', 'sticky'): 'sticky',
    ('width', '100%'): 'w-full', ('width', 'auto'): 'w-auto', ('height', '100%'): 'h-full', ('height', 'auto'): 'h-auto',
    ('max-width', '100%'): 'max-w-full', ('max-height', '100%'): 'max-h-full',
    ('overflow', 'hidden'): 'overflow-hidden', ('overflow', 'auto'): 'overflow-auto', ('overflow', 'visible'): 'overflow-visible',
    ('overflow', 'scroll'): 'overflow-scroll', ('overflow-x', 'auto'): 'overflow-x-auto', ('overflow-y', 'auto'): 'overflow-y-auto',
    ('overflow-x', 'hidden'): 'overflow-x-hidden', ('overflow-y', 'hidden'): 'overflow-y-hidden',
    ('vertical-align', 'middle'): 'align-middle', ('vertical-align', 'top'): 'align-top', ('vertical-align', 'bottom'): 'align-bottom',
    ('vertical-align', 'baseline'): 'align-baseline', ('vertical-align', 'text-top'): 'align-text-top',
    ('vertical-align', 'text-bottom'): 'align-text-bottom',
    ('pointer-events', 'none'): 'pointer-events-none', ('pointer-events', 'auto'): 'pointer-events-auto',
    ('user-select', 'none'): 'select-none', ('user-select', 'all'): 'select-all', ('user-select', 'auto'): 'select-auto',
    ('object-fit', 'cover'): 'object-cover', ('object-fit', 'contain'): 'object-contain',
    ('line-height', '1'): 'leading-none',
    ('float', 'left'): 'float-left', ('float', 'right'): 'float-right', ('float', 'none'): 'float-none',
}


# Bootstrap's colours / radii / shadows as Tailwind theme values, so the views
# read tw:text-bs-secondary-color! rather than tw:text-[rgba(33,37,41,0.75)]!
THEME_FILE = os.path.join(ROOT, 'resources/css/bootstrap-theme.css')
THEME_COLORS = {}   # value -> [names]
THEME_RADIUS = {}   # value -> name
THEME_UTIL = {}     # (prop, value) -> utility name


def is_color(v):
    return re.fullmatch(r'#[0-9a-f]{3,8}|rgba\([\d.,]+\)', v) is not None


def write_theme(root):
    colors, radius, shadows = [], [], []
    for name, v in root.items():
        n = name[len('--bs-'):]
        val = normalize_color(resolve(v, {}, root))
        if is_color(val) and not n.endswith('-rgb'):
            colors.append((n, val))
            THEME_COLORS.setdefault(val, []).append(n)
        elif n.startswith('border-radius') and re.fullmatch(r'[\d.]+rem', val):
            key = n[len('border-radius'):].lstrip('-') or 'DEFAULT'
            radius.append((key, val))
            THEME_RADIUS.setdefault(val, 'bs' if key == 'DEFAULT' else 'bs-' + key)
        elif n.startswith('box-shadow'):
            key = n[len('box-shadow'):].lstrip('-')
            util = 'shadow-bs' + ('-' + key if key else '')
            shadows.append((util, 'box-shadow', val))
            THEME_UTIL[('box-shadow', val)] = util
    border = '%s %s %s' % (root['--bs-border-width'], root['--bs-border-style'], normalize_color(root['--bs-border-color']))
    sides = [('bs-border', 'border'), ('bs-border-t', 'border-top'), ('bs-border-r', 'border-right'),
             ('bs-border-b', 'border-bottom'), ('bs-border-l', 'border-left')]
    for util, prop in sides:
        shadows.append((util, prop, border))
        THEME_UTIL[(prop, border)] = util
    out = ['/*', ' * GENERATED by resources/css/migrate/bs2tw.py build-map from Bootstrap 5.3.3 - do not edit.',
           " * Bootstrap's colours, radii, shadows and border for Tailwind classes:",
           ' *   tw:text-bs-primary  tw:bg-bs-primary-bg-subtle  tw:rounded-bs-lg  tw:shadow-bs-sm  tw:bs-border', ' */', '',
           '@theme {']
    out += ['    --color-bs-%s: %s;' % (n, v) for n, v in colors]
    out += ['    --radius-%s: %s;' % ('bs' if k == 'DEFAULT' else 'bs-' + k, v) for k, v in radius]
    out.append('}')
    for util, prop, val in shadows:
        out += ['', '@utility %s {' % util, '    %s: %s;' % (prop, val), '}']
    open(THEME_FILE, 'w').write('\n'.join(out) + '\n')


def theme_color(v, cls):
    names = THEME_COLORS.get(v)
    if not names:
        return None
    words = set(re.split(r'[-_]', cls))
    words |= {'secondary', 'color'} if 'muted' in words else set()
    best = max(names, key=lambda n: (len(words & set(n.split('-'))), -len(n)))
    return 'bs-' + best


def arbitrary(prop, value):
    return '[%s:%s]' % (prop, value.replace(' ', '_'))


def candidates_for(decls, cls=''):
    """Tailwind class names for a Bootstrap declaration list (without prefix / !)."""
    decls = [(p, v) for p, v in decls]
    out = []
    done = set()
    d = dict(decls)
    # spacing pairs: left+right -> x, top+bottom -> y
    for a, b, axis in (('right', 'left', 'x'), ('top', 'bottom', 'y')):
        for base, short in (('margin', 'm'), ('padding', 'p')):
            pa, pb = '%s-%s' % (base, a), '%s-%s' % (base, b)
            if pa in d and pb in d and d[pa] == d[pb]:
                name = spacing_name(short + axis, d[pa])
                if name:
                    out.append(name)
                    done |= {pa, pb}
    for p, v in decls:
        if p in done:
            continue
        name = None
        if p in SPACING:
            name = spacing_name(SPACING[p], v)
        if not name and (p, v) in NICE:
            name = NICE[(p, v)]
        if not name and p in ('width', 'height') and re.fullmatch(r'[\d.]+%', v):
            name = '%s-[%s]' % (p[0], v)
        if not name and p in COLOR_PREFIX:
            named = theme_color(v, cls)
            name = '%s-%s' % (COLOR_PREFIX[p], named) if named else ('%s-[%s]' % (COLOR_PREFIX[p], v) if ' ' not in v else None)
        if not name and p == 'border-radius' and v in THEME_RADIUS:
            name = 'rounded-' + THEME_RADIUS[v]
        if not name and p == 'border-radius' and ' ' not in v:
            name = 'rounded-[%s]' % v
        if not name and (p, v) in THEME_UTIL:
            name = THEME_UTIL[(p, v)]
        if not name and p == 'font-size':
            name = 'text-[length:%s]' % v.replace(' ', '_')
        if not name and p == 'z-index' and re.fullmatch(r'-?\d+', v):
            name = 'z-' + v
        out.append(name or arbitrary(p, v))
    return out


def spacing_name(short, v):
    if v == 'auto':
        return short + '-auto'
    n = num(v)
    if n is None:
        return None
    val, unit = n
    if val == 0:
        return short + '-0'
    if unit == 'rem' and (val / Fraction(1, 4)).denominator == 1:
        k = int(val / Fraction(1, 4))
        return ('-' if k < 0 else '') + '%s-%d' % (short, abs(k))
    return None


def tw_theme():
    """--tw-* theme values, to compare Tailwind output with Bootstrap."""
    vals = {}
    for f in (os.path.join(ROOT, 'node_modules/tailwindcss/theme.css'), os.path.join(ROOT, 'resources/css/app.css'), THEME_FILE):
        for name, v in re.findall(r'--([\w-]+)\s*:\s*([^;]+);', open(f).read()):
            vals['--tw-' + name] = v.strip()
    return vals


def tw_decls(classes):
    p = subprocess.run(['node', os.path.join(HERE, 'twdecls.mjs')], input=json.dumps(classes),
                       capture_output=True, text=True, check=True, cwd=ROOT)
    return json.loads(p.stdout)


def norm_value(v, theme):
    v = resolve(v, {}, theme)
    v = re.sub(r'calc\(\s*([\d.]+)rem\s*\*\s*(-?[\d.]+)\s*\)',
               lambda m: fmt(Fraction(m.group(1)) * Fraction(m.group(2)), 'rem'), v)
    v = re.sub(r'(?<![\w.])0?\.(\d)', r'0.\1', v)
    v = re.sub(r'(?<![\w.])(-?\d+(?:\.\d+)?)(rem|px|em|%)', lambda m: fmt(Fraction(m.group(1)), m.group(2)), v)
    return v.strip()


def fmt(f, unit):
    if f == 0:
        return '0'
    s = ('%f' % float(f)).rstrip('0').rstrip('.')
    return s + unit


LOGICAL = {'margin-inline': ('margin-left', 'margin-right'), 'margin-block': ('margin-top', 'margin-bottom'),
           'padding-inline': ('padding-left', 'padding-right'), 'padding-block': ('padding-top', 'padding-bottom'),
           'inset-inline': ('left', 'right')}


def decl_set(decls, theme):
    s = set()
    for p, v, imp in decls:
        if p.startswith('--'):
            continue
        v = norm_value(v, theme)
        for q in LOGICAL.get(p, (p,)):
            s.add((q, v, bool(imp)))
    return s


def build_map(bootstrap_css):
    rules = parse_css(open(bootstrap_css).read())
    root = {}
    for media, sel, decls in rules:
        if sel.startswith(':root') and not media:
            for p, v, _ in decls:
                if p.startswith('--') and p not in root:
                    root[p] = v
    # class -> {media: decls}
    classes = {}
    for media, sel, decls in rules:
        for part in sel.split(','):
            m = re.fullmatch(r'\.([\w-]+)', part.strip())
            if not m:
                continue
            classes.setdefault(m.group(1), []).append((media, decls))
    write_theme(root)
    theme = tw_theme()
    utilities = {}
    pending = {}
    for cls, groups in classes.items():
        real = [(media, decls) for media, decls in groups if any(not p.startswith('--') for p, _, _ in decls)]
        if not real or not all(imp for _, decls in real for p, _, imp in decls if not p.startswith('--')):
            continue
        variants = []
        ok = True
        for media, decls in real:
            if media == '':
                prefix = ''
            elif media.startswith('@media print'):
                prefix = 'print:'
            else:
                m = re.search(r'min-width:\s*(\d+px)\)\s*$', media)
                if not m or m.group(1) not in BREAKPOINTS:
                    ok = False
                    break
                prefix = BREAKPOINTS[m.group(1)] + ':'
            local = {p: v for p, v, _ in decls if p.startswith('--')}
            resolved = [(p, resolve(v, local, root)) for p, v, _ in decls if not p.startswith('--')]
            variants.append((prefix, resolved))
        if not ok:
            continue
        pending[cls] = variants
    # try the nice names, check with Tailwind, fall back to [prop:value]
    tries = {}
    for cls, variants in pending.items():
        for prefix, resolved in variants:
            for name in candidates_for(resolved, cls):
                tries['tw:%s%s!' % (prefix, name)] = None
            for p, v in resolved:
                tries['tw:%s%s!' % (prefix, arbitrary(p, v))] = None
    got = tw_decls(list(tries))
    for cls, variants in pending.items():
        out = []
        for prefix, resolved in variants:
            want = decl_set([(p, v, True) for p, v in resolved], theme)
            names = ['tw:%s%s!' % (prefix, n) for n in candidates_for(resolved, cls)]
            have = set()
            for n in names:
                for r in got.get(n, []):
                    have |= decl_set(r['decls'], theme)
            if have != want or not names:
                names = ['tw:%s%s!' % (prefix, arbitrary(p, v)) for p, v in resolved]
                have = set()
                for n in names:
                    for r in got.get(n, []):
                        have |= decl_set(r['decls'], theme)
                if have != want:
                    print('NO MATCH', cls, prefix, resolved, file=sys.stderr)
                    names = None
            if names is None:
                out = None
                break
            out += names
        if out:
            utilities[cls] = out
    json.dump({'utilities': utilities}, open(MAP_FILE, 'w'), indent=1, sort_keys=True)
    print('%d utility classes -> %s' % (len(utilities), MAP_FILE))


# ------------------------------------------------------------------ convert

ICON = re.compile(r'^(bi|bi-[\w-]+|fa[srlbd]?|fa-[\w-]+)$')

# nx theme inside .dashboard-page (legacy.css "BOOTSTRAP OVERRIDES (darkened)")
NX_SCOPE = {
    'text-success': ['tw:text-(color:--nx-green)!'], 'text-danger': ['tw:text-(color:--nx-red)!'],
    'text-primary': ['tw:text-(color:--nx-blue)!'], 'text-warning': ['tw:text-(color:--nx-amber)!'],
    'text-muted': ['tw:text-(color:--nx-muted)!'],
}
NX_BADGE = {
    'bg-success': ['tw:[background:var(--nx-green)]!'], 'bg-warning': ['tw:[background:var(--nx-amber)]!', 'tw:text-white!'],
    'bg-danger': ['tw:[background:var(--nx-red)]!'], 'bg-secondary': ['tw:[background:var(--nx-muted)]!'],
    'bg-primary': ['tw:[background:var(--nx-blue)]!'],
}

GRID_COL = re.compile(r'^col(?:-(sm|md|lg|xl|xxl))?(?:-(auto|\d+))?$')
GUTTER = re.compile(r'^g([xy])?(?:-(sm|md|lg|xl|xxl))?-(\d)$')
GUTTERS = {'0': Fraction(0), '1': Fraction(1, 4), '2': Fraction(1, 2), '3': Fraction(1), '4': Fraction(3, 2), '5': Fraction(3)}


class Converter:
    def __init__(self, nx_scope=False):
        data = json.load(open(MAP_FILE))
        self.util = data['utilities']
        self.nx_scope = nx_scope
        self.problems = []

    def warn(self, where, msg):
        self.problems.append('%s: %s' % (where, msg))

    # ---- one class token (no element context)
    def token(self, tok, static=(), where=''):
        if ICON.match(tok) or tok.startswith('tw:') or tok.startswith('ui-'):
            return [tok]
        if self.nx_scope and tok in NX_SCOPE:
            return NX_SCOPE[tok]
        if self.nx_scope and tok in NX_BADGE and 'badge' in static:
            return NX_BADGE[tok]
        if tok in ('small',):
            return ['tw:text-[.875em]']
        if tok == 'visually-hidden':
            return ['tw:sr-only']
        if tok in ('text-truncate',):
            return ['tw:truncate']
        if tok in KIT:
            return ['ui-' + tok]
        if tok in self.util:
            return self.util[tok]
        return [tok]

    # ---- a whole static class list on one element
    def classes(self, toks, where='', row_parent=None):
        """row_parent: gutters of the parent .row ({bp: (gx, gy)}) when this element is a direct child."""
        static = set(toks)
        out = []
        extra = []
        # grid row
        if 'row' in static:
            gut = self.gutters(toks)
            extra += self.row_classes(gut)
        if row_parent is not None:
            extra += self.col_classes(toks, row_parent, where)
        for tok in toks:
            if tok == 'row' or GUTTER.match(tok) or GRID_COL.match(tok) or re.match(r'^offset-', tok):
                if GRID_COL.match(tok) and row_parent is None:
                    self.warn(where, 'col class outside a .row: ' + tok)
                    out.append(tok)
                if re.match(r'^offset-', tok):
                    self.warn(where, 'offset not supported: ' + tok)
                    out.append(tok)
                continue
            if tok in ('bg-opacity-10', 'bg-opacity-25', 'bg-opacity-50', 'bg-opacity-75'):
                continue
            mapped = self.token(tok, static, where)
            # bg-X + bg-opacity-N
            op = [t for t in toks if t.startswith('bg-opacity-')]
            if op and tok.startswith('bg-') and mapped and mapped[0].startswith('tw:bg-['):
                pass
            if op and re.fullmatch(r'bg-(primary|secondary|success|danger|warning|info|light|dark|white|black)', tok):
                color = re.search(r'\[background-color:(#[0-9a-f]+)\]|bg-\[(#[0-9a-f]+)\]', ' '.join(mapped))
                if color:
                    c = color.group(1) or color.group(2)
                    a = int(op[0].split('-')[-1]) / 100
                    rgb = tuple(int(c[i:i + 2], 16) for i in (1, 3, 5)) if len(c) == 7 else None
                    if rgb:
                        mapped = ['tw:[background-color:rgba(%d,%d,%d,%s)]!' % (rgb + (('%g' % a),))]
            out += mapped
        # Bootstrap order for display: d-none wins over d-inline-* (see map)
        return dedupe(extra + out)

    def gutters(self, toks):
        gut = {'': [Fraction(3, 2), Fraction(0)]}
        for tok in toks:
            m = GUTTER.match(tok)
            if not m:
                continue
            axis, bp, n = m.groups()
            bp = BS_BP.get(bp, '') if bp else ''
            cur = gut.setdefault(bp, [None, None])
            if axis in (None, 'x'):
                cur[0] = GUTTERS[n]
            if axis in (None, 'y'):
                cur[1] = GUTTERS[n]
        # fill responsive gaps from smaller breakpoints
        last = gut['']
        full = {}
        for bp in BP_ORDER:
            if bp in gut:
                g = [gut[bp][0] if gut[bp][0] is not None else last[0], gut[bp][1] if gut[bp][1] is not None else last[1]]
                full[bp] = tuple(g)
                last = g
        return full

    def row_classes(self, gut):
        out = ['tw:flex', 'tw:flex-wrap']
        for bp, (gx, gy) in gut.items():
            v = (bp + ':') if bp else ''
            out += ['tw:%s%s' % (v, sp('mt', -gy)), 'tw:%s%s' % (v, sp('mx', -gx / 2))]
        return out

    def col_classes(self, toks, gut, where):
        out = []
        for bp, (gx, gy) in gut.items():
            v = (bp + ':') if bp else ''
            if not bp:
                out += ['tw:shrink-0', 'tw:max-w-full']
            out += ['tw:%s%s' % (v, sp('px', gx / 2)), 'tw:%s%s' % (v, sp('mt', gy))]
        # width / flex per breakpoint (Bootstrap: .row > * width 100%, then .col-*)
        spec = {}
        for tok in toks:
            m = GRID_COL.match(tok)
            if m:
                bp = BS_BP.get(m.group(1), '') if m.group(1) else ''
                spec[bp] = m.group(2) or 'equal'
        if '' not in spec:
            out.append('tw:w-full')
        for bp in BP_ORDER:
            if bp not in spec:
                continue
            v = (bp + ':') if bp else ''
            kind = spec[bp]
            if kind == 'equal':
                out += ['tw:%sflex-[1_0_0%%]' % v]
                if not bp:
                    out.append('tw:w-full')
            elif kind == 'auto':
                out += ['tw:%sflex-[0_0_auto]' % v, 'tw:%sw-auto' % v]
            else:
                n = int(kind)
                w = '100%' if n == 12 else ('%.8f' % (n * 100 / 12)).rstrip('0').rstrip('.') + '%'
                out += ['tw:%sflex-[0_0_auto]' % v, 'tw:%sw-[%s]' % (v, w)]
        return out

    # ---- a class="" value that may hold Blade
    def class_value(self, value, where='', row_parent=None):
        parts = re.split(r'(\{\{.*?\}\}|\{!!.*?!!\}|@\w+(?:\((?:[^()]|\([^()]*\))*\))?)', value, flags=re.S)
        static = [t for i, p in enumerate(parts) if i % 2 == 0 for t in p.split()]
        mapped = self.classes(static, where, row_parent)
        # dynamic parts: map quoted class names inside the expression
        dyn = []
        for i, p in enumerate(parts):
            if i % 2 == 1:
                dyn.append(self.expr(p, where, static))
        if not any(i % 2 == 1 for i in range(len(parts))):
            return ' '.join(mapped)
        # keep the expressions where they were: static classes first, then the expressions
        return ' '.join(mapped + dyn)

    def expr(self, text, where, static=()):
        def rep(m):
            q, body = m.group(1), m.group(2)
            if not body.strip() or not re.fullmatch(r'[\w\s:-]+', body):
                return m.group(0)
            toks = body.split()
            new = []
            for t in toks:
                if GRID_COL.match(t) or t == 'row':
                    self.warn(where, 'grid class in an expression: ' + t)
                    new.append(t)
                else:
                    new += self.token(t, set(static) | set(toks), where)
            return q + ' '.join(dedupe(new)) + q
        return re.sub(r'(["\'])((?:(?!\1).)*)\1', rep, text)


def sp(short, val):
    if val == 0:
        return short + '-0'
    k = val / Fraction(1, 4)
    if k.denominator == 1:
        return ('-' if k < 0 else '') + '%s-%d' % (short, abs(int(k)))
    return '%s-[%s]' % (short, fmt(val, 'rem'))


def dedupe(seq):
    seen, out = set(), []
    for x in seq:
        if x not in seen:
            seen.add(x)
            out.append(x)
    return out



# ------------------------------------------------------------------ ui kit

# Bootstrap components the views use. build-kit copies their CSS from
# Bootstrap into resources/css/nexora/ui-kit.css as .ui-* (same look),
# with Bootstrap's global variables replaced by their values.
KIT = [
    'container', 'container-fluid',
    'card', 'card-body', 'card-header', 'card-footer', 'card-title', 'card-subtitle', 'card-text', 'card-img-top',
    'table', 'table-responsive', 'table-sm', 'table-hover', 'table-striped', 'table-bordered', 'table-borderless',
    'table-light', 'table-dark', 'table-success', 'table-danger', 'table-warning', 'table-info', 'table-secondary',
    'table-primary', 'table-active', 'table-group-divider',
    'badge',
    'list-group', 'list-group-flush', 'list-group-item', 'list-group-item-action', 'list-unstyled',
    'btn', 'btn-sm', 'btn-lg', 'btn-link', 'btn-group', 'btn-group-sm',
    'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-light', 'btn-dark',
    'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-success', 'btn-outline-danger',
    'btn-outline-warning', 'btn-outline-info', 'btn-outline-light', 'btn-outline-dark',
    'btn-close', 'btn-close-white',
    'form-label', 'form-text', 'form-control', 'form-control-sm', 'form-control-lg', 'form-select', 'form-select-sm',
    'form-select-lg', 'form-check', 'form-check-input', 'form-check-label', 'form-check-inline', 'form-switch',
    'input-group', 'input-group-text', 'input-group-sm', 'input-group-lg',
    'is-invalid', 'is-valid', 'invalid-feedback', 'valid-feedback',
    'alert', 'alert-heading', 'alert-link', 'alert-dismissible',
    'alert-primary', 'alert-secondary', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info', 'alert-light',
    'alert-dark',
    'spinner-border', 'spinner-border-sm', 'spinner-grow', 'spinner-grow-sm',
    'pagination', 'pagination-sm', 'pagination-lg', 'page-item', 'page-link',
    'breadcrumb', 'breadcrumb-item',
    'progress', 'progress-bar', 'progress-bar-striped', 'progress-bar-animated', 'progress-stacked',
    'nav', 'nav-link', 'nav-item', 'nav-tabs', 'nav-pills', 'tab-content', 'tab-pane',
    'dropdown', 'dropdown-menu', 'dropdown-item', 'dropdown-divider', 'dropdown-header', 'dropdown-toggle',
    'dropdown-menu-end',
    'modal', 'modal-dialog', 'modal-content', 'modal-header', 'modal-title', 'modal-body', 'modal-footer',
    'modal-dialog-centered', 'modal-dialog-scrollable', 'modal-sm', 'modal-lg', 'modal-xl', 'modal-backdrop',
    'modal-fullscreen',
    'fade', 'collapse', 'collapsing',
    'img-fluid', 'img-thumbnail',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'lead', 'mark', 'display-1', 'display-2', 'display-3', 'display-4',
    'display-5', 'display-6',
]
STATE = {'active', 'disabled', 'show', 'showing', 'hiding', 'open'}


def build_kit(bootstrap_css):
    rules = parse_css(open(bootstrap_css).read())
    kit = set(KIT)
    root = {}
    for media, sel, decls in rules:
        if sel.startswith(':root') and not media:
            for p_, v, _ in decls:
                if p_.startswith('--') and p_ not in root:
                    root[p_] = v
    kept = []
    for media, sel, decls in rules:
        if 'data-bs-theme' in sel or media.startswith('@media (prefers-color-scheme'):
            continue
        parts = []
        for part in sel.split(','):
            part = part.strip()
            cls = re.findall(r'\.([a-zA-Z][\w-]*)', part)
            if cls and all(c in kit or c in STATE for c in cls) and any(c in kit for c in cls):
                parts.append(re.sub(r'\.([a-zA-Z][\w-]*)', lambda m: '.' + (m.group(1) if m.group(1) in STATE else 'ui-' + m.group(1)), part))
        if parts:
            kept.append((media, parts, decls))
    local = {p_ for _, _, decls in kept for p_, _, _ in decls if p_.startswith('--bs-')}

    def value(v):
        # resolve Bootstrap's global variables, keep the component ones (renamed)
        for _ in range(10):
            m = re.search(r'var\((--bs-[\w-]+)\s*(?:,\s*((?:[^()]|\([^()]*\))*))?\)', v)
            if not m or m.group(1) in local:
                break
            v = v[:m.start()] + root.get(m.group(1), m.group(2) or '').strip() + v[m.end():]
        # nested: resolve globals that sit inside kept var() fallbacks
        v = re.sub(r'var\((--bs-[\w-]+)\)', lambda m: m.group(0) if m.group(1) in local else root.get(m.group(1), m.group(0)), v)
        return normalize_color(v).replace('--bs-', '--ui-')

    out = ['/*', ' * GENERATED by resources/css/migrate/bs2tw.py build-kit from Bootstrap 5.3.3 - do not edit.',
           ' * Bootstrap\'s components as .ui-* classes (same look), for pages moved to Tailwind.',
           ' * Loaded before the old nx theme, where Bootstrap was.', ' */', '']
    cur = None
    for media, parts, decls in kept:
        if media != cur:
            if cur:
                out.append('}')
            if media:
                out.append(media.strip() + ' {')
            cur = media
        ind = '  ' if media else ''
        body = []
        for p_, v, imp in decls:
            v = value(v)
            body.append('%s  %s: %s%s;' % (ind, p_.replace('--bs-', '--ui-'), v, ' !important' if imp else ''))
        out.append(ind + (',\n' + ind).join(parts) + ' {')
        out += body
        out.append(ind + '}')
    if cur:
        out.append('}')
    path = os.path.join(ROOT, 'resources/css/nexora/ui-kit.css')
    open(path, 'w').write('\n'.join(out) + '\n')
    print('%d rules -> %s' % (len(kept), path))


# ---------------------------------------------------------- template walking

VOID = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'}
TAG = re.compile(r'<(/?)([a-zA-Z][\w-]*)((?:\s+(?:[^\s"\'<>/=]+)(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?|\s*\{\{.*?\}\}|\s*@\w+(?:\([^)]*\))?)*)\s*(/?)>', re.S)
CLASS_ATTR = re.compile(r'(\bclass\s*=\s*)("([^"]*)"|\'([^\']*)\')', re.S)


def convert_markup(text, conv, fname):
    """Walk the HTML (outside <script>/<style>), rewrite class attributes with grid context."""
    out = []
    pos = 0
    stack = []  # (tag, gutters-if-row)
    # blank out things that are not markup so tags inside them are not seen
    masked = mask(text)
    for m in TAG.finditer(masked):
        closing, tag, attrs, selfclose = m.group(1), m.group(2).lower(), m.group(3), m.group(4)
        line = text.count('\n', 0, m.start()) + 1
        where = '%s:%d' % (fname, line)
        if closing:
            # pop to the matching tag
            for i in range(len(stack) - 1, -1, -1):
                if stack[i][0] == tag:
                    if i != len(stack) - 1:
                        conv.warn(where, 'unbalanced </%s> (Blade @if around tags?)' % tag)
                    del stack[i:]
                    break
            continue
        parent_row = stack[-1][1] if stack else None
        real_attrs = text[m.start(3):m.end(3)]
        cm = CLASS_ATTR.search(real_attrs)
        gut = None
        if cm:
            value = cm.group(3) if cm.group(3) is not None else cm.group(4)
            quote = '"' if cm.group(3) is not None else "'"
            static = re.sub(r'\{\{.*?\}\}|\{!!.*?!!\}', ' ', value).split()
            if 'row' in static:
                gut = conv.gutters(static)
            new = conv.class_value(value, where, parent_row)
            if new != value:
                a0 = m.start(3) + cm.start(2)
                out.append(text[pos:a0])
                out.append(quote + new + quote)
                pos = m.start(3) + cm.end(2)
        elif parent_row is not None and tag not in ('template',):
            # a direct child of .row with no class: give it the .row > * styles
            new = ' '.join(conv.classes([], where, parent_row))
            a0 = m.start(2) + len(m.group(2))
            out.append(text[pos:a0])
            out.append(' class="%s"' % new)
            pos = a0
        if not selfclose and tag not in VOID:
            stack.append((tag, gut))
    out.append(text[pos:])
    return ''.join(out)


def mask(text):
    """Same length text with <script>, <style>, Blade comments / echoes / @php blocks blanked."""
    def blank(m):
        return re.sub(r'[^\n]', ' ', m.group(0))
    t = re.sub(r'\{\{--.*?--\}\}', blank, text, flags=re.S)
    t = re.sub(r'@php\b(?!\s*\().*?@endphp', blank, t, flags=re.S)
    t = re.sub(r'(<script\b[^>]*>)(.*?)(</script>)', lambda m: m.group(1) + blank_inner(m.group(2)) + m.group(3), t, flags=re.S | re.I)
    t = re.sub(r'(<style\b[^>]*>)(.*?)(</style>)', lambda m: m.group(1) + blank_inner(m.group(2)) + m.group(3), t, flags=re.S | re.I)
    t = re.sub(r'\{\{.*?\}\}|\{!!.*?!!\}', lambda m: 'X' * len(m.group(0)) if '\n' not in m.group(0) else blank(m), t, flags=re.S)
    return t


def blank_inner(s):
    return re.sub(r'[^\n]', ' ', s)


def convert_scripts(text, conv, fname):
    """class="..." in JS strings, classList.*('...'), className = '...', selectors '.x'."""
    def in_script(m):
        body = m.group(2)
        start_line = text.count('\n', 0, m.start(2))

        def where(i):
            return '%s:%d' % (fname, start_line + body.count('\n', 0, i) + 1)

        def cls_attr(mm):
            q = mm.group(2)
            val = mm.group(3)
            if '${' in val:
                parts = re.split(r'(\$\{[^}]*\})', val)
                new = []
                for i, p in enumerate(parts):
                    if i % 2:
                        new.append(conv.expr(p, where(mm.start())))
                    else:
                        lead = ' ' if p[:1].isspace() else ''
                        trail = ' ' if p[-1:].isspace() else ''
                        toks = p.split()
                        new.append(lead + ' '.join(conv.classes(toks, where(mm.start()))) + trail if toks else p)
                return mm.group(1) + q + ''.join(new) + q
            return mm.group(1) + q + ' '.join(conv.classes(val.split(), where(mm.start()))) + q
        body = re.sub(r'(class\s*=\s*\\?)(["\'])((?:(?!\2)[^<>])*?)\\?\2', cls_attr, body)

        def cl(mm):
            args = mm.group(2)
            return mm.group(1) + '(' + conv.expr(args, where(mm.start())) + ')'
        body = re.sub(r'(classList\.(?:add|remove|toggle|contains|replace))\(([^()]*)\)', cl, body)

        def cname(mm):
            return mm.group(1) + conv.expr(mm.group(2), where(mm.start()))
        body = re.sub(r'(\.className\s*[+]?=\s*)((["\'`])[^"\'`]*\3)', cname, body)

        def selector(mm):
            sel = mm.group(2)
            new = re.sub(r'\.([a-zA-Z][\w-]*)', lambda x: '.' + rename_selector_class(x.group(1), conv, where(mm.start())), sel)
            return mm.group(1) + new + mm.group(3)
        body = re.sub(r'((?:querySelector(?:All)?|closest|matches)\(\s*["\'])([^"\']+)(["\'])', selector, body)
        return m.group(1) + body + m.group(3)
    return re.sub(r'(<script\b[^>]*>)(.*?)(</script>)', in_script, text, flags=re.S | re.I)


def rename_selector_class(cls, conv, where):
    if cls in KIT:
        return 'ui-' + cls
    if cls in conv.util or GRID_COL.match(cls) or cls == 'row':
        conv.warn(where, 'Bootstrap class used in a selector: .' + cls)
    return cls


def convert_styles(text, conv, fname):
    def in_style(m):
        css = m.group(2)
        line0 = text.count('\n', 0, m.start(2))

        def sel(mm):
            head = mm.group(1)
            if head.strip().startswith('@'):
                return mm.group(0)
            where = '%s:%d' % (fname, line0 + css.count('\n', 0, mm.start()) + 1)
            new = re.sub(r'\.([a-zA-Z][\w-]*)', lambda x: '.' + rename_selector_class(x.group(1), conv, where), head)
            return new + '{'
        css = re.sub(r'([^{};]+)\{', sel, css)
        return m.group(1) + css + m.group(3)
    return re.sub(r'(<style\b[^>]*>)(.*?)(</style>)', in_style, text, flags=re.S | re.I)


def convert_php_strings(text, conv, fname):
    """Class names in @php blocks / {{ }} outside class attributes are not touched: listed for a check."""
    for m in re.finditer(r'@php\b(?!\s*\().*?@endphp', text, flags=re.S):
        for s in re.findall(r'\'([\w\s-]+)\'', m.group(0)):
            for t in s.split():
                if t in conv.util or t in KIT or t == 'row' or GRID_COL.match(t):
                    line = text.count('\n', 0, m.start()) + 1
                    conv.warn('%s:%d' % (fname, line), 'Bootstrap class in @php string (check): ' + t)
    return text


def convert_file(path, nx_scope=False):
    conv = Converter(nx_scope)
    text = open(path).read()
    fname = os.path.relpath(path, ROOT)
    text = convert_markup(text, conv, fname)
    text = convert_scripts(text, conv, fname)
    text = convert_styles(text, conv, fname)
    convert_php_strings(text, conv, fname)
    open(path, 'w').write(text)
    return conv.problems


def main():
    if len(sys.argv) >= 3 and sys.argv[1] == 'build-kit':
        build_kit(sys.argv[2])
        return
    if len(sys.argv) >= 3 and sys.argv[1] == 'build-map':
        build_map(sys.argv[2])
        return
    if len(sys.argv) >= 3 and sys.argv[1] == 'convert':
        args = sys.argv[2:]
        nx = '--nx-scope' in args
        files = [a for a in args if not a.startswith('--')]
        problems = []
        for f in files:
            problems += convert_file(f, nx)
        for p in problems:
            print('CHECK', p)
        return
    print(__doc__)


if __name__ == '__main__':
    main()
