<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$dir = __DIR__;
$files = scandir($dir);
$csvFile = null;
$xlsxFile = null;
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
        $csvFile = $file;
    }

    if (pathinfo($file, PATHINFO_EXTENSION) === 'XLSX') {
        $xlsxFile = $file;
    }
}

if (!$csvFile) {
    echo "No CSV file found.\n";
    exit(1);
}

$mobillsAppTransactionHistory = (new MobillsAppReader())->buildTransactionHistory("$dir/$csvFile");

if (!$xlsxFile) {
    echo "No XLSX file found.\n";
    exit(1);
}

$toptalTransactionHistory = (new ToptalReader())->buildTransactionHistory("$dir/$xlsxFile");

echo number_format($toptalTransactionHistory->calculateTotalAmount(), 2) . "\n";
