<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class TransactionAggregation
{
    private $currency;
    private $income;
    private $spend;

    public function __construct(string $currency, float $income, float $spend)
    {
        $this->currency = $currency;
        $this->income = $income;
        $this->spend = $spend;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getSpend(): float
    {
        return $this->spend;
    }

    /**
     * @return float
     */
    public function getIncome(): float
    {
        return $this->income;
    }

    public function getTotal()
    {
        return $this->income - $this->spend;
    }
}
