<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;
use amcsi\BankStatementMerger\Transaction\CurrencyFormatter;
use amcsi\BankStatementMerger\Transaction\Exchange\ExchangeRatesApi;
use amcsi\BankStatementMerger\Transaction\TransactionStatisticsCalculator;
use Carbon\CarbonImmutable;
use Exchanger\Exchanger;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Parser\DecimalMoneyParser;
use Swap\Swap;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
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

$currencies = new ISOCurrencies();
$parser = new DecimalMoneyParser($currencies);
$exchange = new Money\Exchange\SwapExchange(
    new Swap(
        new Exchanger(
            new ExchangeRatesApi(),
            new Psr16Cache(new FilesystemAdapter('', 1440, __DIR__ . '/storage/framework/cache/currencyExchange'))
        )
    )
);
$converter = new Converter($currencies, $exchange);
$formatter = new Money\Formatter\DecimalMoneyFormatter($currencies);

$transactionHistory = (new MobillsAppReader($parser))->buildTransactionHistory("$dir/$csvFile");

if (!$xlsxFile) {
    echo "No XLSX file found.\n";
    exit(1);
}

$toptalTransactionHistory = (new ToptalReader($parser))->buildTransactionHistory("$dir/$xlsxFile");

$transactionHistory->appendTransactionHistory($toptalTransactionHistory);

$currency = 'GBP';
$transactionTotalCalculator = new TransactionStatisticsCalculator($converter, new Currency('GBP'));

$monthlyAggregation = $transactionTotalCalculator->aggregateByMonth($transactionHistory);

echo $formatter->format($transactionTotalCalculator->calculateTotalAmount($transactionHistory)) . "\n";

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
