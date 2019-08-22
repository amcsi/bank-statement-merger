<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use Money\Money;

class Transaction
{
    private $money;
    private $dateTime;
    private $source;

    public function __construct(Money $money, \DateTimeInterface $dateTime, string $source)
    {
        $this->money = $money;
        $this->dateTime = $dateTime;
        $this->source = $source;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
