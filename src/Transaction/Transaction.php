<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class Transaction
{
    private $amount;
    private $dateTime;

    public function __construct(float $amount, \DateTimeInterface $dateTime)
    {
        $this->amount = $amount;
        $this->dateTime = $dateTime;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
