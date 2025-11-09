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
 * Default currencies configuration for fallback scenarios
 */
class DefaultCurrenciesConfig
{
    /**
     * Get default currencies by table type
     *
     * @param string $tableType NBP table type (A, B, or C)
     *
     * @return array
     */
    public static function getCurrencies(string $tableType = 'A'): array
    {
        $currencies = [
            'A' => self::getTableACurrencies(),
            'B' => self::getTableBCurrencies(),
            'C' => self::getTableCCurrencies(),
        ];

        return $currencies[$tableType] ?? $currencies['A'];
    }

    /**
     * Get Table A currencies (most common)
     *
     * @return array
     */
    private static function getTableACurrencies(): array
    {
        return [
            ['code' => 'THB', 'name' => 'THB - bat (Tajlandia)'],
            ['code' => 'USD', 'name' => 'USD - dolar amerykański'],
            ['code' => 'AUD', 'name' => 'AUD - dolar australijski'],
            ['code' => 'HKD', 'name' => 'HKD - dolar Hongkongu'],
            ['code' => 'CAD', 'name' => 'CAD - dolar kanadyjski'],
            ['code' => 'NZD', 'name' => 'NZD - dolar nowozelandzki'],
            ['code' => 'SGD', 'name' => 'SGD - dolar singapurski'],
            ['code' => 'EUR', 'name' => 'EUR - euro'],
            ['code' => 'HUF', 'name' => 'HUF - forint (Węgry)'],
            ['code' => 'CHF', 'name' => 'CHF - frank szwajcarski'],
            ['code' => 'GBP', 'name' => 'GBP - funt szterling'],
            ['code' => 'UAH', 'name' => 'UAH - hrywna (Ukraina)'],
            ['code' => 'JPY', 'name' => 'JPY - jen (Japonia)'],
            ['code' => 'CZK', 'name' => 'CZK - korona czeska'],
            ['code' => 'DKK', 'name' => 'DKK - korona duńska'],
            ['code' => 'ISK', 'name' => 'ISK - korona islandzka'],
            ['code' => 'NOK', 'name' => 'NOK - korona norweska'],
            ['code' => 'SEK', 'name' => 'SEK - korona szwedzka'],
            ['code' => 'RON', 'name' => 'RON - lej rumuński'],
            ['code' => 'BGN', 'name' => 'BGN - lew (Bułgaria)'],
            ['code' => 'TRY', 'name' => 'TRY - lira turecka'],
            ['code' => 'ILS', 'name' => 'ILS - nowy izraelski szekel'],
            ['code' => 'CLP', 'name' => 'CLP - peso chilijskie'],
            ['code' => 'PHP', 'name' => 'PHP - peso filipińskie'],
            ['code' => 'MXN', 'name' => 'MXN - peso meksykańskie'],
            ['code' => 'ZAR', 'name' => 'ZAR - rand (Republika Południowej Afryki)'],
            ['code' => 'BRL', 'name' => 'BRL - real (Brazylia)'],
            ['code' => 'MYR', 'name' => 'MYR - ringgit (Malezja)'],
            ['code' => 'IDR', 'name' => 'IDR - rupia indonezyjska'],
            ['code' => 'INR', 'name' => 'INR - rupia indyjska'],
            ['code' => 'KRW', 'name' => 'KRW - won południowokoreański'],
            ['code' => 'CNY', 'name' => 'CNY - yuan renminbi (Chiny)'],
            ['code' => 'XDR', 'name' => 'XDR - SDR (MFW)'],
        ];
    }

