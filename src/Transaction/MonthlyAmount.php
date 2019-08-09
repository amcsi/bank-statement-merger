<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class MonthlyAmount
{
    private $amount;
    private $currency;
    private $year;
    private $month;

    public function __construct(float $amount, string $currency, int $year, int $month)
    {

        $this->amount = $amount;
        $this->currency = $currency;
        $this->year = $year;
        $this->month = $month;
    }

    public function format(): string
    {
        $monthFormatted = date('M', mktime(null, null, null, $this->month));
        return sprintf('%d %s: %s %s', $this->year, $monthFormatted, number_format($this->amount, 2), $this->currency);
    }
}
