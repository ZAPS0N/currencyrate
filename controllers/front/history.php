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

use CurrencyRate\Service\HistoryService;

/**
 * Front controller for currency rates history page
 */
class CurrencyRateHistoryModuleFrontController extends ModuleFrontController
{
    /**
     * @var HistoryService
     */
    private $historyService;

    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->initializeContext();
        $this->initializeHistoryService();
    }

    /**
     * Initialize context if not set
     *
     * @return void
     */
    private function initializeContext(): void
    {
        if (!isset($this->context) || !$this->context) {
            $this->context = Context::getContext();
        }
    }

    /**
     * Initialize history service
     *
     * @return void
     */
    private function initializeHistoryService(): void
    {
        $repository = new CurrencyRateRepository();
        $this->historyService = new HistoryService($repository);
    }

    /**
     * Initialize content
     *
     * @return void
     */
    public function initContent(): void
    {
        parent::initContent();

        $params = $this->getRequestParams();
        $historyData = $this->historyService->getHistory($params);

        $this->assignTemplateVars($historyData);
        $this->setTemplate('module:currencyrate/views/templates/front/history.tpl');
    }

    /**
     * Get request parameters
     *
     * @return array Request parameters
     */
    private function getRequestParams(): array
    {
        return [
            'page' => (int) Tools::getValue('p', 1),
            'orderby' => Tools::getValue('orderby', 'effective_date'),
            'orderway' => Tools::getValue('orderway', 'desc'),
            'currency' => Tools::getValue('currency', ''),
            'search' => Tools::getValue('search', ''),
        ];
    }

    /**
     * Assign variables to template
     *
     * @param array $historyData History data from service
     *
     * @return void
     */
    private function assignTemplateVars(array $historyData): void
    {
        $this->context->smarty->assign([
            'rates' => $historyData['rates'],
            'current_page' => $historyData['pagination']['current_page'],
            'total_pages' => $historyData['pagination']['total_pages'],
            'total_rates' => $historyData['pagination']['total_rates'],
            'items_per_page' => $historyData['pagination']['items_per_page'],
            'order_by' => $historyData['filters']['order_by'],
            'order_way' => $historyData['filters']['order_way'],
            'currency_filter' => $historyData['filters']['currency_filter'],
            'search_query' => $historyData['filters']['search_query'],
            'available_currencies' => $historyData['available_currencies'],
            'table_type' => $historyData['table_type'],
        ]);
    }

    /**
     * Set media (CSS and JS)
     *
     * @return void
     */
    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheets();
        $this->registerJavascripts();
    }

    /**
     * Register stylesheets
     *
     * @return void
     */
    private function registerStylesheets(): void
    {
        $this->context->controller->registerStylesheet(
            'module-' . $this->module->name . '-css',
            $this->module->getPathUri() . 'views/css/front.css'
        );
    }

    /**
     * Register javascripts
     *
     * @return void
     */
    private function registerJavascripts(): void
    {
        $this->context->controller->registerJavascript(
            'module-' . $this->module->name . '-js',
            $this->module->getPathUri() . 'views/js/front.js'
        );
    }

    /**
     * Get breadcrumb links
     *
     * @return array Breadcrumb data
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->trans('Currency Rates History', [], 'Modules.Currencyrate.Shop'),
            'url' => $this->context->link->getModuleLink('currencyrate', 'history'),
        ];

        return $breadcrumb;
    }
}
