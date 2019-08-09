<?php
declare(strict_types=1);

use League\Csv\CharsetConverter;
use League\Csv\Reader;

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

$reader = Reader::createFromPath("$dir/$csvFile");
$reader->setHeaderOffset(0);
$reader->setDelimiter(';');
CharsetConverter::addTo($reader, 'utf-16le', 'utf-8');
$amount = 0;
$index = -1;
$amount = (double) $_ENV['MOBILLSAPP_STARTING_AMOUNT']; // Starting balance.
foreach ($reader->getRecords() as $row) {
    $rowAmount = (float) str_replace(',', '.', $row['Valor']);
    if (!$rowAmount) {
        throw new RuntimeException('Row amount is 0');
    }
    $amount += $rowAmount;
}

echo number_format($amount, 2) . "\n";
