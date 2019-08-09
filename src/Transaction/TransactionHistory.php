<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class TransactionHistory
{
    private $transactions;

    /**
     * TransactionHistory constructor.
     * @param Transaction[] $transactions
     */
    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function calculateTotalAmount(): float
    {
        $amount = 0.0;
        foreach ($this->transactions as $transaction) {
            $amount += $transaction->getAmount();
        }
        return $amount;
    }
}
