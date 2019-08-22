<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

use Carbon\CarbonImmutable;
use Money\Converter;
use Money\Currency;
use Money\Money;

class TransactionStatisticsCalculator
{
    private $converter;
    private $currency;

    public function __construct(Converter $converter, Currency $currency)
    {
        $this->converter = $converter;
        $this->currency = $currency;
    }

    public function calculateTotalAmount(TransactionHistory $transactionHistory): Money
    {
        $amount = new Money(0, $this->currency);

        foreach ($transactionHistory->getTransactions() as $transaction) {
            $amount = $amount->add($this->converter->convert($transaction->getMoney(), $this->currency));
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
        $currency = $this->currency;
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
            $monthSpend = 0;
            $monthIncome = 0;
            while ($transactionsIterator->valid() && ($current = $transactionsIterator->current(
                )) && $current->getDateTime() < $nextMonth) {
                $money = $this->converter->convert($current->getMoney(), $this->currency);
                $amount = (int) $money->getAmount();
                if ($amount > 0) {
                    $monthIncome += $amount;
                } else {
                    $monthSpend += -$amount;
                }
                $transactionsIterator->next();
            }
            $amountsPerMonth[$date->format('Y-m')] = new TransactionAggregation(
                new Money($monthIncome, $currency),
                new Money($monthSpend, $currency)
            );
        }
        return $amountsPerMonth;
    }
}
