#!/usr/bin/env python3
"""Check that classes used together keep Bootstrap's winner after the move.
   python3 resources/css/migrate/order_check.py bootstrap.css ORIGINAL_FILE...
Two Bootstrap utilities on one element that set the same property: the one
later in bootstrap.css wins. The Tailwind classes they map to must be in the
same order in public/css/nexora.css (or the pair is listed)."""
import json, os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import bs2tw

util = json.load(open(bs2tw.MAP_FILE))['utilities']
bs = open(sys.argv[1]).read()
built = open(os.path.join(bs2tw.ROOT, 'public/css/nexora.css')).read()

def bs_pos(c):
    m = re.search(r'(?m)^\s*\.%s\s*[{,]' % re.escape(c), bs)
    return m.start() if m else None

def tw_pos(t):
    esc = re.sub(r'([^\w-])', r'\\\1', t)
    i = built.find('.' + esc + ' ')
    if i < 0:
        i = built.find('.' + esc + ':')
    return i if i >= 0 else None

def props(c):
    ps = set()
    for m in re.finditer(r'(?m)^\s*\.%s\s*\{([^}]*)\}' % re.escape(c), bs):
        for d in m.group(1).split(';'):
            if ':' in d and not d.strip().startswith('--'):
                p = d.split(':')[0].strip()
                ps.add(p)
                if re.match(r'(margin|padding)-', p) or re.match(r'border(-(top|right|bottom|left))?(-(width|style|color))?$', p):
                    ps.add(p.split('-')[0])  # margin-top ~ margin, border-color ~ border
    return ps

bad = 0
seen = set()
for f in sys.argv[2:]:
    for m in re.finditer(r'class="([^"]*)"', open(f).read()):
        toks = [t for t in m.group(1).split() if t in util and '{' not in t]
        for i, a in enumerate(toks):
            for b in toks[i + 1:]:
                if (a, b) in seen or not (props(a) & props(b)):
                    continue
                seen.add((a, b))
                ba, bb = bs_pos(a), bs_pos(b)
                ta = [tw_pos(t) for t in util[a]]
                tb = [tw_pos(t) for t in util[b]]
                if None in (ba, bb) or None in ta + tb:
                    continue
                if (ba < bb) != (max(ta) < min(tb)) and (ba > bb) != (min(ta) > max(tb)):
                    print('ORDER', a, b, util[a], util[b])
                    bad += 1
print(bad, 'order problems')
