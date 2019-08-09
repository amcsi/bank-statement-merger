<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use Carbon\CarbonImmutable;

class TransactionStatisticsCalculator
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

    public function calculateAmountsPerMonth(TransactionHistory $transactionHistory): array
    {
        $transactions = $transactionHistory->getTransactions();
        if (!$transactions) {
            return [];
        }
        $now = new CarbonImmutable();

        $transactionsIterator = new \ArrayIterator($transactions);
        $transactionsIterator->rewind();

        $amountsPerMonth = [];
        for ($date = CarbonImmutable::instance($transactions[0]->getDateTime())->startOfMonth(
        ); $date < $now; $date = $nextMonth) {
            $nextMonth = $date->addMonth();
            $monthAmount = 0.0;
            while ($transactionsIterator->valid() && $transactionsIterator->current()->getDateTime() < $nextMonth) {
                $monthAmount += $this->currencyConverter->convertAmount(
                    $transactionsIterator->current()->getAmount(),
                    $transactionsIterator->current()->getCurrency()
                );
                $transactionsIterator->next();
            }
            $amountsPerMonth[$date->format('Y-m')] = $monthAmount;
        }
        return $amountsPerMonth;
    }
}
