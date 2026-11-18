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
    css_text = open(bootstrap_css).read()
    border_order = {}
    for cls in utilities:
        if BORDER_TOK.match(cls):
            m = re.search(r'(?m)^\.%s\s*\{' % re.escape(cls), css_text)
            if m:
                border_order[cls] = m.start()
    json.dump({'utilities': utilities, 'border_order': border_order}, open(MAP_FILE, 'w'), indent=1, sort_keys=True)
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

GRID_ANY = re.compile(r'^(row|col(-(sm|md|lg|xl|xxl))?(-(auto|\d+))?|g[xy]?(-(sm|md|lg|xl|xxl))?-\d|row-cols(-(sm|md|lg|xl|xxl))?-(auto|\d)|offset(-(sm|md|lg|xl|xxl))?-\d+)$')
GRID_COL = re.compile(r'^col(?:-(sm|md|lg|xl|xxl))?(?:-(auto|\d+))?$')
GUTTER = re.compile(r'^g([xy])?(?:-(sm|md|lg|xl|xxl))?-(\d)$')
GUTTERS = {'0': Fraction(0), '1': Fraction(1, 4), '2': Fraction(1, 2), '3': Fraction(1), '4': Fraction(3, 2), '5': Fraction(3)}


class Converter:
    def __init__(self, nx_scope=False):
        data = json.load(open(MAP_FILE))
        self.util = data['utilities']
        self.nx_scope = nx_scope
        self.problems = []
        load_bs_colors()
        load_border_order()

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
    def classes(self, toks, where='', row_parent=None, ui_grid=False):
        """row_parent: gutters of the parent .row ({bp: (gx, gy)}) when this element is a direct
        child of a row moved to Tailwind. ui_grid: a row with no columns in the markup (they come
        from JavaScript): it and its columns use the ui-kit grid (.ui-row / .ui-col-*)."""
        static = set(toks)
        out = []
        extra = []
        if 'row' in static and not ui_grid:
            extra += self.row_classes(self.gutters(toks))
        if row_parent is not None:
            extra += self.col_classes(toks, row_parent, where)
        op = [t for t in toks if re.fullmatch(r'bg-opacity-\d+', t)]
        borders = [t for t in toks if BORDER_TOK.match(t) and t in self.util]
        merged = self.merge_borders(borders) if len(borders) > 1 else None
        for tok in toks:
            if merged is not None and tok in borders:
                if tok == borders[0]:
                    out += merged
                continue
            if GRID_ANY.match(tok):
                if tok == 'row' or GUTTER.match(tok):
                    if ui_grid:
                        out.append('ui-' + tok)
                elif row_parent is not None and GRID_COL.match(tok):
                    pass  # done by col_classes
                else:
                    out.append('ui-' + tok)  # column of a ui-row (filled by JS)
                continue
            if tok in op:
                continue
            mapped = self.token(tok, static, where)
            m = re.fullmatch(r'bg-(primary|secondary|success|danger|warning|info|light|dark|white|black)', tok)
            if op and m:
                c = BS_COLORS.get(m.group(1))
                a = int(op[0].split('-')[-1]) / 100
                if c:
                    mapped = ['tw:bg-[rgba(%d,%d,%d,%s)]!' % (hex_rgb(c) + ('%g' % a,))]
            out += mapped
        return dedupe(extra + out)

    def merge_borders(self, toks):
        """Several border classes on one element: Tailwind does not keep Bootstrap's order for
        them (border then border-primary ...), so work out the final border here."""
        sides = ('top', 'right', 'bottom', 'left')
        cur = {side: {} for side in sides}
        order = sorted(toks, key=lambda t: BORDER_ORDER.get(t, 0))
        for t in order:
            for cls in self.util[t]:
                m = re.fullmatch(r'tw:\[([\w-]+):(.*)\]!', cls)
                if m:
                    prop, val = m.group(1), m.group(2).replace('_', ' ')
                else:
                    prop, val = BORDER_NAMED.get(cls, (None, None))
                    if prop is None:
                        return None
                apply_border(cur, prop, val)
        out = []
        if all(cur[x] == cur['top'] for x in sides):
            out.append(border_class('border', cur['top']))
        else:
            for x in sides:
                if cur[x]:
                    out.append(border_class('border-' + x, cur[x]))
        return out

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
    def class_value(self, value, where='', row_parent=None, ui_grid=False):
        parts = re.split(r'(\{\{.*?\}\}|\{!!.*?!!\}|@\w+(?:\((?:[^()]|\([^()]*\))*\))?)', value, flags=re.S)
        static = [t for i, p in enumerate(parts) if i % 2 == 0 for t in p.split()]
        mapped = self.classes(static, where, row_parent, ui_grid)
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
                if GRID_ANY.match(t):
                    new.append('ui-' + t)
                else:
                    new += self.token(t, set(static) | set(toks), where)
            return q + ' '.join(dedupe(new)) + q
        return re.sub(r'(["\'])((?:(?!\1).)*)\1', rep, text)


