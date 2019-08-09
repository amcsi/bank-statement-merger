<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class TransactionTotalCalculator
{
    private $currencyConverter;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function calculateTotalAmount(TransactionHistory $transactionHistory): float
    {
        $amount = 0.0;

        foreach ($transactionHistory->getTransactions() as $transaction) {
            $rowCurrency = $transaction->getCurrency();
            $rowAmount = $transaction->getAmount();
            $amount += $this->currencyConverter->convertAmount($rowAmount, $rowCurrency);
        }
        return $amount;
    }
}
