<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
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
$transactions = [];
$transactions[] = new Transaction((double) $_ENV['MOBILLSAPP_STARTING_AMOUNT'], new DateTimeImmutable('2016-06-01 00:00:00'));
foreach ($reader->getRecords() as $row) {
    $rowAmount = (float) str_replace(',', '.', $row['Valor']);
    if (!$rowAmount) {
        throw new RuntimeException('Row amount is 0');
    }
    $transactions[] = new Transaction($rowAmount, DateTimeImmutable::createFromFormat('d/m/Y', $row['Fecha']));
}

$transactionHistory = new TransactionHistory($transactions);

echo number_format($transactionHistory->calculateTotalAmount(), 2) . "\n";