BORDER_TOK = re.compile(r'^border(-(top|end|bottom|start|0|1|2|3|4|5|primary|secondary|success|danger|warning|info|light|dark|black|white|[a-z]+-subtle))*$')
BORDER_ORDER = {}   # Bootstrap's order of the border classes (later wins)
BORDER_NAMED = {}   # tw class -> (prop, value) for the named ones


def apply_border(cur, prop, val):
    sides = ('top', 'right', 'bottom', 'left')
    m = re.fullmatch(r'border(?:-(top|right|bottom|left))?(?:-(width|style|color))?', prop)
    which = [m.group(1)] if m.group(1) else list(sides)
    if m.group(2):
        for x in which:
            cur[x][m.group(2)] = val
        return
    parts = val.split()
    width, style, color = '0', 'none', 'currentcolor'
    if val.strip() not in ('0', 'none'):
        for p_ in parts:
            if re.fullmatch(r'[\d.]+(px|rem|em)?', p_):
                width = p_
            elif p_ in ('solid', 'dashed', 'dotted', 'double', 'none', 'hidden'):
                style = p_
            else:
                color = p_
    for x in which:
        cur[x] = {'width': width, 'style': style, 'color': color}


def border_class(prop, d):
    if set(d) == {'width', 'style', 'color'}:
        return 'tw:[%s:%s_%s_%s]!' % (prop, d['width'], d['style'], d['color'])
    return ' '.join('tw:[%s-%s:%s]!' % (prop, k, v) for k, v in d.items())


def load_border_order():
    if BORDER_ORDER or not os.path.exists(THEME_FILE):
        return
    data = json.load(open(MAP_FILE))
    BORDER_ORDER.update(data.get('border_order', {}))
    theme = dict(re.findall(r'--color-(bs-[\w-]+):\s*([^;]+);', open(THEME_FILE).read()))
    for util, prop, val in re.findall(r'@utility ([\w-]+) \{\s*([\w-]+): ([^;]+);', open(THEME_FILE).read()):
        BORDER_NAMED['tw:%s!' % util] = (prop, val)
    for name, val in theme.items():
        BORDER_NAMED['tw:border-%s!' % name] = ('border-color', val)


BS_COLORS = {}


def hex_rgb(c):
    c = c.lstrip('#')
    if len(c) == 3:
        c = ''.join(x * 2 for x in c)
    return tuple(int(c[i:i + 2], 16) for i in (0, 2, 4))


def load_bs_colors():
    if os.path.exists(THEME_FILE):
        for name, v in re.findall(r'--color-bs-([\w-]+):\s*(#[0-9a-f]+);', open(THEME_FILE).read()):
            BS_COLORS[name] = v


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
    'modal-fullscreen', 'modal-static',
    'fade', 'collapse', 'collapsing',
    'img-fluid', 'img-thumbnail',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'lead', 'mark', 'display-1', 'display-2', 'display-3', 'display-4',
    'display-5', 'display-6',
]
STATE = {'active', 'disabled', 'show', 'showing', 'hiding', 'open'}


def build_kit(bootstrap_css):
    rules = parse_css(open(bootstrap_css).read())
    kit = set(KIT)
    # the grid too, for rows filled by JavaScript (see Converter.classes)
    kit |= {c for c in re.findall(r'\.([a-zA-Z][\w-]*)', open(bootstrap_css).read()) if GRID_ANY.match(c)}
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


def walk(masked):
    """Elements in (masked) markup: [{m, tag, parent, children}] (children = element count)."""
    els = []
    stack = []
    for m in TAG.finditer(masked):
        closing, tag, selfclose = m.group(1), m.group(2).lower(), m.group(4)
        if closing:
            for i in range(len(stack) - 1, -1, -1):
                if els[stack[i]]['tag'] == tag:
                    del stack[i:]
                    break
            continue
        parent = stack[-1] if stack else None
        els.append({'m': m, 'tag': tag, 'parent': parent, 'children': 0})
        if parent is not None:
            els[parent]['children'] += 1
        if not selfclose and tag not in VOID:
            stack.append(len(els) - 1)
    return els


