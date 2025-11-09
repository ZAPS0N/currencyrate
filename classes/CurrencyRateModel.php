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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Currency Rate Model
 *
 * Represents a currency exchange rate record from the NBP (National Bank of Poland) API.
 * Stores historical and current exchange rates for different currencies and table types.
 */
class CurrencyRateModel extends ObjectModel
{
    /**
     * @var int Currency rate ID
     */
    public $id_currency_rate;

    /**
     * @var string Currency code (ISO 4217 - 3 letters, e.g., USD, EUR)
     */
    public $currency_code;

    /**
     * @var string Full currency name
     */
    public $currency_name;

    /**
     * @var string NBP table type (A, B, or C)
     */
    public $table_type;

    /**
     * @var float|null Mid exchange rate (for tables A and B)
     */
    public $rate_mid;

    /**
     * @var float|null Bid exchange rate (for table C)
     */
    public $rate_bid;

    /**
     * @var float|null Ask exchange rate (for table C)
     */
    public $rate_ask;

    /**
     * @var string Effective date of the exchange rate
     */
    public $effective_date;

    /**
     * @var string NBP table number (e.g., "001/A/NBP/2024")
     */
    public $table_number;

    /**
     * @var string Date when record was created
     */
    public $date_add;

    /**
     * @var string Date when record was last updated
     */
    public $date_upd;

    /**
     * @var array<string, mixed> Object model definition
     */
    public static $definition = [
        'table' => 'currency_rate',
        'primary' => 'id_currency_rate',
        'fields' => [
            'currency_code' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 3,
            ],
            'currency_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 100,
            ],
            'table_type' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 1,
            ],
            'rate_mid' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false,
            ],
            'rate_bid' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false,
            ],
            'rate_ask' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false,
            ],
            'effective_date' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ],
            'table_number' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 20,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
        ],
    ];

    /**
     * Get formatted rate value
     *
     * Returns the exchange rate based on available data:
     * - For tables A and B: returns the mid rate
     * - For table C: calculates average of bid and ask rates
     * - Returns 0.0 if no rates are available
     *
     * @return float The exchange rate value
     */
    public function getRate(): float
    {
        if ($this->rate_mid !== null) {
            return (float) $this->rate_mid;
        }

        if ($this->rate_bid !== null && $this->rate_ask !== null) {
            return ((float) $this->rate_bid + (float) $this->rate_ask) / 2;
        }

        return 0.0;
    }

    /**
     * Get formatted date
     *
     * Converts the effective date to Y-m-d format.
     * This method ensures consistent date formatting across the application.
     *
     * @return string The formatted date in Y-m-d format (e.g., "2024-01-15")
     */
    public function getFormattedDate(): string
    {
        return date('Y-m-d', strtotime($this->effective_date));
    }
}