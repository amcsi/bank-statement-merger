<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Readers;

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use DateTimeImmutable;
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use RuntimeException;

class MobillsAppReader
{
    public function buildTransactionHistory(string $filename)
    {
        $reader = Reader::createFromPath($filename);
        $reader->setHeaderOffset(0);
        $reader->setDelimiter(';');
        CharsetConverter::addTo($reader, 'utf-16le', 'utf-8');
        $transactions = [];
        $transactions[] = new Transaction((double) $_ENV['MOBILLSAPP_STARTING_AMOUNT'],
            new DateTimeImmutable('2016-06-01 00:00:00'));
        foreach ($reader->getRecords() as $row) {
            $rowAmount = (float) str_replace(',', '.', $row['Valor']);
            if (!$rowAmount) {
                throw new RuntimeException('Row amount is 0');
            }
            $transactions[] = new Transaction($rowAmount, DateTimeImmutable::createFromFormat('d/m/Y', $row['Fecha']));
        }

        return new TransactionHistory($transactions);
    }
}