def markup_edits(text, masked, conv, where_of, js=False):
    """[(start, end, new)] class attribute rewrites for the markup in text."""
    els = walk(masked)
    info = []  # per element: gutters if a Tailwind row, 'ui' if a ui-kit row, else None
    edits = []
    for el in els:
        m = el['m']
        where = where_of(m.start())
        attrs = text[m.start(3):m.end(3)]
        cm = CLASS_ATTR.search(attrs)
        parent = info[el['parent']] if el['parent'] is not None else None
        parent_row = parent if isinstance(parent, dict) else None
        value = None
        if cm:
            value = cm.group(3) if cm.group(3) is not None else cm.group(4)
        static = re.sub(r'\{\{.*?\}\}|\{!!.*?!!\}|\$\{(?:[^{}]|\{[^{}]*\})*\}', ' ', value or '').split()
        mine = None
        ui_grid = False
        if 'row' in static:
            if el['children'] and '${' not in (value or '') and not js_children(el, els, text):
                mine = conv.gutters(static)
            else:
                ui_grid = True
                mine = 'ui'
        info.append(mine)
        if cm:
            quote = '"' if cm.group(3) is not None else "'"
            a0 = m.start(3) + cm.start(2)
            if js and '${' in value:
                # only the plain parts: ${...} may hold other templates
                pos = 0
                for part in re.split(r'(\$\{(?:[^{}]|\{(?:[^{}]|\{[^{}]*\})*\})*\})', value):
                    if part.startswith('${'):
                        if '`' not in part:
                            new = conv.expr(part, where, static)
                            if new != part:
                                edits.append((a0 + 1 + pos, a0 + 1 + pos + len(part), new))
                        pos += len(part)
                        continue
                    toks = part.split()
                    if toks:
                        lead = part[:len(part) - len(part.lstrip())]
                        trail = part[len(part.rstrip()):]
                        new = lead + ' '.join(conv.classes(toks, where, parent_row, ui_grid)) + trail
                        if new != part:
                            edits.append((a0 + 1 + pos, a0 + 1 + pos + len(part), new))
                    pos += len(part)
            else:
                new = conv.class_value(value, where, parent_row, ui_grid)
                if new != value:
                    edits.append((a0, m.start(3) + cm.end(2), quote + new + quote))
        elif parent_row is not None and el['tag'] != 'template':
            # a direct child of a Tailwind row with no class: give it the .row > * styles
            new = ' '.join(conv.classes([], where, parent_row))
            a0 = m.start(2) + len(m.group(2))
            edits.append((a0, a0, ' class="%s"' % new))
    return edits


def js_children(el, els, text):
    """True when the row's content also comes from Blade loops of partials we cannot see (none here)."""
    return False


def apply_edits(text, edits):
    for start, end, new in sorted(edits, key=lambda e: e[0], reverse=True):
        text = text[:start] + new + text[end:]
    return text


def convert_markup(text, conv, fname):
    """Rewrite class attributes in the HTML (outside <script>/<style>), with grid context."""
    def where_of(i):
        return '%s:%d' % (fname, text.count('\n', 0, i) + 1)
    return apply_edits(text, markup_edits(text, mask(text), conv, where_of))


def js_templates(body):
    """[(start, end, [(s, e) of ${...}])] for each `template literal` content in JS source."""
    out = []
    i, n = 0, len(body)
    stack = []  # open templates: [start, holes, brace depth when in ${}]

    def skip_string(i, q):
        i += 1
        while i < n and body[i] != q:
            i += 2 if body[i] == '\\' else 1
        return i + 1
    while i < n:
        ch = body[i]
        top = stack[-1] if stack else None
        in_tpl = top is not None and top[2] is None
        if in_tpl:
            if ch == '\\':
                i += 2
                continue
            if ch == '`':
                out.append((top[0], i, top[1]))
                stack.pop()
                if stack and stack[-1][2] is not None:
                    pass
                i += 1
                continue
            if body.startswith('${', i):
                top[2] = 0
                top[1].append([i, None])
                i += 2
                continue
            i += 1
            continue
        # code (top level or inside ${ })
        if ch in '"\'':
            i = skip_string(i, ch)
            continue
        if body.startswith('//', i):
            j = body.find('\n', i)
            i = n if j < 0 else j
            continue
        if body.startswith('/*', i):
            j = body.find('*/', i)
            i = n if j < 0 else j + 2
            continue
        if ch == '`':
            stack.append([i + 1, [], None])
            i += 1
            continue
        if top is not None:
            if ch == '{':
                top[2] += 1
            elif ch == '}':
                if top[2] == 0:
                    top[1][-1][1] = i + 1
                    top[2] = None
                else:
                    top[2] -= 1
        i += 1
    return [(s, e, [(a, b if b else e) for a, b in holes]) for s, e, holes in out]


