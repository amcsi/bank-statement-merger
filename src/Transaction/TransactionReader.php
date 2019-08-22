<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\RevolutReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Symfony\Contracts\Cache\CacheInterface;

class TransactionReader
{
    private $parser;
    private $thousandsSeparatorParser;
    private $cache;

    public function __construct(
        DecimalMoneyParser $parser,
        IntlLocalizedDecimalParser $thousandsSeparatorParser,
        CacheInterface $cache
    ) {
        $this->parser = $parser;
        $this->thousandsSeparatorParser = $thousandsSeparatorParser;
        $this->cache = $cache;
    }

    public function readTransactions(): TransactionHistory
    {
        $dir = __DIR__ . '/../..';
        $files = scandir($dir);
        $mobillsAppCsvFile = null;
        $revolutCsvFile = null;
        $xlsxFile = null;
        foreach ($files as $file) {
            if (strpos($file, 'INFORMES_') === 0) {
                $mobillsAppCsvFile = $file;
            }

            if (strpos($file, 'Revolut-') === 0) {
                $revolutCsvFile = $file;
            }

            if (pathinfo($file, PATHINFO_EXTENSION) === 'XLSX') {
                $xlsxFile = $file;
            }
        }

        if (!$mobillsAppCsvFile) {
            echo "No MobillsApp CSV file found.\n";
            exit(1);
        }

        if (!$revolutCsvFile) {
            echo "No Revolut CSV file found.\n";
            exit(1);
        }

        if (!$xlsxFile) {
            echo "No XLSX file found.\n";
            exit(1);
        }

        $key = sha1(
            sprintf(
                "%s%s%s%s",
                filemtime("$dir/$mobillsAppCsvFile"),
                filemtime("$dir/$revolutCsvFile"),
                filemtime("$dir/$xlsxFile"),
                getenv('TRANSACTIONS_CACHE_BUSTER')
            )
        );

        return $this->cache->get(
            $key,
            function () use ($dir, $mobillsAppCsvFile, $xlsxFile, $revolutCsvFile) {
                $transactionHistory = (new MobillsAppReader($this->parser))->buildTransactionHistory(
                    "$dir/$mobillsAppCsvFile"
                );
                $toptalTransactionHistory = (new ToptalReader($this->parser))->buildTransactionHistory(
                    "$dir/$xlsxFile"
                );
                $transactionHistory->appendTransactionHistory($toptalTransactionHistory);
                $revolutTransactionHistory = (new RevolutReader(
                    $this->thousandsSeparatorParser
                ))->buildTransactionHistory("$dir/$revolutCsvFile");
                $transactionHistory->appendTransactionHistory($revolutTransactionHistory);

                return $transactionHistory;
            }
        );
    }
}
