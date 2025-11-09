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

use Configuration;
use CurrencyRate\Config\ModuleConfig;
use CurrencyRateRepository;

/**
 * Service for managing currency rates history
 */
class HistoryService
{
    /**
     * @var CurrencyRateRepository
     */
    private $repository;

    /**
     * @var array Allowed order by fields
     */
    private const ALLOWED_ORDER_BY = [
        'effective_date',
        'currency_code',
        'rate_mid',
        'rate_bid',
        'rate_ask',
        'table_type',
    ];

    /**
     * @var array Allowed order ways
     */
    private const ALLOWED_ORDER_WAY = ['asc', 'desc'];

    /**
     * @param CurrencyRateRepository $repository
     */
    public function __construct(CurrencyRateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated and filtered currency rates
     *
     * @param array $params Request parameters
     *
     * @return array History data with rates and pagination info
     */
    public function getHistory(array $params): array
    {
        $page = $this->validatePage($params['page'] ?? 1);
        $orderBy = $this->validateOrderBy($params['orderby'] ?? 'effective_date');
        $orderWay = $this->validateOrderWay($params['orderway'] ?? 'desc');
        $currencyFilter = $params['currency'] ?? '';
        $searchQuery = $params['search'] ?? '';

        $itemsPerPage = $this->getItemsPerPage();
        $filters = $this->buildFilters($currencyFilter, $searchQuery);

        $rates = $this->repository->getRates($filters, $page, $itemsPerPage, $orderBy, $orderWay);
        $totalRates = $this->repository->getTotalCount($filters);
        $totalPages = (int) ceil($totalRates / $itemsPerPage);

        $availableCurrencies = $this->repository->getDistinctCurrencies($filters['table_type']);

        return [
            'rates' => $rates,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_rates' => $totalRates,
                'items_per_page' => $itemsPerPage,
            ],
            'filters' => [
                'order_by' => $orderBy,
                'order_way' => $orderWay,
                'currency_filter' => $currencyFilter,
                'search_query' => $searchQuery,
            ],
            'available_currencies' => $availableCurrencies,
            'table_type' => $filters['table_type'],
        ];
    }

    /**
     * Build filters array for repository query
     *
     * @param string $currencyFilter Currency code filter
     * @param string $searchQuery Search query
     *
     * @return array Filters array
     */
    private function buildFilters(string $currencyFilter, string $searchQuery): array
    {
        $tableType = Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE);
        $enabledCurrencies = $this->getEnabledCurrencies();

        $filters = [
            'table_type' => $tableType,
        ];

        if (!empty($currencyFilter) && in_array($currencyFilter, $enabledCurrencies, true)) {
            $filters['currency_code'] = $currencyFilter;
        }

        if (!empty($searchQuery)) {
            $filters['search'] = $searchQuery;
        }

        return $filters;
    }

    /**
     * Get enabled currencies from configuration
     *
     * @return array Array of currency codes
     */
    private function getEnabledCurrencies(): array
    {
        $enabledCurrenciesStr = Configuration::get(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES);

        if (empty($enabledCurrenciesStr)) {
            return [];
        }

        return explode(',', $enabledCurrenciesStr);
    }

    /**
     * Get items per page from configuration
     *
     * @return int Items per page
     */
    private function getItemsPerPage(): int
    {
        return (int) Configuration::get(ModuleConfig::CONFIG_KEY_ITEMS_PER_PAGE) ?: ModuleConfig::DEFAULT_ITEMS_PER_PAGE;
    }

    /**
     * Validate page number
     *
     * @param mixed $page Page number
     *
     * @return int Valid page number (min 1)
     */
    private function validatePage($page): int
    {
        return max(1, (int) $page);
    }

    /**
     * Validate order by field
     *
     * @param string $orderBy Order by field
     *
     * @return string Valid order by field
     */
    private function validateOrderBy(string $orderBy): string
    {
        if (in_array($orderBy, self::ALLOWED_ORDER_BY, true)) {
            return $orderBy;
        }

        return 'effective_date';
    }

    /**
     * Validate order way
     *
     * @param string $orderWay Order way (asc/desc)
     *
     * @return string Valid order way
     */
    private function validateOrderWay(string $orderWay): string
    {
        $orderWay = strtolower($orderWay);

        if (in_array($orderWay, self::ALLOWED_ORDER_WAY, true)) {
            return $orderWay;
        }

        return 'desc';
    }
}
