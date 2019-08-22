<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\RevolutReader;
use amcsi\BankStatementMerger\Readers\ToptalReader;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;

class TransactionReader
{
    private $parser;
    private $thousandsSeparatorParser;

    public function __construct(DecimalMoneyParser $parser, IntlLocalizedDecimalParser $thousandsSeparatorParser)
    {
        $this->parser = $parser;
        $this->thousandsSeparatorParser = $thousandsSeparatorParser;
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

        $transactionHistory = (new MobillsAppReader($this->parser))->buildTransactionHistory("$dir/$mobillsAppCsvFile");

        if (!$xlsxFile) {
            echo "No XLSX file found.\n";
            exit(1);
        }

        $toptalTransactionHistory = (new ToptalReader($this->parser))->buildTransactionHistory("$dir/$xlsxFile");

        $transactionHistory->appendTransactionHistory($toptalTransactionHistory);

        $revolutTransactionHistory = (new RevolutReader($this->thousandsSeparatorParser))->buildTransactionHistory("$dir/$revolutCsvFile");

        $transactionHistory->appendTransactionHistory($revolutTransactionHistory);

        return $transactionHistory;
    }
}
