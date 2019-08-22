<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use Money\Money;

class TransactionAggregation
{
    private $income;
    private $spend;

    public function __construct(Money $income, Money $spend)
    {
        $this->income = $income;
        $this->spend = $spend;
    }

    public function getSpend(): Money
    {
        return $this->spend;
    }

    public function getIncome(): Money
    {
        return $this->income;
    }

    public function getTotal()
    {
        return $this->income->subtract($this->spend);
    }
}
