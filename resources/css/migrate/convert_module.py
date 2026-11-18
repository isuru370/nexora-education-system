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
    # a whole HTML document of its own (PDF, printed receipt): not a page in the app layout
    own_doc = re.search(r'^\s*(<!DOCTYPE|<html)', re.sub(r'\{\{--.*?--\}\}', '', t, flags=re.S), re.I)
    return ('pdf' in base or 'bootstrap@' in t or own_doc) and "@extends('layouts.app')" not in t


problems = []
util = bs2tw.Converter().util
for d in sys.argv[1:]:
    paths = [p for p in sorted(glob.glob(os.path.join(views, d, '**/*.blade.php'), recursive=True))]
    # a folder's partials share their <style> / scripts: one marker set per folder
    markers = set()
    for path in paths:
        if not skip(path):
            markers |= bs2tw.find_markers(texts[path], util)
    if markers:
        print('MARKERS', d, ' '.join(sorted(markers)))
    for path in paths:
        rel = os.path.relpath(path, ROOT)
        if skip(path):
            print('SKIP', rel)
            continue
        problems += bs2tw.convert_file(path, in_scope(path), markers)
for p in problems:
    print('CHECK', p)
