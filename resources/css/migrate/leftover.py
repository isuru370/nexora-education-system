#!/usr/bin/env python3
"""List Bootstrap class names still used in views (class="", JS class strings, @php strings).
   python3 resources/css/migrate/leftover.py FILE...
"""
import json, os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import bs2tw

util = json.load(open(bs2tw.MAP_FILE))['utilities']
names = set(util) | set(bs2tw.KIT) | {'row', 'small', 'text-truncate', 'visually-hidden'}
bad = 0
for f in sys.argv[1:]:
    text = open(f).read()
    for i, line in enumerate(text.split('\n'), 1):
        for m in re.finditer(r'class(?:Name)?\s*=\s*\\?["\']([^"\']*)|classList\.\w+\(([^)]*)\)|\'([\w\s-]+)\'', line):
            val = next(g for g in m.groups() if g is not None)
            toks = val.split()
            if m.group(3) is not None and not all(re.fullmatch(r'[a-z][\w-]*', t) for t in toks):
                continue  # a sentence, not a class list
            for t in toks:
                if (t in names or bs2tw.GRID_COL.match(t) or bs2tw.GUTTER.match(t)) and t not in ('active', 'show', 'disabled'):
                    print('%s:%d: %s   | %s' % (f, i, t, line.strip()[:140]))
                    bad += 1
print(bad, 'left')
