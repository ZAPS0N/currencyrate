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

require_once __DIR__ . '/autoload.php';

use CurrencyRate\Config\DefaultCurrenciesConfig;
use CurrencyRate\Config\ModuleConfig;
use CurrencyRate\Form\ConfigurationFormBuilder;
use CurrencyRate\Hook\HookHandler;
use CurrencyRate\Install\ModuleInstaller;
use CurrencyRate\Service\CacheService;
use CurrencyRate\Service\CronService;
use CurrencyRate\Service\DataCleanupService;
use CurrencyRate\Service\NbpApiService;
use CurrencyRate\Service\ProductPriceConverter;
use CurrencyRate\Service\RateProcessor;

class CurrencyRate extends Module
{
    /**
     * @var ModuleInstaller
     */
    private $installer;

    /**
     * @var HookHandler
     */
    private $hookHandler;

    /**
     * @var NbpApiService
     */
    private $nbpApiService;

    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * @var CronService
     */
    private $cronService;

    /**
     * @var CurrencyRateRepository
     */
    private $repository;

    /**
     * @var ProductPriceConverter
     */
    private $priceConverter;

    /**
     * Module constructor
     */
    public function __construct()
    {
        $this->name = 'currencyrate';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Bartosz Żabicki';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Currency Rate', [], 'Modules.Currencyrate.Admin');
        $this->description = $this->trans(
            'Display current and historical currency exchange rates from NBP API',
            [],
            'Modules.Currencyrate.Admin'
        );

        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall this module?',
            [],
            'Modules.Currencyrate.Admin'
        );

        $this->initializeServices();
    }

    /**
     * Initialize module services
     *
     * @return void
     */
    private function initializeServices(): void
    {
        $this->repository = new CurrencyRateRepository();
        $this->cacheService = new CacheService($this->getCacheTtl());
        $this->nbpApiService = new NbpApiService($this->cacheService);

        $rateProcessor = new RateProcessor($this->repository);
        $cleanupService = new DataCleanupService($this->repository);

        $this->cronService = new CronService(
            $this->nbpApiService,
            $rateProcessor,
            $cleanupService,
            $this->cacheService
        );
        $this->priceConverter = new ProductPriceConverter($this->repository);
        $this->hookHandler = new HookHandler($this, $this->priceConverter);
        $this->installer = new ModuleInstaller($this);
    }

    /**
     * Module installation
     *
     * @return bool
     */
    public function install(): bool
    {
        return parent::install() && $this->installer->install();
    }

    /**
     * Module uninstallation
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        return $this->installer->uninstall() && parent::uninstall();
    }

    /**
     * Module configuration page
     *
     * @return string
     */
    public function getContent(): string
    {
        $output = '';

        if (Tools::getValue('reload_currencies') && Tools::getValue('new_table_type')) {
            $output .= $this->processTableTypeChange();
        }

        if (Tools::isSubmit('submitCurrencyRateConfig')) {
            $output .= $this->processConfiguration();
        }

        if (Tools::isSubmit('submitManualUpdate')) {
            $output .= $this->processManualUpdate();
        }

        $output .= $this->displayConfigurationForm();
        $output .= $this->displayUpdateInfo();

        return $output;
    }

    /**
     * Process table type change
     *
     * @return string
     */
    private function processTableTypeChange(): string
    {
        $oldTableType = Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE);
        $newTableType = Tools::getValue('new_table_type');

        if (!in_array($newTableType, ['A', 'B', 'C'])) {
            return '';
        }

        $tableTypeChanged = ($oldTableType !== $newTableType);

        Configuration::updateValue(ModuleConfig::CONFIG_KEY_TABLE_TYPE, $newTableType);

        $this->cacheService->delete('nbp_currencies_table_A');
        $this->cacheService->delete('nbp_currencies_table_B');
        $this->cacheService->delete('nbp_currencies_table_C');

        if ($tableTypeChanged && !empty($oldTableType)) {
            $this->repository->deleteByTableType($oldTableType);
            $this->cacheService->clearAll();

            Configuration::updateValue(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES, '');

            return $this->displayWarning(
                $this->trans(
                    'Table type changed from %s to %s. Old currency data has been removed. Please select currencies and update rates.',
                    [$oldTableType, $newTableType],
                    'Modules.Currencyrate.Admin'
                )
            );
        }

        return $this->displayConfirmation(
            $this->trans(
                'Table type set. Please review and save your currency selection.',
                [],
                'Modules.Currencyrate.Admin'
            )
        );
    }

    /**
     * Process configuration form submission
     *
     * @return string
     */
    private function processConfiguration(): string
    {
        $currencies = Tools::getValue('enabled_currencies');
        $tableType = Tools::getValue('table_type');
        $cacheTtl = (int) Tools::getValue('cache_ttl');
        $itemsPerPage = (int) Tools::getValue('items_per_page');
        $autoCleanup = (int) Tools::getValue('auto_cleanup');
        $cronToken = Tools::getValue('cron_token');

        if (empty($currencies) || !in_array($tableType, ['A', 'B', 'C'])) {
            return $this->displayError(
                $this->trans('Invalid configuration values', [], 'Modules.Currencyrate.Admin')
            );
        }

        if (empty($cronToken)) {
            try {
                $cronToken = $this->generateCronToken();
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    'Failed to generate cron token: ' . $e->getMessage(),
                    3
                );
                $cronToken = md5(uniqid((string) mt_rand(), true));
            }
        }

        Configuration::updateValue(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES, implode(',', $currencies));
        Configuration::updateValue(ModuleConfig::CONFIG_KEY_TABLE_TYPE, $tableType);
        Configuration::updateValue(ModuleConfig::CONFIG_KEY_CACHE_TTL, $cacheTtl);
        Configuration::updateValue(ModuleConfig::CONFIG_KEY_ITEMS_PER_PAGE, $itemsPerPage);
        Configuration::updateValue(ModuleConfig::CONFIG_KEY_AUTO_CLEANUP, $autoCleanup);
        Configuration::updateValue(ModuleConfig::CONFIG_KEY_CRON_TOKEN, $cronToken);

        $this->initializeServices();

        return $this->displayConfirmation(
            $this->trans('Settings updated successfully', [], 'Modules.Currencyrate.Admin')
        );
    }

    /**
     * Process manual update
     *
     * @return string
     */
    private function processManualUpdate(): string
    {
        try {
            $result = $this->cronService->updateRates();

            if ($result['success']) {
                return $this->displayConfirmation(
                    $this->trans('Currency rates updated successfully', [], 'Modules.Currencyrate.Admin')
                );
            }

            return $this->displayError(
                $this->trans('Error updating currency rates: ', [], 'Modules.Currencyrate.Admin')
                . $result['message']
            );
        } catch (Exception $e) {
            return $this->displayError(
                $this->trans('Error updating currency rates: ', [], 'Modules.Currencyrate.Admin')
                . $e->getMessage()
            );
        }
    }

    /**
     * Display configuration form
     *
     * @return string
     */
    private function displayConfigurationForm(): string
    {
        $availableCurrencies = $this->getAvailableCurrencies();
        $formBuilder = new ConfigurationFormBuilder($this, $availableCurrencies);

        return $formBuilder->build();
    }

    /**
     * Get available currencies from NBP
     *
     * @return array
     */
    private function getAvailableCurrencies(): array
    {
        $tableType = Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE) ?: ModuleConfig::DEFAULT_TABLE_TYPE;
        $cacheKey = 'nbp_currencies_table_' . $tableType;
        $cached = $this->cacheService->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        try {
            $currencies = $this->nbpApiService->getAvailableCurrencies($tableType);

            if (!empty($currencies)) {
                $formattedCurrencies = [];

                foreach ($currencies as $currency) {
                    $formattedCurrencies[] = [
                        'code' => $currency['code'],
                        'name' => $currency['code'] . ' - ' . $currency['name'],
                    ];
                }

                // Cache for 7 days (currencies don't change often)
                $this->cacheService->set($cacheKey, $formattedCurrencies, 604800);

                return $formattedCurrencies;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error fetching currencies from NBP: ' . $e->getMessage(), 3);
        }

        return DefaultCurrenciesConfig::getCurrencies($tableType);
    }

    /**
     * Display update info
     *
     * Renders the update information block showing last update time
     * and the cron URL with security token for scheduled updates.
     *
     * @return string Rendered template HTML
     */
    private function displayUpdateInfo(): string
    {
        $lastUpdate = Configuration::get(ModuleConfig::CONFIG_KEY_LAST_UPDATE);

        if (empty($lastUpdate)) {
            $message = $this->trans('No updates yet', [], 'Modules.Currencyrate.Admin');
        } else {
            $message = $this->trans('Last update: ', [], 'Modules.Currencyrate.Admin') . $lastUpdate;
        }

        $cronToken = Configuration::get(ModuleConfig::CONFIG_KEY_CRON_TOKEN);
        $cronParams = [];

        if (!empty($cronToken)) {
            $cronParams['token'] = $cronToken;
        }

        $this->context->smarty->assign([
            'last_update' => $message,
            'cron_url' => $this->context->link->getModuleLink($this->name, 'cron', $cronParams, true),
            'cron_token' => $cronToken,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/update_info.tpl');
    }

    /**
     * Get cache TTL
     *
     * @return int
     */
    private function getCacheTtl(): int
    {
        return (int) Configuration::get(ModuleConfig::CONFIG_KEY_CACHE_TTL) ?: ModuleConfig::DEFAULT_CACHE_TTL;
    }

    /**
     * Generate a secure random token for cron authentication
     *
     * Generates a cryptographically secure random token using random_bytes
     * and encodes it as a hexadecimal string.
     *
     * @param int $length Token length in bytes (default: 32 bytes = 64 hex characters)
     *
     * @return string The generated token as hexadecimal string
     *
     * @throws Exception If random_bytes fails to generate secure random data
     */
    private function generateCronToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hook: Display Header
     *
     * @param array $params
     *
     * @return void
     */
    public function hookDisplayHeader(array $params): void
    {
        $this->hookHandler->displayHeader($params);
    }

    /**
     * Hook: Display Back Office Header
     *
     * @param array $params
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader(array $params): void
    {
        $this->hookHandler->displayBackOfficeHeader($params);
    }

    /**
     * Hook: Display Product Additional Info
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        return $this->hookHandler->displayProductAdditionalInfo($params);
    }

    /**
     * Hook: Module Routes
     *
     * @param array $params
     *
     * @return array
     */
    public function hookModuleRoutes(array $params): array
    {
        return $this->hookHandler->moduleRoutes($params);
    }

    /**
     * Wrapper for protected trans function
     *
     * @param mixed $id
     * @param array $parameters
     * @param mixed $domain
     * @param mixed $locale
     *
     * @return string
     */
    public function _trans($id, $parameters = [], $domain = null, $locale = null): string
    {
        return $this->trans($id, $parameters, $domain, $locale);
    }

    // https://devdocs.prestashop-project.org/9/modules/creation/module-translation/new-system/
    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
