<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction;

class CurrencyConverter
{
    private $rates;
    private $currency;

    public function __construct(string $currency)
    {
        $this->rates = json_decode(
            file_get_contents("https://api.exchangeratesapi.io/latest?base=$currency"),
            true
        )['rates'];
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Converts the given amount-currency to the amount of currency provided in the constructor.
     * @param float $amount
     * @param string $currency
     * @return float
     */
    public function convertAmount(float $amount, string $currency): float
    {
        if ($currency === $this->currency) {
            return $amount;
        }

        return $amount / $this->rates[$currency];
    }
}
