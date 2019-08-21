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

    /**
     * @param TransactionHistory $transactionHistory
     * @return array|TransactionAggregation[]
     * @throws \Exception
     */
    public function aggregateByMonth(TransactionHistory $transactionHistory): array
    {
        $currency = $this->currencyConverter->getCurrency();
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
            $monthSpend = 0.0;
            $monthIncome = 0.0;
            while ($transactionsIterator->valid() && $transactionsIterator->current()->getDateTime() < $nextMonth) {
                $amount = $this->currencyConverter->convertAmount(
                    $transactionsIterator->current()->getAmount(),
                    $transactionsIterator->current()->getCurrency()
                );
                $monthAmount += $amount;
                if ($amount > 0) {
                    $monthIncome += $amount;
                } else {
                    $monthSpend += -$amount;
                }
                $transactionsIterator->next();
            }
            $amountsPerMonth[$date->format('Y-m')] = new TransactionAggregation($currency, $monthIncome, $monthSpend);
        }
        return $amountsPerMonth;
    }
}
