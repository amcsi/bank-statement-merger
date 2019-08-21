<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class Transaction
{
    private $amount;
    private $currency;
    private $dateTime;

    public function __construct(float $amount, string $currency, \DateTimeInterface $dateTime)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->dateTime = $dateTime;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }
}
