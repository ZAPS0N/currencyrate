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

use CurrencyRate\Config\ModuleConfig;
use CurrencyRate\Service\CacheService;
use CurrencyRate\Service\CronService;
use CurrencyRate\Service\DataCleanupService;
use CurrencyRate\Service\NbpApiService;
use CurrencyRate\Service\RateProcessor;

/**
 * Front controller for cron job execution
 */
class CurrencyRateCronModuleFrontController extends ModuleFrontController
{
    /**
     * @var CronService
     */
    private $cronService;

    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->initializeCronService();
    }

    /**
     * Initialize cron service with dependencies
     *
     * Creates all required services with proper configuration.
     * Uses configured cache TTL from module settings.
     *
     * @return void
     */
    private function initializeCronService(): void
    {
        $repository = new CurrencyRateRepository();

        $cacheTtl = (int) Configuration::get(ModuleConfig::CONFIG_KEY_CACHE_TTL);

        if ($cacheTtl <= 0) {
            $cacheTtl = 86400; // Fallback to 24 hours if not configured
        }

        $cacheService = new CacheService($cacheTtl);
        $nbpApiService = new NbpApiService($cacheService);
        $rateProcessor = new RateProcessor($repository);
        $cleanupService = new DataCleanupService($repository);

        $this->cronService = new CronService(
            $nbpApiService,
            $rateProcessor,
            $cleanupService,
            $cacheService
        );
    }

    /**
     * Initialize content and execute cron job
     *
     * @return void
     */
    public function initContent(): void
    {
        if (!$this->validateToken()) {
            $this->renderJsonResponse([
                'success' => false,
                'message' => 'Invalid or missing token',
            ]);

            return;
        }

        try {
            $result = $this->cronService->updateRates();

            $this->renderJsonResponse($result);
        } catch (Exception $e) {
            $this->renderJsonResponse([
                'success' => false,
                'message' => 'Error executing cron: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate security token
     *
     * Compares the provided token with the configured cron security token.
     * Uses hash_equals for timing-attack-safe comparison.
     *
     * @return bool True if token is valid or not configured
     */
    private function validateToken(): bool
    {
        $token = Tools::getValue('token');
        $expectedToken = Configuration::get(ModuleConfig::CONFIG_KEY_CRON_TOKEN);

        // If no token is configured, allow access (for initial setup)
        if (empty($expectedToken)) {
            return true;
        }

        return !empty($token) && hash_equals($expectedToken, $token);
    }

    /**
     * Render JSON response
     *
     * @param array $data Response data
     *
     * @return void
     */
    private function renderJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        
        $this->ajaxRender(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Disable display header
     *
     * @return void
     */
    public function display(): void
    {
        // Don't display default template
    }
}
