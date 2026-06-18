<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$lines = [
    'php_version=' . PHP_VERSION,
    'sapi=' . PHP_SAPI,
    'memory_limit=' . ini_get('memory_limit'),
    'max_execution_time=' . ini_get('max_execution_time'),
    'loaded_ini=' . (php_ini_loaded_file() ?: ''),
    'scanned_ini=' . (php_ini_scanned_files() ?: ''),
    'user_ini=' . (is_file(__DIR__ . '/.user.ini') ? realpath(__DIR__ . '/.user.ini') : ''),
];

echo implode("\n", $lines) . "\n";
