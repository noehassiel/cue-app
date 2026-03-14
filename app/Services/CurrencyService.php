<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    private const FRANKFURTER_URL = 'https://api.frankfurter.app/latest';

    /**
     * Get exchange rate from one currency to another. Cached 24 hours.
     */
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "currency_rate:{$from}_{$to}";
        $cacheTtl = (int) config('app.currency_cache_ttl', 86400);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($from, $to) {
            $response = Http::get(self::FRANKFURTER_URL, [
                'from' => $from,
                'to' => $to,
            ]);

            if ($response->failed()) {
                return 1.0;
            }

            return (float) $response->json("rates.{$to}", 1.0);
        });
    }

    /**
     * Convert an amount from $fromCurrency to the workspace's base currency.
     */
    public function convertToBase(float $amount, string $fromCurrency, Workspace $workspace): string
    {
        $rate = $this->getRate($fromCurrency, $workspace->currency);

        return bcmul((string) $amount, (string) $rate, 4);
    }

    /**
     * Format an amount with its currency code.
     */
    public function formatAmount(string $amount, string $currency): string
    {
        return number_format((float) $amount, 2) . ' ' . $currency;
    }
}
