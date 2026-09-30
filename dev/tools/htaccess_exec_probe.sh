#!/usr/bin/env bash
# Apache-side PolyShell check: plants harmless marker files under pub/media, requests them over
# HTTPS, and reports whether any was executed as PHP or served. Removes the files afterwards.
# Run on the server from the Magento root: BASE=https://dev3.example.com dev/tools/htaccess_exec_probe.sh
# Pass rules: nothing may execute; names with a PHP-like dot-segment must never be served; nothing
# under custom_options may be served. A plain .png elsewhere may be served (static file, not run).
# Needs write access to pub/media.
set -u
BASE=${BASE:?set BASE to the store URL}
MARK="polyshell-exec-probe-$$"
PAYLOAD="<?php echo 'EXEC-' . '$MARK'; ?>"
dirs=(pub/media/custom_options/quote/z/z pub/media/catalog/product/z/z pub/media/wysiwyg pub/media/tmp)
names=(probe.php probe.php.png probe.phtml.jpg probe.phar probe.png)
fail=0
created=()
for d in "${dirs[@]}"; do
  made_dir=0
  [ -d "$d" ] || { mkdir -p "$d" && made_dir=1; }
  for n in "${names[@]}"; do
    f="$d/$n"
    printf '%s' "$PAYLOAD" > "$f" && created+=("$f")
    url="$BASE/${f#pub/}"
    body=$(curl -sk --max-time 20 -w '\n%{http_code}' "$url")
    code=${body##*$'\n'}
    content=${body%$'\n'*}
    if [[ "$content" == *"EXEC-$MARK"* ]]; then
      echo "FAIL executed  $code $url"; fail=1
    elif [[ "$content" == *"$MARK"* && ( "$n" != probe.png || "$d" == *custom_options* ) ]]; then
      echo "FAIL served    $code $url"; fail=1
    elif [[ "$content" == *"$MARK"* ]]; then
      echo "OK (static)    $code $url"
    else
      echo "OK             $code $url"
    fi
  done
  [ "$made_dir" = 1 ] && created+=("DIR:$d")
done
for f in "${created[@]}"; do
  case "$f" in DIR:*) rmdir -p "${f#DIR:}" 2>/dev/null ;; *) rm -f "$f" ;; esac
done
echo "result: $([ $fail = 0 ] && echo PASS || echo FAIL)"
exit $fail
