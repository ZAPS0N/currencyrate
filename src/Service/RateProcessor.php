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

use CurrencyRateRepository;
use Exception;
use PrestaShopLogger;

/**
 * Processes and saves currency rates from API responses
 */
class RateProcessor
{
    /**
     * @var CurrencyRateRepository
     */
    private $repository;

    /**
     * @param CurrencyRateRepository $repository
     */
    public function __construct(CurrencyRateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Process API response and save rates to database
     *
     * Validates each rate record before saving. Invalid records are skipped
     * and logged, but processing continues for remaining valid records.
     *
     * @param array|false $apiResponse API response data
     * @param string $tableType NBP table type (A, B, or C)
     *
     * @return int Number of successfully saved records
     */
    public function processAndSaveRates($apiResponse, string $tableType): int
    {
        if ($apiResponse === false || !$this->isValidResponse($apiResponse)) {
            PrestaShopLogger::addLog(
                'Invalid API response structure received',
                3
            );

            return 0;
        }

        $savedCount = 0;
        $errorCount = 0;
        $currencyCode = $apiResponse['code'];
        $currencyName = $apiResponse['currency'];
        $totalRates = count($apiResponse['rates']);

        foreach ($apiResponse['rates'] as $rate) {
            try {
                // Validate and prepare rate data
                $data = $this->prepareRateData($currencyCode, $currencyName, $rate, $tableType);

                // Save to database
                if ($this->repository->saveRate($data)) {
                    ++$savedCount;
                } else {
                    ++$errorCount;
                    PrestaShopLogger::addLog(
                        sprintf(
                            'Failed to save rate for %s on %s (database error)',
                            $currencyCode,
                            $rate['effectiveDate'] ?? 'unknown date'
                        ),
                        3
                    );
                }
            } catch (Exception $e) {
                ++$errorCount;
                $this->logValidationError($currencyCode, $rate, $e);
            }
        }

        // Log summary if there were any errors
        if ($errorCount > 0) {
            PrestaShopLogger::addLog(
                sprintf(
                    'Rate processing completed for %s: %d/%d saved, %d failed validation',
                    $currencyCode,
                    $savedCount,
                    $totalRates,
                    $errorCount
                ),
                2
            );
        }

        return $savedCount;
    }

    /**
     * Validate API response structure
     *
     * @param mixed $response
     *
     * @return bool
     */
    private function isValidResponse($response): bool
    {
        return is_array($response)
            && isset($response['rates'])
            && is_array($response['rates'])
            && isset($response['code'])
            && isset($response['currency']);
    }

    /**
     * Prepare rate data for database insertion
     *
     * Validates all data before preparing for database insertion.
     * Throws exception if any validation fails.
     *
     * @param string $currencyCode Currency code
     * @param string $currencyName Currency name
     * @param array $rate Rate data from API
     * @param string $tableType Table type
     *
     * @return array Prepared data array
     *
     * @throws Exception If validation fails
     */
    private function prepareRateData(
        string $currencyCode,
        string $currencyName,
        array $rate,
        string $tableType
    ): array {
        // Validate currency code format
        if (!$this->isValidCurrencyCode($currencyCode)) {
            throw new Exception(
                sprintf('Invalid currency code format: %s (expected 3 uppercase letters)', $currencyCode)
            );
        }

        // Validate effective date
        if (!isset($rate['effectiveDate']) || !$this->isValidDate($rate['effectiveDate'])) {
            throw new Exception(
                sprintf('Invalid or missing effective date for %s', $currencyCode)
            );
        }

        $data = [
            'currency_code' => $currencyCode,
            'currency_name' => $currencyName,
            'table_type' => $tableType,
            'effective_date' => $rate['effectiveDate'],
            'table_number' => $rate['no'] ?? '',
        ];

        if ($tableType === 'C') {
            // Table C has bid and ask prices
            $bid = $rate['bid'] ?? null;
            $ask = $rate['ask'] ?? null;

            // Validate bid and ask rates
            if ($bid !== null && !$this->isValidRate($bid)) {
                throw new Exception(
                    sprintf('Invalid bid rate for %s: %s', $currencyCode, $bid)
                );
            }

            if ($ask !== null && !$this->isValidRate($ask)) {
                throw new Exception(
                    sprintf('Invalid ask rate for %s: %s', $currencyCode, $ask)
                );
            }

            $data['rate_bid'] = $bid;
            $data['rate_ask'] = $ask;
            $data['rate_mid'] = null;
        } else {
            // Tables A and B have mid rate
            $mid = $rate['mid'] ?? null;

            // Validate mid rate
            if ($mid !== null && !$this->isValidRate($mid)) {
                throw new Exception(
                    sprintf('Invalid mid rate for %s: %s', $currencyCode, $mid)
                );
            }

            $data['rate_mid'] = $mid;
            $data['rate_bid'] = null;
            $data['rate_ask'] = null;
        }

        return $data;
    }

    /**
     * Validate currency code format
     *
     * Checks if currency code is exactly 3 uppercase letters (ISO 4217 standard).
     *
     * @param string $code Currency code to validate
     *
     * @return bool True if valid, false otherwise
     */
    private function isValidCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }

    /**
     * Validate exchange rate value
     *
     * Checks if rate is a positive number (greater than 0).
     * Rates cannot be negative or zero in real-world scenarios.
     *
     * @param mixed $rate Rate value to validate
     *
     * @return bool True if valid, false otherwise
     */
    private function isValidRate($rate): bool
    {
        return is_numeric($rate) && (float) $rate > 0;
    }

    /**
     * Validate date format and value
     *
     * Checks if date is in Y-m-d format and not in the future.
     * Exchange rates should only exist for past or current dates.
     *
     * @param string $date Date string to validate
     *
     * @return bool True if valid, false otherwise
     */
    private function isValidDate(string $date): bool
    {
        // Check format (Y-m-d)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        // Parse date
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        // Verify the date components are valid (e.g., not 2024-13-45)
        $parts = explode('-', $date);
        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return false;
        }

        // Check if date is not in the future (allow today + 1 day buffer for timezone differences)
        $tomorrow = strtotime('+1 day');
        if ($timestamp > $tomorrow) {
            return false;
        }

        return true;
    }

    /**
     * Log validation error with detailed information
     *
     * Logs validation errors with context about the specific rate that failed,
     * including the date and error message for easier debugging.
     *
     * @param string $currencyCode Currency code
     * @param array $rate Rate data that failed validation
     * @param Exception $exception Exception instance with error details
     *
     * @return void
     */
    private function logValidationError(string $currencyCode, array $rate, Exception $exception): void
    {
        $date = $rate['effectiveDate'] ?? 'unknown';
        $rateValue = $rate['mid'] ?? $rate['bid'] ?? $rate['ask'] ?? 'N/A';

        PrestaShopLogger::addLog(
            sprintf(
                'Validation failed for %s on %s (rate: %s): %s',
                $currencyCode,
                $date,
                $rateValue,
                $exception->getMessage()
            ),
            3
        );
    }
}
