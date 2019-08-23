<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Commands;

use amcsi\BankStatementMerger\Readers\MobillsAppReader;
use amcsi\BankStatementMerger\Readers\RevolutReader;
use amcsi\BankStatementMerger\Transaction\Transaction;
use amcsi\BankStatementMerger\Transaction\TransactionAggregation;
use amcsi\BankStatementMerger\Transaction\TransactionHistory;
use amcsi\BankStatementMerger\Transaction\TransactionReader;
use amcsi\BankStatementMerger\Transaction\TransactionStatisticsCalculator;
use Carbon\CarbonImmutable;
use Money\Converter;
use Money\Currency;
use Money\MoneyFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function iter\drop;

class OutputMonthlyAggregationsCommand extends Command
{
    public const NAME = 'output-monthly-aggregations';
    private $transactionReader;
    private $converter;
    private $formatter;

    public function __construct(TransactionReader $transactionReader, Converter $converter, MoneyFormatter $formatter)
    {
        parent::__construct('output-monthly-aggregations');
        $this->transactionReader = $transactionReader;
        $this->converter = $converter;
        $this->formatter = $formatter;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Outputs aggregations of income, spend and balance per month.');
        $this->addOption('currency', 'c', InputArgument::OPTIONAL, 'Currency to display results in', 'GBP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transactionHistory = $this->transactionReader->readTransactions();

        $transactionHistory = self::removeTransfersBetweenAccounts($transactionHistory);

        $currency = new Currency($input->getOption('currency'));
        $transactionTotalCalculator = new TransactionStatisticsCalculator($this->converter, $currency);

        $monthlyAggregation = $transactionTotalCalculator->aggregateByMonth($transactionHistory);

        echo $this->formatter->format($transactionTotalCalculator->calculateTotalAmount($transactionHistory)) . "\n";

        $table = new Table($output);
        $table->setStyle((new TableStyle())->setPadType(STR_PAD_LEFT));
        $table->setHeaders(['Month', 'Balance', 'Difference', 'Income', 'Spend']);

        $balance = reset($monthlyAggregation)->getTotal();
        foreach (drop(1, $monthlyAggregation) as $dateKey => $aggregate) {
            /** @var TransactionAggregation $aggregate */
            $total = $aggregate->getTotal();
            $balance = $balance->add($total);
            $totalIncome = $aggregate->getIncome();
            $totalSpend = $aggregate->getSpend();
            $color = $total->isPositive() ? 'green' : 'red';
            $table->addRow(
                [
                    CarbonImmutable::createFromFormat('Y-m', $dateKey)->format('Y M'),
                    $this->formatter->format($balance),
                    sprintf("<fg=$color>%s%s</>", $total->isPositive() ? '+' : '', $this->formatter->format($total)),
                    $this->formatter->format($totalIncome),
                    $this->formatter->format($totalSpend),
                ]
            );
        }

        $table->render();
    }

    private static function removeTransfersBetweenAccounts(TransactionHistory $transactionHistory)
    {
        $transactions = $transactionHistory->getTransactions();
        $newTransactions = [];
        /** @var Transaction[] $potentialTransferTransactions */
        $potentialTransferTransactions = [];

        foreach ($transactions as $index => $transaction) {
            $money = $transaction->getMoney();
            if (!(
                ($transaction->getSource() === RevolutReader::SOURCE && $money->isPositive())
                || ($transaction->getSource() === MobillsAppReader::SOURCE && $money->isNegative()))
            ) {
                $newTransactions[] = $transaction;
                continue;
            }

            $potentialTransferTransactions[] = $transaction;
        }

        /*  Let's iterate the list of transactions that have the potential to be transfer transactions.
            We iterate the list to find any income on Revolut. Then, we look ahead to see if there's a matching
            spend transaction in MobillsApp that's at most 4 days apart. If we find one, then the two transactions
            cancel each other out, and are left out of the final list. */

        while ($transaction = array_shift($potentialTransferTransactions)) {
            /** @var Transaction $transaction */
            $money = $transaction->getMoney();
            if ($transaction->getSource() === RevolutReader::SOURCE && $money->isPositive()) {
                $date = $transaction->getDateTime();
                $lastFoundMatchingIndex = null;
                for ($i = 0; $i < count(
                    $potentialTransferTransactions
                ) && $potentialTransferTransactions[$i]->getDateTime()->diff($date)->days <= 4; $i++) {
                    $lookaheadTransaction = $potentialTransferTransactions[$i];
                    $lookaheadMoney = $lookaheadTransaction->getMoney();
                    if ($lookaheadTransaction->getSource() === MobillsAppReader::SOURCE && $lookaheadMoney->isNegative(
                        ) && $lookaheadMoney->negative()->equals($money)) {
                        $lastFoundMatchingIndex = $i;
                    }
                }
                if ($lastFoundMatchingIndex !== null) {
                    array_splice($potentialTransferTransactions, $lastFoundMatchingIndex, 1);
                    continue;
                }
            }

            $newTransactions[] = $transaction;
        }

        return new TransactionHistory($newTransactions);
    }
}
