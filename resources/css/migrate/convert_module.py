#!/usr/bin/env python3
"""Convert whole view folders:  python3 resources/css/migrate/convert_module.py students exams ...
- skips PDF views (dompdf, no app CSS) and pages with their own Bootstrap <link>
- --nx-scope for a view inside .dashboard-page (itself, or a page that @includes it)
"""
import glob, os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import bs2tw

ROOT = bs2tw.ROOT
views = os.path.join(ROOT, 'resources/views')


def view_name(path):
    return os.path.relpath(path, views)[:-len('.blade.php')].replace('/', '.')


all_views = glob.glob(os.path.join(views, '**/*.blade.php'), recursive=True)
texts = {p: open(p).read() for p in all_views}


def includers(name):
    pat = re.compile(r"@(?:include|includeIf|includeWhen|each)\(\s*(?:[^,]*,\s*)?'%s'" % re.escape(name))
    return [p for p, t in texts.items() if pat.search(t)]


def in_scope(path, seen=()):
    if 'dashboard-page' in texts[path]:
        return True
    return any(in_scope(p, seen + (path,)) for p in includers(view_name(path)) if p not in seen)


def skip(path):
    t = texts[path]
    base = os.path.basename(path)
    return ('pdf' in base or 'bootstrap@' in t or '<html' in t.lower()) and "@extends('layouts.app')" not in t


problems = []
for d in sys.argv[1:]:
    for path in sorted(glob.glob(os.path.join(views, d, '**/*.blade.php'), recursive=True)):
        rel = os.path.relpath(path, ROOT)
        if skip(path):
            print('SKIP', rel)
            continue
        problems += bs2tw.convert_file(path, in_scope(path))
for p in problems:
    print('CHECK', p)
