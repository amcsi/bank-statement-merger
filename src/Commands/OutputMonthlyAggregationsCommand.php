<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Commands;

use amcsi\BankStatementMerger\Transaction\CurrencyFormatter;
use amcsi\BankStatementMerger\Transaction\TransactionReader;
use amcsi\BankStatementMerger\Transaction\TransactionStatisticsCalculator;
use Carbon\CarbonImmutable;
use Money\Converter;
use Money\Currency;
use Money\MoneyFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transactionHistory = $this->transactionReader->readTransactions();

        $currency = 'GBP';
        $transactionTotalCalculator = new TransactionStatisticsCalculator($this->converter, new Currency('GBP'));

        $monthlyAggregation = $transactionTotalCalculator->aggregateByMonth($transactionHistory);

        echo $this->formatter->format($transactionTotalCalculator->calculateTotalAmount($transactionHistory)) . "\n";

        $table = new Table(new ConsoleOutput());
        $table->setStyle((new TableStyle())->setPadType(STR_PAD_LEFT));
        $table->setHeaders(['Month', 'Balance', 'Difference', 'Income', 'Spend']);

        $balance = 0.0;
        foreach ($monthlyAggregation as $dateKey => $aggregate) {
            $total = $aggregate->getTotal();
            $balance += $total;
            $totalIncome = $aggregate->getIncome();
            $totalSpend = $aggregate->getSpend();
            $table->addRow(
                [
                    CarbonImmutable::createFromFormat('Y-m', $dateKey)->format('Y M'),
                    CurrencyFormatter::format($balance, $currency),
                    sprintf("%s % 9s %s", $total < 0 ? '-' : '+', number_format(abs($total), 2), $currency),
                    CurrencyFormatter::format($totalIncome, $currency),
                    CurrencyFormatter::format($totalSpend, $currency),
                ]
            );
        }

        $table->render();
    }
}
