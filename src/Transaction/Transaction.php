<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use Money\Money;

class Transaction
{
    private $money;
    private $dateTime;

    public function __construct(Money $money, \DateTimeInterface $dateTime)
    {
        $this->money = $money;
        $this->dateTime = $dateTime;
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
}
