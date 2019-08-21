<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;
use amcsi\BankStatementMerger\Transaction\CurrencyConverter;
use amcsi\BankStatementMerger\Transaction\CurrencyFormatter;
use amcsi\BankStatementMerger\Transaction\TransactionStatisticsCalculator;
use Carbon\CarbonImmutable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

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

$monthlyAggregation = $transactionTotalCalculator->aggregateByMonth($transactionHistory);

echo CurrencyFormatter::format(
        $transactionTotalCalculator->calculateTotalAmount($transactionHistory),
        $currency
    ) . "\n";

$table = new Table(new ConsoleOutput());
$table->setStyle((new TableStyle())->setPadType(STR_PAD_LEFT));
$table->setHeaders(['Month', 'Balance', 'Difference', 'Income', 'Spend']);

$balance = 0.0;
foreach ($monthlyAggregation as $dateKey => $aggregate) {
    $total = $aggregate->getTotal();
    $balance += $total;
    $totalIncome = $aggregate->getIncome();
    $totalSpend = $aggregate->getSpend();
    $table->addRow(
        [
        CarbonImmutable::createFromFormat('Y-m', $dateKey)->format('Y M'),
            CurrencyFormatter::format($balance, $currency),
            sprintf("%s % 9s %s", $total < 0 ? '-' : '+', number_format(abs($total), 2), $currency),
            CurrencyFormatter::format($totalIncome, $currency),
            CurrencyFormatter::format($totalSpend, $currency),
        ]
    );
}

$table->render();
