<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Readers;

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ToptalReader
{
    public function buildTransactionHistory(string $filename)
    {
        $spreadsheet = IOFactory::load($filename);
        $worksheet = $spreadsheet->getWorksheetIterator()->current();
        $transactions = [];
        foreach ($worksheet->getRowIterator() as $index => $row) {
            if ($index <= 1) {
                // Skip header row.
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->seek('C');
            $rowAmount = (float) (string) $cellIterator->current()->getValue();
            $cellIterator->seek('D');
            $date = Date::excelToDateTimeObject($cellIterator->current()->getValue());

            $transactions[] = new Transaction($rowAmount, 'USD', $date);
        }

        return new TransactionHistory($transactions);
    }
}
