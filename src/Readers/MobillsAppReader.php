<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Readers;

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use DateTimeImmutable;
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use Money\Currency;
use Money\MoneyParser;
use RuntimeException;

class MobillsAppReader
{
    public const SOURCE = 'MobillsApp';

    private $parser;

    public function __construct(MoneyParser $parser)
    {
        $this->parser = $parser;
    }

    public function buildTransactionHistory(string $filename)
    {
        $reader = Reader::createFromPath($filename);
        $reader->setHeaderOffset(0);
        $reader->setDelimiter(';');
        CharsetConverter::addTo($reader, 'utf-16le', 'utf-8');
        $transactions = [];
        $currency = new Currency('GBP');
        $transactions[] = new Transaction(
            $this->parser->parse($_ENV['MOBILLSAPP_STARTING_AMOUNT'], $currency),
            new DateTimeImmutable('2016-06-01 00:00:00'),
            self::SOURCE
        );
        foreach ($reader->getRecords() as $row) {
            $rowAmount = str_replace(',', '.', $row['Valor']);
            if (!$rowAmount) {
                throw new RuntimeException('Row amount is 0');
            }
            $transactions[] = new Transaction(
                $this->parser->parse($rowAmount, $currency),
                DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $row['Fecha'] . ' 00:00:00'),
                self::SOURCE
            );
        }

        return new TransactionHistory($transactions);
    }
}
