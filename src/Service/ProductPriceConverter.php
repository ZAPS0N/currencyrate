<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    Bartosz Żabicki
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace CurrencyRate\Service;

use Currency;
use Tools;
use CurrencyRateRepository;

/**
 * Handles product price conversion to different currencies
 */
class ProductPriceConverter
{
    /**
     * @var object Repository instance (CurrencyRateRepository)
     */
    private CurrencyRateRepository $repository;

    /**
     * @param object $repository
     */
    public function __construct(CurrencyRateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Convert product price to multiple currencies
     *
     * @param array $product Product data
     * @param Currency $currentCurrency Current shop currency
     * @param Currency $defaultCurrency Default shop currency
     * @param array $enabledCurrencies List of currency codes to convert to
     * @param string $tableType NBP table type
     *
     * @return array Array of conversion results
     */
    public function convertProductPrice(
        array $product,
        Currency $currentCurrency,
        Currency $defaultCurrency,
        array $enabledCurrencies,
        string $tableType
    ): array {
        $priceKey = 'price';

        $productPrice = isset($product[$priceKey]) && $product[$priceKey] ? (float) str_replace(',', '.', $product[$priceKey]) : 0.0;

        if ($productPrice <= 0) {
            return [];
        }

        // Convert to default currency if needed
        $productPriceDefault = $this->convertToDefaultCurrency(
            $productPrice,
            $currentCurrency,
            $defaultCurrency
        );

        $rates = [];

        foreach ($enabledCurrencies as $currencyCode) {
            $currencyCode = trim($currencyCode);

            if (empty($currencyCode)) {
                continue;
            }

            $rate = $this->repository->getLatestRate($currencyCode, $tableType);

            if (!$rate) {
                continue;
            }

            $rateValue = $this->extractRateValue($rate);

            if ($rateValue <= 0) {
                continue;
            }

            // Convert default currency price to PLN if needed
            $pricePLN = $this->convertToPLN(
                $productPriceDefault,
                $defaultCurrency,
                $tableType
            );

            if ($pricePLN === null) {
                continue;
            }

            // Convert PLN to target currency
            $convertedPrice = round($pricePLN / $rateValue, 2);

            $rates[] = [
                'currency_code' => $currencyCode,
                'currency_name' => $rate['currency_name'],
                'rate' => $rateValue,
                'converted_price' => $convertedPrice,
            ];
        }

        return $rates;
    }

    /**
     * Convert product price to multiple currencies with pagination
     *
     * @param array $product Product data
     * @param Currency $currentCurrency Current shop currency
     * @param Currency $defaultCurrency Default shop currency
     * @param array $enabledCurrencies List of currency codes to convert to
     * @param string $tableType NBP table type
     * @param int $page Current page number
     * @param int $itemsPerPage Items per page
     *
     * @return array Array with 'rates', 'pagination', and 'total_rates'
     */
    public function convertProductPriceWithPagination(
        array $product,
        Currency $currentCurrency,
        Currency $defaultCurrency,
        array $enabledCurrencies,
        string $tableType,
        int $page = 1,
        int $itemsPerPage = 10
    ): array {
        // Get all rates
        $allRates = $this->convertProductPrice(
            $product,
            $currentCurrency,
            $defaultCurrency,
            $enabledCurrencies,
            $tableType
        );

        $totalRates = count($allRates);
        $totalPages = $totalRates > 0 ? (int) ceil($totalRates / $itemsPerPage) : 1;

        // Ensure page is within valid range
        $page = max(1, min($page, $totalPages));

        $offset = ($page - 1) * $itemsPerPage;
        $paginatedRates = array_slice($allRates, $offset, $itemsPerPage);

        return [
            'rates' => $paginatedRates,
            'all_rates' => $allRates, // Include all rates for client-side filtering
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'items_per_page' => $itemsPerPage,
            ],
            'total_rates' => $totalRates,
        ];
    }

    /**
     * Convert price to default currency
     *
     * @param float $price
     * @param Currency $currentCurrency
     * @param Currency $defaultCurrency
     *
     * @return float
     */
    private function convertToDefaultCurrency(
        float $price,
        Currency $currentCurrency,
        Currency $defaultCurrency
    ): float {
        if ($currentCurrency->id != $defaultCurrency->id) {
            return Tools::convertPrice($price, $currentCurrency, false);
        }

        return $price;
    }

    /**
     * Convert price to PLN
     *
     * @param float $price
     * @param Currency $currency
     * @param string $tableType
     *
     * @return float|null Returns null if conversion not possible
     */
    private function convertToPLN(float $price, Currency $currency, string $tableType): ?float
    {
        if ($currency->iso_code === 'PLN') {
            return $price;
        }

        $defaultCurrencyRate = $this->repository->getLatestRate($currency->iso_code, $tableType);

        if (!$defaultCurrencyRate) {
            return null;
        }

        $defaultCurrencyRateValue = $this->extractRateValue($defaultCurrencyRate);

        if ($defaultCurrencyRateValue <= 0) {
            return null;
        }

        return $price * $defaultCurrencyRateValue;
    }

    /**
     * Extract rate value from rate data
     *
     * @param array $rate
     *
     * @return float
     */
    private function extractRateValue(array $rate): float
    {
        if (!empty($rate['rate_mid'])) {
            return (float) $rate['rate_mid'];
        }

        if (!empty($rate['rate_bid']) && !empty($rate['rate_ask'])) {
            return ((float) $rate['rate_bid'] + (float) $rate['rate_ask']) / 2;
        }

        return 0.0;
    }
}
