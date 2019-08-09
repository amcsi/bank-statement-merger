<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Readers\MobillsAppReader;

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

$transactionHistory = (new MobillsAppReader())->buildTransactionHistory("$dir/$csvFile");

echo number_format($transactionHistory->calculateTotalAmount(), 2) . "\n";
