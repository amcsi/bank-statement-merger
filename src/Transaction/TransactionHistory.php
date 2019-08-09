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

    public function getTransactions()
    {
        return $this->transactions;
    }

    public function appendTransactionHistory(self $transactionHistory): void
    {
        foreach ($transactionHistory->getTransactions() as $transaction) {
            $this->transactions[] = $transaction;
        }
    }
}
