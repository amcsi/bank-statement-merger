<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class CurrencyFormatter
{
    public static function format(float $amount, string $currency): string
    {
        return sprintf('%s %s', number_format($amount, 2), $currency);
    }
}
