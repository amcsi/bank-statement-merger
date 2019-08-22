<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Commands;

use amcsi\BankStatementMerger\Transaction\TransactionReader;
use Money\MoneyFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OutputTransactionsCommand extends Command
{
    public const NAME = 'output-transactions';

    private $transactionReader;
    private $formatter;

    public function __construct(TransactionReader $transactionReader, MoneyFormatter $formatter)
    {
        parent::__construct(self::NAME);
        $this->transactionReader = $transactionReader;
        $this->formatter = $formatter;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transactionHistory = $this->transactionReader->readTransactions();

        $table = new Table(new ConsoleOutput());
        $table->setStyle((new TableStyle())->setPadType(STR_PAD_LEFT));
        $table->setHeaders(['Date', 'Amount', 'Source']);

        foreach ($transactionHistory->getTransactions() as $transaction) {
            $table->addRow(
                [
                    $transaction->getDateTime()->format('Y-m-d'),
                    $this->formatter->format($transaction->getMoney()),
                    $transaction->getSource(),
                ]
            );
        }

        $table->render();
    }
}