    /**
     * Get Table B currencies (additional)
     *
     * @return array
     */
    private static function getTableBCurrencies(): array
    {
        return [
            ['code' => 'AFN', 'name' => 'AFN - afgani (Afganistan)'],
            ['code' => 'MGA', 'name' => 'MGA - ariary (Madagaskar)'],
            ['code' => 'PAB', 'name' => 'PAB - balboa (Panama)'],
            ['code' => 'ETB', 'name' => 'ETB - birr etiopski'],
            ['code' => 'VES', 'name' => 'VES - boliwar soberano (Wenezuela)'],
            ['code' => 'BOB', 'name' => 'BOB - boliviano (Boliwia)'],
            ['code' => 'BRL', 'name' => 'BRL - real (Brazylia)'],
            ['code' => 'BND', 'name' => 'BND - dolar brunejski'],
            ['code' => 'FJD', 'name' => 'FJD - dolar Fidżi'],
            ['code' => 'XCD', 'name' => 'XCD - dolar wschodniokaraibski'],
            ['code' => 'AMD', 'name' => 'AMD - dram (Armenia)'],
            ['code' => 'CVE', 'name' => 'CVE - escudo Zielonego Przylądka'],
            ['code' => 'AWG', 'name' => 'AWG - florin arubański'],
            ['code' => 'GMD', 'name' => 'GMD - dalasi (Gambia)'],
            ['code' => 'GEL', 'name' => 'GEL - lari (Gruzja)'],
            ['code' => 'LBP', 'name' => 'LBP - funt libański'],
            ['code' => 'ALL', 'name' => 'ALL - lek (Albania)'],
            ['code' => 'HNL', 'name' => 'HNL - lempira (Honduras)'],
            ['code' => 'SLE', 'name' => 'SLE - leone (Sierra Leone)'],
            ['code' => 'MDL', 'name' => 'MDL - lej mołdawski'],
            ['code' => 'MKD', 'name' => 'MKD - denar (Macedonia Północna)'],
            ['code' => 'AZN', 'name' => 'AZN - manat azerbejdżański'],
            ['code' => 'TMT', 'name' => 'TMT - manat turkmeński'],
            ['code' => 'MZN', 'name' => 'MZN - metical (Mozambik)'],
            ['code' => 'NGN', 'name' => 'NGN - naira (Nigeria)'],
            ['code' => 'NAD', 'name' => 'NAD - dolar namibijski'],
            ['code' => 'TWD', 'name' => 'TWD - nowy dolar tajwański'],
            ['code' => 'PGK', 'name' => 'PGK - kina (Papua-Nowa Gwinea)'],
            ['code' => 'LAK', 'name' => 'LAK - kip (Laos)'],
            ['code' => 'MWK', 'name' => 'MWK - kwacha malawijska'],
            ['code' => 'ZMW', 'name' => 'ZMW - kwacha zambijska'],
            ['code' => 'AOA', 'name' => 'AOA - kwanza (Angola)'],
            ['code' => 'MMK', 'name' => 'MMK - kyat (Myanmar)'],
            ['code' => 'GHS', 'name' => 'GHS - cedi ghańskie'],
            ['code' => 'HTG', 'name' => 'HTG - gourde (Haiti)'],
            ['code' => 'PYG', 'name' => 'PYG - guarani (Paragwaj)'],
            ['code' => 'ANG', 'name' => 'ANG - gulden antylski'],
            ['code' => 'LSL', 'name' => 'LSL - loti (Lesotho)'],
            ['code' => 'SZL', 'name' => 'SZL - lilangeni (Eswatini)'],
            ['code' => 'MRU', 'name' => 'MRU - ouguiya (Mauretania)'],
        ];
    }

    /**
     * Get Table C currencies (bid/ask rates)
     *
     * @return array
     */
    private static function getTableCCurrencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'USD - dolar amerykański'],
            ['code' => 'AUD', 'name' => 'AUD - dolar australijski'],
            ['code' => 'CAD', 'name' => 'CAD - dolar kanadyjski'],
            ['code' => 'EUR', 'name' => 'EUR - euro'],
            ['code' => 'HUF', 'name' => 'HUF - forint (Węgry)'],
            ['code' => 'CHF', 'name' => 'CHF - frank szwajcarski'],
            ['code' => 'GBP', 'name' => 'GBP - funt szterling'],
            ['code' => 'JPY', 'name' => 'JPY - jen (Japonia)'],
            ['code' => 'CZK', 'name' => 'CZK - korona czeska'],
            ['code' => 'DKK', 'name' => 'DKK - korona duńska'],
            ['code' => 'NOK', 'name' => 'NOK - korona norweska'],
            ['code' => 'SEK', 'name' => 'SEK - korona szwedzka'],
            ['code' => 'XDR', 'name' => 'XDR - SDR (MFW)'],
        ];
    }
}
