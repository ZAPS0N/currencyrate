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

namespace CurrencyRate\Config;

/**
 * Module configuration constants
 */
class ModuleConfig
{
    public const DB_TABLE = _DB_PREFIX_ . 'currency_rate';

    public const CONFIG_KEY_ENABLED_CURRENCIES = 'CURRENCYRATE_ENABLED_CURRENCIES';
    public const CONFIG_KEY_TABLE_TYPE = 'CURRENCYRATE_TABLE_TYPE';
    public const CONFIG_KEY_CACHE_TTL = 'CURRENCYRATE_CACHE_TTL';
    public const CONFIG_KEY_ITEMS_PER_PAGE = 'CURRENCYRATE_ITEMS_PER_PAGE';
    public const CONFIG_KEY_LAST_UPDATE = 'CURRENCYRATE_LAST_UPDATE';
    public const CONFIG_KEY_AUTO_CLEANUP = 'CURRENCYRATE_AUTO_CLEANUP';
    public const CONFIG_KEY_CRON_TOKEN = 'CURRENCYRATE_CRON_TOKEN';

    public const DEFAULT_TABLE_TYPE = 'A';
    public const DEFAULT_CACHE_TTL = 86400; // 24 hours
    public const DEFAULT_ITEMS_PER_PAGE = 10;
    public const DEFAULT_CURRENCIES = 'EUR,USD,GBP,CHF';

    /**
     * Get all configuration keys
     *
     * @return array
     */
    public static function getConfigKeys(): array
    {
        return [
            self::CONFIG_KEY_ENABLED_CURRENCIES,
            self::CONFIG_KEY_TABLE_TYPE,
            self::CONFIG_KEY_CACHE_TTL,
            self::CONFIG_KEY_ITEMS_PER_PAGE,
            self::CONFIG_KEY_LAST_UPDATE,
            self::CONFIG_KEY_AUTO_CLEANUP,
            self::CONFIG_KEY_CRON_TOKEN,
        ];
    }

    /**
     * Get default configuration values
     *
     * @return array
     */
    public static function getDefaultValues(): array
    {
        return [
            self::CONFIG_KEY_ENABLED_CURRENCIES => self::DEFAULT_CURRENCIES,
            self::CONFIG_KEY_TABLE_TYPE => self::DEFAULT_TABLE_TYPE,
            self::CONFIG_KEY_CACHE_TTL => (string) self::DEFAULT_CACHE_TTL,
            self::CONFIG_KEY_ITEMS_PER_PAGE => (string) self::DEFAULT_ITEMS_PER_PAGE,
            self::CONFIG_KEY_LAST_UPDATE => '',
            self::CONFIG_KEY_AUTO_CLEANUP => '1',
            self::CONFIG_KEY_CRON_TOKEN => '',
        ];
    }
}
