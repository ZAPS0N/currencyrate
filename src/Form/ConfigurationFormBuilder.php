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

namespace CurrencyRate\Form;

use Configuration;
use Context;
use CurrencyRate\Config\ModuleConfig;
use HelperForm;
use Module;
use Tools;

/**
 * Builds configuration form for the module
 */
class ConfigurationFormBuilder
{
    /**
     * @var Module
     */
    private Module $module;

    /**
     * @var array
     */
    private $availableCurrencies;


    /**
     * @param Module $module
     * @param array $availableCurrencies
     */
    public function __construct(Module $module, array $availableCurrencies = [])
    {
        $this->module = $module;
        $this->availableCurrencies = $availableCurrencies;
    }

    /**
     * Generate the configuration form
     *
     * @return string
     */
    public function build(): string
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->module->_trans('Settings', [], 'Modules.Currencyrate.Admin.Form'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $this->getFormInputs(),
                'submit' => [
                    'title' => $this->module->_trans('Save', [], 'Modules.Currencyrate.Admin.Form'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = $this->createHelper();
        $helper->fields_value = $this->getFieldsValues();

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Get form inputs definition
     *
     * @return array
     */
    private function getFormInputs(): array
    {
        return [
            [
                'type' => 'select',
                'label' => $this->module->_trans('Enabled Currencies', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'enabled_currencies[]',
                'multiple' => true,
                'class' => 'chosen',
                'options' => [
                    'query' => $this->availableCurrencies,
                    'id' => 'code',
                    'name' => 'name',
                ],
                'desc' => $this->module->_trans('Select currencies to track and display', [], 'Modules.Currencyrate.Admin.Form'),
            ],
            [
                'type' => 'select',
                'label' => $this->module->_trans('NBP Table Type', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'table_type',
                'options' => [
                    'query' => [
                        ['id' => 'A', 'name' => $this->module->_trans('Table A - Average rates', [], 'Modules.Currencyrate.Admin.Form')],
                        ['id' => 'B', 'name' => $this->module->_trans('Table B - Average rates (additional)', [], 'Modules.Currencyrate.Admin.Form')],
                        ['id' => 'C', 'name' => $this->module->_trans('Table C - Bid/Ask rates', [], 'Modules.Currencyrate.Admin.Form')],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
                'desc' => $this->module->_trans('Table A: Most common currencies | Table B: Additional currencies | Table C: Buy/sell rates', [], 'Modules.Currencyrate.Admin.Form'),
            ],
            [
                'type' => 'text',
                'label' => $this->module->_trans('Cache Time (seconds)', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'cache_ttl',
                'class' => 'fixed-width-lg',
                'desc' => $this->module->_trans('How long to cache API responses (3600 = 1 hour, 86400 = 1 day)', [], 'Modules.Currencyrate.Admin.Form'),
            ],
            [
                'type' => 'select',
                'label' => $this->module->_trans('Items Per Page', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'items_per_page',
                'options' => [
                    'query' => [
                        ['id' => '10', 'name' => '10'],
                        ['id' => '20', 'name' => '20'],
                        ['id' => '50', 'name' => '50'],
                        ['id' => '100', 'name' => '100'],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->module->_trans('Auto Cleanup Old Data', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'auto_cleanup',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'auto_cleanup_on',
                        'value' => 1,
                        'label' => $this->module->_trans('Yes', [], 'Modules.Currencyrate.Admin.Form'),
                    ],
                    [
                        'id' => 'auto_cleanup_off',
                        'value' => 0,
                        'label' => $this->module->_trans('No', [], 'Modules.Currencyrate.Admin.Form'),
                    ],
                ],
                'desc' => $this->module->_trans('Automatically delete rates older than 30 days', [], 'Modules.Currencyrate.Admin.Form'),
            ],
            [
                'type' => 'text',
                'label' => $this->module->_trans('Cron Security Token', [], 'Modules.Currencyrate.Admin.Form'),
                'name' => 'cron_token',
                'class' => 'cron-token fixed-width-xxl',
                'desc' => $this->module->_trans('Security token for cron endpoint. Leave empty to generate a new token automatically.', [], 'Modules.Currencyrate.Admin.Form'),
                'hint' => $this->module->_trans('Use this token in your cron URL to secure the endpoint from unauthorized access.', [], 'Modules.Currencyrate.Admin.Form'),
                'suffix' => '<button type="button" id="generateCronToken" class="btn btn-primary" style="margin-left: 10px;">'
                    . '<i class="icon-key"></i> '
                    . $this->module->_trans('Generate New Token', [], 'Modules.Currencyrate.Admin.Form')
                    . '</button>',
            ],
        ];
    }

    /**
     * Create and configure form helper
     *
     * @return HelperForm
     */
    private function createHelper(): HelperForm
    {
        $context = Context::getContext();

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->module = $this->module;
        $helper->default_form_language = $context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->submit_action = 'submitCurrencyRateConfig';
        $helper->currentIndex = $context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper;
    }

    /**
     * Get current field values
     *
     * @return array
     */
    private function getFieldsValues(): array
    {
        $enabledCurrenciesStr = Configuration::get(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES);
        $enabledCurrencies = !empty($enabledCurrenciesStr) ? explode(',', $enabledCurrenciesStr) : [];

        return [
            'enabled_currencies[]' => $enabledCurrencies,
            'table_type' => Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE),
            'cache_ttl' => Configuration::get(ModuleConfig::CONFIG_KEY_CACHE_TTL),
            'items_per_page' => Configuration::get(ModuleConfig::CONFIG_KEY_ITEMS_PER_PAGE),
            'auto_cleanup' => Configuration::get(ModuleConfig::CONFIG_KEY_AUTO_CLEANUP),
            'cron_token' => Configuration::get(ModuleConfig::CONFIG_KEY_CRON_TOKEN),
        ];
    }
}
