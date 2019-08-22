<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Readers;

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use Money\Currency;
use Money\MoneyParser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ToptalReader
{
    public const SOURCE = 'Toptal';

    private $parser;

    public function __construct(MoneyParser $parser)
    {
        $this->parser = $parser;
    }

    public function buildTransactionHistory(string $filename)
    {
        $spreadsheet = IOFactory::load($filename);
        $worksheet = $spreadsheet->getWorksheetIterator()->current();
        $transactions = [];
        $currency = new Currency('USD');
        foreach ($worksheet->getRowIterator() as $index => $row) {
            if ($index <= 1) {
                // Skip header row.
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->seek('C');
            $rowAmount = (string) $cellIterator->current()->getValue();
            $cellIterator->seek('D');
            $date = Date::excelToDateTimeObject($cellIterator->current()->getValue());

            $transactions[] = new Transaction($this->parser->parse($rowAmount, $currency), $date, self::SOURCE);
        }

        return new TransactionHistory($transactions);
    }
}
