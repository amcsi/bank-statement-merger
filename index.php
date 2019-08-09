<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;
use amcsi\BankStatementMerger\Transaction\CurrencyConverter;
use amcsi\BankStatementMerger\Transaction\CurrencyFormatter;
use amcsi\BankStatementMerger\Transaction\TransactionStatisticsCalculator;
use Carbon\CarbonImmutable;

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

$transactionHistory = (new MobillsAppReader())->buildTransactionHistory("$dir/$csvFile");

if (!$xlsxFile) {
    echo "No XLSX file found.\n";
    exit(1);
}

$toptalTransactionHistory = (new ToptalReader())->buildTransactionHistory("$dir/$xlsxFile");

$transactionHistory->appendTransactionHistory($toptalTransactionHistory);

$currency = 'GBP';
$currencyConverter = new CurrencyConverter($currency);
$transactionTotalCalculator = new TransactionStatisticsCalculator($currencyConverter);

$amountsPerMonth = $transactionTotalCalculator->calculateAmountsPerMonth($transactionHistory);

echo CurrencyFormatter::format(
        $transactionTotalCalculator->calculateTotalAmount($transactionHistory),
        $currency
    ) . "\n";

$totalAmountByEndOfMonth = 0.0;
foreach ($amountsPerMonth as $dateKey => $amount) {
    $totalAmountByEndOfMonth += $amount;
    printf(
        "%s: %s (%s %+9s %s)\n",
        CarbonImmutable::createFromFormat('Y-m', $dateKey)->format('Y M'),
        CurrencyFormatter::format($totalAmountByEndOfMonth, $currency),
        ($amount < 0 ? '-' : '+'),
        number_format(abs($amount), 2),
        $currency
    );
}
