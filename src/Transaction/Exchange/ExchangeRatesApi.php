<?php
declare(strict_types=1);

namespace amcsi\BankStatementMerger\Transaction\Exchange;

use Exchanger\Contract\ExchangeRate;
use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\HistoricalExchangeRateQuery;
use Exchanger\Service\HttpService;

class ExchangeRatesApi extends HttpService
{
    public const URL = 'https://api.exchangeratesapi.io/latest?base=';

    /**
     * Gets the exchange rate.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return ExchangeRate
     */
    public function getExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        $currencyPair = $exchangeQuery->getCurrencyPair();
        $baseCurrency = $currencyPair->getBaseCurrency();

        $json = json_decode($this->request(self::URL . rawurlencode($baseCurrency)), true);

        $rates = $json['rates'];

        return new \Exchanger\ExchangeRate(
            $currencyPair,
            $rates[$currencyPair->getQuoteCurrency()],
            new \DateTimeImmutable($json['date']),
            $this->getName()
        );
    }

    /**
     * Gets the name of the exchange rate service.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'exchangeratesapi';
    }

    /**
     * Tells if the service supports the exchange rate query.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return bool
     */
    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        return !$exchangeQuery instanceof HistoricalExchangeRateQuery;
    }
}
