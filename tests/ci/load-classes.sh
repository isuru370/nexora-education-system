#!/bin/bash
# Loads every test class on its own, so one PHP fatal error (e.g. a helper
# method name that clashes with PHPUnit / Laravel) does not hide the others.
fails=0
for f in $(find tests -name "*Test.php"); do
  class="Tests\\$(echo "${f#tests/}" | sed 's/\.php$//; s#/#\\\\#g')"
  out=$(php -r "require 'vendor/autoload.php'; class_exists('$class');" 2>&1)
  if [ -n "$out" ]; then echo "$f: $out"; fails=$((fails+1)); fi
done
echo "class load problems: $fails"
