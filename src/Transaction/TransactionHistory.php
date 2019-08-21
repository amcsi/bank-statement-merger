<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class TransactionHistory
{
    /**
     * @var Transaction[]|array $transactions
     */
    private $transactions;

    /**
     * TransactionHistory constructor.
     * @param Transaction[]|array $transactions
     */
    public function __construct(array $transactions)
    {
        $this->transactions = self::sortTransactions($transactions);
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
        $this->transactions = self::sortTransactions($this->transactions);
    }

    private static function sortTransactions(array $transactions)
    {
        usort(
            $transactions,
            function (Transaction $transaction1, Transaction $transaction2) {
                return $transaction1->getDateTime() <=> $transaction2->getDateTime();
            }
        );
        return $transactions;
    }
}
