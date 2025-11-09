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

/**
 * Currency Rate Repository
 *
 * Handles database operations for currency exchange rates.
 * Provides methods for CRUD operations, filtering, and querying rate data.
 */
class CurrencyRateRepository
{
    /**
     * @var Db Database instance
     */
    private $db;

    /**
     * @var string Full table name with prefix
     */
    private $table;

    /**
     * Repository constructor
     *
     * Initializes database connection and sets up table name with prefix.
     */
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->table = ModuleConfig::DB_TABLE;
    }

    /**
     * Save or update currency rate
     *
     * Checks if a rate already exists for the given currency, table type, and date.
     * If it exists, updates the existing record; otherwise, inserts a new one.
     *
     * @param array<string, mixed> $data Currency rate data containing:
     *                                    - currency_code: string (required)
     *                                    - table_type: string (required)
     *                                    - effective_date: string (required)
     *                                    - currency_name: string
     *                                    - rate_mid, rate_bid, rate_ask: float|null
     *                                    - table_number: string
     *
     * @return bool True if operation succeeded, false otherwise
     */
    public function saveRate(array $data): bool
    {
        $existing = $this->findRate(
            $data['currency_code'],
            $data['table_type'],
            $data['effective_date']
        );

        if ($existing) {
            return $this->updateRate((int) $existing['id_currency_rate'], $data);
        }

        return $this->insertRate($data);
    }

    /**
     * Insert new rate
     *
     * Adds timestamps (date_add, date_upd) to the data and inserts
     * a new record into the database.
     *
     * @param array<string, mixed> $data Currency rate data
     *
     * @return bool True if insert succeeded, false otherwise
     */
    private function insertRate(array $data): bool
    {
        $data['date_add'] = date('Y-m-d H:i:s');
        $data['date_upd'] = date('Y-m-d H:i:s');

        return $this->db->insert('currency_rate', $data);
    }

    /**
     * Update existing rate
     *
     * Updates the date_upd timestamp and modifies an existing record.
     *
     * @param int $id Currency rate ID
     * @param array<string, mixed> $data Updated currency rate data
     *
     * @return bool True if update succeeded, false otherwise
     */
    private function updateRate(int $id, array $data): bool
    {
        $data['date_upd'] = date('Y-m-d H:i:s');

        return $this->db->update(
            'currency_rate',
            $data,
            'id_currency_rate = ' . (int) $id
        );
    }

    /**
     * Find rate by currency, table type and date
     *
     * Searches for a specific exchange rate record matching all three criteria.
     * Uses a unique constraint on these three fields.
     *
     * @param string $currencyCode Three-letter currency code (e.g., "USD")
     * @param string $tableType NBP table type ("A", "B", or "C")
     * @param string $effectiveDate Effective date in Y-m-d format
     *
     * @return array<string, mixed>|false Rate data as associative array, or false if not found
     */
    public function findRate(string $currencyCode, string $tableType, string $effectiveDate)
    {
        $sql = 'SELECT * FROM `' . $this->table . '`
                WHERE `currency_code` = \'' . pSQL($currencyCode) . '\'
                AND `table_type` = \'' . pSQL($tableType) . '\'
                AND `effective_date` = \'' . pSQL($effectiveDate) . '\'';

        return $this->db->getRow($sql);
    }

    /**
     * Get latest rate for currency
     *
     * Retrieves the most recent exchange rate for a specific currency and table type,
     * ordered by effective date in descending order.
     *
     * @param string $currencyCode Three-letter currency code (e.g., "EUR")
     * @param string $tableType NBP table type ("A", "B", or "C")
     *
     * @return array<string, mixed>|false Latest rate data, or false if no rate exists
     */
    public function getLatestRate(string $currencyCode, string $tableType)
    {
        $sql = 'SELECT * FROM `' . $this->table . '`
                WHERE `currency_code` = \'' . pSQL($currencyCode) . '\'
                AND `table_type` = \'' . pSQL($tableType) . '\'
                ORDER BY `effective_date` DESC';

        return $this->db->getRow($sql);
    }

    /**
     * Get rates with pagination and sorting
     *
     * Retrieves currency rates with support for filtering, pagination, and custom sorting.
     * Validates orderBy column and orderWay to prevent SQL injection.
     *
     * @param array<string, mixed> $filters Optional filters (currency_code, table_type, date_from, date_to, search)
     * @param int $page Current page number (starts at 1)
     * @param int $limit Number of records per page
     * @param string $orderBy Column name to sort by (must be in allowed list)
     * @param string $orderWay Sort direction ("ASC" or "DESC")
     *
     * @return array<int, array<string, mixed>> Array of rate records
     */
    public function getRates(
        array $filters = [],
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'effective_date',
        string $orderWay = 'DESC'
    ): array {
        $offset = ($page - 1) * $limit;
        $where = $this->buildWhereClause($filters);

        // Validate order by and way to prevent SQL injection
        $allowedOrderBy = ['effective_date', 'currency_code', 'rate_mid', 'rate_bid', 'rate_ask', 'table_type'];

        $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'effective_date';
        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC';

        $sql = 'SELECT * FROM `' . $this->table . '`'
            . ($where ? ' WHERE ' . $where : '')
            . ' ORDER BY `' . pSQL($orderBy) . '` ' . $orderWay
            . ' LIMIT ' . (int) $offset . ', ' . (int) $limit;

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Get total count of rates
     *
     * Returns the total number of currency rate records matching the given filters.
     * Useful for pagination calculations.
     *
     * @param array<string, mixed> $filters Optional filters (same as getRates())
     *
     * @return int Total number of matching records
     */
    public function getTotalCount(array $filters = []): int
    {
        $where = $this->buildWhereClause($filters);

        $sql = 'SELECT COUNT(*) FROM `' . $this->table . '`'
            . ($where ? ' WHERE ' . $where : '');

        return (int) $this->db->getValue($sql);
    }

    /**
     * Build WHERE clause from filters
     *
     * Constructs a SQL WHERE clause string from an array of filter conditions.
     * All values are sanitized using pSQL() to prevent SQL injection.
     *
     * @param array<string, mixed> $filters Associative array of filter conditions:
     *                                      - currency_code: string (exact match)
     *                                      - table_type: string (exact match)
     *                                      - date_from: string (greater than or equal)
     *                                      - date_to: string (less than or equal)
     *                                      - search: string (LIKE search on code and name)
     *
     * @return string WHERE clause conditions joined with AND (without "WHERE" keyword)
     */
    private function buildWhereClause(array $filters): string
    {
        $conditions = [];

        if (!empty($filters['currency_code'])) {
            $conditions[] = '`currency_code` = \'' . pSQL($filters['currency_code']) . '\'';
        }

        if (!empty($filters['table_type'])) {
            $conditions[] = '`table_type` = \'' . pSQL($filters['table_type']) . '\'';
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = '`effective_date` >= \'' . pSQL($filters['date_from']) . '\'';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = '`effective_date` <= \'' . pSQL($filters['date_to']) . '\'';
        }

        if (!empty($filters['search'])) {
            $search = pSQL($filters['search']);
            $conditions[] = '(`currency_code` LIKE \'%' . $search . '%\''
                . ' OR `currency_name` LIKE \'%' . $search . '%\')';
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Delete rates older than specified date
     *
     * Removes all currency rate records with an effective date before
     * the specified date. Useful for data cleanup and maintenance.
     *
     * @param string $date Date threshold in Y-m-d format (records before this date will be deleted)
     *
     * @return bool True if deletion succeeded, false otherwise
     */
    public function deleteOlderThan(string $date): bool
    {
        return $this->db->delete(
            'currency_rate',
            '`effective_date` < \'' . pSQL($date) . '\''
        );
    }

    /**
     * Get all distinct currency codes
     *
     * Returns a list of unique currency codes and their names from the database.
     * Optionally filters by table type.
     *
     * @param string|null $tableType Optional NBP table type filter ("A", "B", or "C")
     *
     * @return array<int, array<string, string>> Array of arrays containing 'currency_code' and 'currency_name'
     */
    public function getDistinctCurrencies(?string $tableType = null): array
    {
        $sql = 'SELECT DISTINCT `currency_code`, `currency_name`
                FROM `' . $this->table . '`';

        if ($tableType !== null) {
            $sql .= ' WHERE `table_type` = \'' . pSQL($tableType) . '\'';
        }

        $sql .= ' ORDER BY `currency_code` ASC';

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Get rates for specific currency and date range
     *
     * Retrieves all exchange rates for a given currency within a date range,
     * ordered chronologically from oldest to newest.
     *
     * @param string $currencyCode Three-letter currency code (e.g., "GBP")
     * @param string $tableType NBP table type ("A", "B", or "C")
     * @param string $dateFrom Start date in Y-m-d format (inclusive)
     * @param string $dateTo End date in Y-m-d format (inclusive)
     *
     * @return array<int, array<string, mixed>> Array of rate records
     */
    public function getRatesForPeriod(
        string $currencyCode,
        string $tableType,
        string $dateFrom,
        string $dateTo
    ): array {
        $sql = 'SELECT * FROM `' . $this->table . '`
                WHERE `currency_code` = \'' . pSQL($currencyCode) . '\'
                AND `table_type` = \'' . pSQL($tableType) . '\'
                AND `effective_date` BETWEEN \'' . pSQL($dateFrom) . '\' AND \'' . pSQL($dateTo) . '\'
                ORDER BY `effective_date` ASC';

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Delete all rates for specific table type
     *
     * Removes all currency rate records for a given NBP table type.
     * Useful when switching between table types (A, B, or C).
     *
     * @param string $tableType NBP table type to delete ("A", "B", or "C")
     *
     * @return bool True if deletion succeeded, false otherwise
     */
    public function deleteByTableType(string $tableType): bool
    {
        return $this->db->delete(
            'currency_rate',
            '`table_type` = \'' . pSQL($tableType) . '\''
        );
    }
}
