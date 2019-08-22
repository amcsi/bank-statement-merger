<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Readers;

use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use DateTimeImmutable;
use League\Csv\Reader;
use Money\Currency;
use Money\MoneyParser;

class RevolutReader
{
    public const SOURCE = 'Revolut';
    private const SPANISH_MONTH_MAP = [
        'ene' => 1,
        'feb' => 2,
        'mar' => 3,
        'abr' => 4,
        'may' => 5,
        'jun' => 6,
        'jul' => 7,
        'ago' => 8,
        'sep' => 9,
        'oct' => 10,
        'nov' => 11,
        'dic' => 12,
    ];
    private $parser;

    public function __construct(MoneyParser $parser)
    {
        $this->parser = $parser;
    }

    public function buildTransactionHistory(string $filename)
    {
        $reader = Reader::createFromPath($filename);
        $reader->setDelimiter(';');
        $transactions = [];
        $currency = new Currency('GBP');
        $records = $reader->getRecords();
        foreach ($records as $index => $row) {
            if (!$index) {
                // Skip header row.
                continue;
            }
            $spend = trim($row[2]);
            $income = trim($row[3]);
            $rowAmount = $spend ? "-{$spend}" : $income;
            $transactions[] = new Transaction($this->parser->parse($rowAmount, $currency), self::parseDate($row[0]), self::SOURCE);
        }

        return new TransactionHistory($transactions);
    }

    private static function parseDate(string $date)
    {
        $parts = explode(' ', trim($date));
        $month = self::SPANISH_MONTH_MAP[rtrim($parts[1], '.')];
        return DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "{$parts[2]}-{$month}-{$parts[0]} 00:00:00");
    }
}
