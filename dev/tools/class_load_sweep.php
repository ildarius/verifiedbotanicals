<?php
/**
 * Upgrade harness: load every PHP class under app/code so PHP reports
 * signature-incompatibility fatals that setup:di:compile does not surface.
 * Each class is loaded in a child process so one fatal doesn't stop the sweep.
 * Run from the Magento root: php <this file> [dir relative to root, default app/code]
 *   e.g. php dev/tools/class_load_sweep.php vendor/mirasvit
 */
$root = getcwd();
if (!is_file($root . '/app/bootstrap.php')) {
    fwrite(STDERR, "Run from the Magento root.\n");
    exit(2);
}
if (($argv[1] ?? '') === '--one') {
    require $root . '/vendor/autoload.php';
    require $root . '/app/bootstrap.php';
    $c = $argv[2];
    class_exists($c) || interface_exists($c) || trait_exists($c);
    exit(0);
}
$dir = $root . '/' . trim($argv[1] ?? 'app/code', '/');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$bad = 0; $n = 0;
foreach ($it as $f) {
    $p = $f->getPathname();
    if (substr($p, -4) !== '.php' || preg_match('#/(Test|Tests|view|registration\.php)#', $p)) continue;
    $src = file_get_contents($p);
    if (!preg_match('/^namespace\s+([^;]+);/m', $src, $ns) || !preg_match('/^(?:abstract\s+|final\s+|readonly\s+)*(?:class|interface|trait)\s+(\w+)/m', $src, $cl)) continue;
    $class = $ns[1] . '\\' . $cl[1];
    $n++;
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --one ' . escapeshellarg($class) . ' 2>&1');
    if ($out !== null && trim($out) !== '' && preg_match('/Fatal|Error|Deprecated|Warning/i', $out)) {
        $bad++;
        echo "== $class\n" . trim(substr($out, 0, 600)) . "\n";
    }
}
echo "checked=$n problems=$bad\n";