def convert_js_templates(text, conv, fname):
    def in_script(m):
        body = m.group(2)
        line0 = text.count('\n', 0, m.start(2))

        def where_of(i):
            return '%s:%d' % (fname, line0 + body.count('\n', 0, i) + 1)
        edits = []
        for s_, e_, holes in js_templates(body):
            masked = list(body)
            for i in range(len(body)):
                if i < s_ or i >= e_:
                    if masked[i] != '\n':
                        masked[i] = ' '
            for a, b in holes:
                for i in range(a, min(b, len(body))):
                    if masked[i] != '\n':
                        masked[i] = 'X' if 0 else ' '
            masked = ''.join(masked)
            # keep ${...} inside attribute values visible as text so CLASS_ATTR sees them
            for ed in markup_edits_js(body, masked, holes, conv, where_of):
                edits.append(ed)
        return m.group(1) + apply_edits(body, edits) + m.group(3)
    return re.sub(r'(<script\b[^>]*>)(.*?)(</script>)', in_script, text, flags=re.S | re.I)


def markup_edits_js(body, masked, holes, conv, where_of):
    # tags are found in the masked text (holes blanked); attributes are read from the real text,
    # but a hole inside a tag's attributes would be blank in masked: put a placeholder there
    m2 = list(masked)
    for a, b in holes:
        # inside a tag? (an unclosed < before it on the masked text)
        before = masked[:a]
        lt, gt = before.rfind('<'), before.rfind('>')
        if lt > gt:
            for i in range(a, b):
                m2[i] = 'X' if body[i] != '\n' else '\n'
    return markup_edits(body, ''.join(m2), conv, where_of, js=True)


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
                        new.append(lead + ' '.join(conv.classes(toks, where(mm.start()), None, True)) + trail if toks else p)
                return mm.group(1) + q + ''.join(new) + q
            return mm.group(1) + q + ' '.join(conv.classes(val.split(), where(mm.start()), None, True)) + q
        body = re.sub(r'(class\s*=\s*\\?)(["\'])((?:(?!\2)[^<>])*?)\\?\2', cls_attr, body)

        def cl(mm):
            args = mm.group(2)
            return mm.group(1) + '(' + conv.expr(args, where(mm.start())) + ')'
        body = re.sub(r'(classList\.(?:add|remove|toggle|contains|replace))\(([^()]*)\)', cl, body)

        def cname(mm):
            lit = mm.group(2)
            if lit[0] != '`':
                return mm.group(1) + conv.expr(lit, where(mm.start()))
            parts = re.split(r'(\$\{[^}]*\})', lit[1:-1])
            out = []
            for i, part in enumerate(parts):
                if i % 2:
                    out.append(conv.expr(part, where(mm.start())))
                    continue
                toks = part.split()
                partial = toks[-1] if toks and part.endswith('-') and i + 1 < len(parts) else None
                if partial:
                    toks = toks[:-1]
                mapped = conv.classes(toks, where(mm.start()), None, True) if toks else []
                if partial:
                    if any(k.startswith(partial) for k in KIT):
                        mapped.append('ui-' + partial)
                    else:
                        if any(k.startswith(partial) for k in conv.util):
                            conv.warn(where(mm.start()), 'class built from a variable: ' + partial + '${...}')
                        mapped.append(partial)
                lead = ' ' if part[:1].isspace() else ''
                trail = ' ' if part[-1:].isspace() and not partial else ''
                out.append(lead + ' '.join(mapped) + trail if (toks or partial) else part)
            return mm.group(1) + '`' + ''.join(out) + '`'
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


def convert_js_api(text, conv, fname):
    """Bootstrap JS -> public/js/nx-ui.js (same API names for modals)."""
    text = re.sub(r'data-bs-(toggle|target|dismiss|backdrop|keyboard)(\s*=)', r'data-nx-\1\2', text)
    text = re.sub(r'\[data-bs-(toggle|target|dismiss)', r'[data-nx-\1', text)
    text = re.sub(r'\bbootstrap\.Modal\b', 'NxUi.Modal', text)
    text = re.sub(r'\b(show|shown|hide|hidden)\.bs\.(modal|tab)\b', r'\1.nx.\2', text)
    for m in re.finditer(r'\bbootstrap\.\w+|data-bs-\w+', text):
        line = text.count('\n', 0, m.start()) + 1
        conv.warn('%s:%d' % (fname, line), 'Bootstrap JS left: ' + m.group(0))
    return text


def convert_file(path, nx_scope=False):
    conv = Converter(nx_scope)
    text = open(path).read()
    fname = os.path.relpath(path, ROOT)
    text = convert_markup(text, conv, fname)
    text = convert_js_templates(text, conv, fname)
    text = convert_scripts(text, conv, fname)
    text = convert_styles(text, conv, fname)
    convert_php_strings(text, conv, fname)
    text = convert_js_api(text, conv, fname)
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
