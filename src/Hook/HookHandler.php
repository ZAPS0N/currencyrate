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

namespace CurrencyRate\Hook;

use Configuration;
use Context;
use Currency;
use CurrencyRate\Config\ModuleConfig;
use CurrencyRate\Service\ProductPriceConverter;
use Module;
use Tools;

/**
 * Handles all module hooks
 */
class HookHandler
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var ProductPriceConverter
     */
    private $priceConverter;

    /**
     * @param Module $module
     * @param ProductPriceConverter $priceConverter
     */
    public function __construct(Module $module, ProductPriceConverter $priceConverter)
    {
        $this->module = $module;
        $this->priceConverter = $priceConverter;
    }

    /**
     * Hook: Display Header
     *
     * @param array $params
     *
     * @return void
     */
    public function displayHeader(array $params): void
    {
        $context = Context::getContext();

        $context->controller->registerStylesheet(
            'module-' . $this->module->name . '-css',
            $this->module->getPathUri() . 'views/css/front.css'
        );

        $context->controller->registerJavascript(
            'module-' . $this->module->name . '-js',
            $this->module->getPathUri() . 'views/js/front.js'
        );
    }

    /**
     * Hook: Display Back Office Header
     *
     * @param array $params
     *
     * @return void
     */
    public function displayBackOfficeHeader(array $params): void
    {
        if (Tools::getValue('configure') === $this->module->name) {
            $context = Context::getContext();

            // We're using addCSS method instead of registerStylesheet method, because
            // addCSS is used in the AdminControllers and registerStylesheet is used in the front controllers
            $context->controller->addCSS(
                $this->module->getPathUri() . 'views/css/admin.css',
            );
            
            // Same situation with addJS
            $context->controller->addJS(
                $this->module->getPathUri() . 'views/js/admin.js',
            );
        }
    }

    /**
     * Hook: Display Product Additional Info
     *
     * @param array $params
     *
     * @return string
     */
    public function displayProductAdditionalInfo(array $params): string
    {
        $product = $params['product'];

        if (!isset($product['id_product'])) {
            return '';
        }

        // Convert ProductLazyArray to array for compatibility
        $productArray = is_array($product) ? $product : $this->convertProductToArray($product);

        // Always use price with taxes
        $priceKey = 'price';
        $productPrice = isset($productArray[$priceKey])
            ? (float) str_replace(',', '.', (string) $productArray[$priceKey])
            : 0.0;

        if ($productPrice <= 0) {
            return '';
        }

        $context = Context::getContext();
        $currentCurrency = $context->currency;
        $defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

        $enabledCurrenciesStr = Configuration::get(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES);
        $enabledCurrencies = explode(',', $enabledCurrenciesStr);
        $tableType = Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE);

        // Get pagination parameters
        $itemsPerPage = (int) Configuration::get(ModuleConfig::CONFIG_KEY_ITEMS_PER_PAGE) ?: 10;
        $page = 1; // Initial page, will be handled by JavaScript

        // Get rates with pagination
        $ratesData = $this->priceConverter->convertProductPriceWithPagination(
            $productArray,
            $currentCurrency,
            $defaultCurrency,
            $enabledCurrencies,
            $tableType,
            $page,
            $itemsPerPage
        );

        if (empty($ratesData['all_rates'])) {
            return '';
        }

        $context->smarty->assign([
            'rates' => $ratesData['all_rates'], // Pass all rates for client-side pagination
            'current_page' => $ratesData['pagination']['current_page'],
            'total_pages' => $ratesData['pagination']['total_pages'],
            'items_per_page' => $ratesData['pagination']['items_per_page'],
            'total_rates' => $ratesData['total_rates'],
            'product_price' => $productPrice,
            'product_price_formatted' => $context->currentLocale->formatPrice(
                $productPrice,
                $currentCurrency->iso_code
            ),
            'current_currency' => $currentCurrency->iso_code,
            'default_currency' => $defaultCurrency->iso_code,
        ]);

        return $this->module->display($this->module->getLocalPath(), 'views/templates/hook/product_rates.tpl');
    }

    /**
     * Convert ProductLazyArray to plain array
     *
     * @param mixed $product ProductLazyArray or array-like object
     *
     * @return array
     */
    private function convertProductToArray($product): array
    {
        if (is_array($product)) {
            return $product;
        }

        // If it implements ArrayAccess (like ProductLazyArray), convert it
        if ($product instanceof \ArrayAccess) {
            $result = [];

            foreach ($product as $key => $value) {
                $result[$key] = $value;
            }

            return $result;
        }

        // If it's an object with public properties, convert to array
        if (is_object($product)) {
            return (array) $product;
        }

        return [];
    }

    /**
     * Hook: Module Routes
     *
     * @param array $params
     *
     * @return array
     */
    public function moduleRoutes(array $params): array
    {
        return [
            'module-currencyrate-history' => [
                'controller' => 'history',
                'rule' => 'currency-rates/history',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'currencyrate',
                    'controller' => 'history',
                ],
            ],
            'module-currencyrate-cron' => [
                'controller' => 'cron',
                'rule' => 'currency-rates/cron',
                'keywords' => [
                    'token' => [
                        'regexp' => '[a-zA-Z0-9_-]+',
                        'param' => 'token',
                    ],
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'currencyrate',
                    'controller' => 'cron',
                ],
            ],
        ];
    }
}
