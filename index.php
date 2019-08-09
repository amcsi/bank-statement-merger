<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$dir = __DIR__;
$files = scandir($dir);
$csvFile = null;
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
        $csvFile = $file;
    }
}

if (!$csvFile) {
    echo "No CSV file found.\n";
    exit(1);
}

$f = fopen("$dir/$csvFile", 'rb');
$amount = 0;
$index = -1;
$amount = (double) $_ENV['MOBILLSAPP_STARTING_AMOUNT']; // Starting balance.
while ($line = fgets($f)) {
    ++$index;
    if (!$index) {
        // Skip first row.
        continue;
    }
    $row = str_getcsv(mb_convert_encoding($line, 'utf-8', 'utf-16'), ';', '"');

    $amount += (float) str_replace(',', '.', $row[2]);
}

echo number_format($amount, 2) . "\n";
