<?php
declare(strict_types=1);

use amcsi\BankStatementMerger\Commands\OutputMonthlyAggregationsCommand;
use amcsi\BankStatementMerger\Commands\OutputTransactionsCommand;
use amcsi\BankStatementMerger\Transaction\Exchange\ExchangeRatesApi;
use amcsi\BankStatementMerger\Transaction\TransactionReader;
use Exchanger\Exchanger;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Exchange\SwapExchange;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Swap\Swap;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$currencies = new ISOCurrencies();
$parser = new DecimalMoneyParser($currencies);
$numberFormatter = new NumberFormatter('en_US', NumberFormatter::DEFAULT_STYLE);
$moneyFormatter = new NumberFormatter('hu_HU', NumberFormatter::CURRENCY);
$thousandsSeparatorParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);
$exchange = new SwapExchange(
    new Swap(
        new Exchanger(
            new ExchangeRatesApi(),
            new Psr16Cache(new FilesystemAdapter('', 1440, __DIR__ . '/storage/framework/cache/currencyExchange'))
        )
    )
);
$converter = new Converter($currencies, $exchange);
$formatter = new IntlMoneyFormatter($moneyFormatter, $currencies);
$transactionReader = new TransactionReader(
    $parser,
    $thousandsSeparatorParser,
    new FilesystemAdapter('transactions', 60 * 60 * 24, __DIR__ . '/storage/framework/cache/transactions')
);

$application = new Application();
$application->add(new OutputMonthlyAggregationsCommand($transactionReader, $converter, $formatter));
$application->add(new OutputTransactionsCommand($transactionReader, $formatter));
$application->setDefaultCommand(OutputMonthlyAggregationsCommand::NAME);

$application->run();
