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

namespace CurrencyRate\Install;

use Configuration;
use CurrencyRate\Config\ModuleConfig;
use Db;
use Language;
use Module;
use Tab;

/**
 * Handles module installation and uninstallation
 */
class ModuleInstaller
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install(): bool
    {
        return $this->createTables()
            && $this->setDefaultConfiguration()
            && $this->registerHooks()
            && $this->installTab();
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        return $this->removeTables()
            && $this->removeConfiguration()
            && $this->uninstallTab();
    }

    /**
     * Create database tables
     *
     * @return bool
     */
    private function createTables(): bool
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . ModuleConfig::DB_TABLE . '` (
            `id_currency_rate` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `currency_code` VARCHAR(3) NOT NULL,
            `currency_name` VARCHAR(100) NOT NULL,
            `table_type` CHAR(1) NOT NULL,
            `rate_mid` DECIMAL(10, 4) NULL,
            `rate_bid` DECIMAL(10, 4) NULL,
            `rate_ask` DECIMAL(10, 4) NULL,
            `effective_date` DATE NOT NULL,
            `table_number` VARCHAR(20) NOT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_currency_rate`),
            UNIQUE KEY `unique_rate` (`currency_code`, `table_type`, `effective_date`),
            KEY `idx_currency_date` (`currency_code`, `effective_date`),
            KEY `idx_effective_date` (`effective_date`),
            KEY `idx_table_type` (`table_type`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove database tables
     *
     * @return bool
     */
    private function removeTables(): bool
    {
        $sql = [
            'DROP TABLE IF EXISTS `' . ModuleConfig::DB_TABLE . '`'
        ];

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set default configuration values
     *
     * Initializes all module configuration values with defaults.
     * Generates a secure random token for cron authentication.
     *
     * @return bool True if all configurations were set successfully
     */
    private function setDefaultConfiguration(): bool
    {
        $defaults = ModuleConfig::getDefaultValues();

        foreach ($defaults as $key => $value) {
            // Generate secure token for cron endpoint
            if ($key === ModuleConfig::CONFIG_KEY_CRON_TOKEN && empty($value)) {
                $value = $this->generateSecureToken();
            }

            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a cryptographically secure random token
     *
     * Uses random_bytes for secure token generation. Falls back to
     * md5(uniqid()) if random_bytes is unavailable.
     *
     * @param int $length Token length in bytes (default: 32)
     *
     * @return string The generated token as hexadecimal string
     */
    private function generateSecureToken(int $length = 32): string
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (\Exception $e) {
            // Fallback for environments where random_bytes might fail
            return md5(uniqid((string) mt_rand(), true));
        }
    }

    /**
     * Remove configuration values
     *
     * @return bool
     */
    private function removeConfiguration(): bool
    {
        $keys = ModuleConfig::getConfigKeys();

        foreach ($keys as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register module hooks
     *
     * @return bool
     */
    private function registerHooks(): bool
    {
        $hooks = [
            'displayHeader',
            'displayBackOfficeHeader',
            'displayProductAdditionalInfo',
            'moduleRoutes',
        ];

        foreach ($hooks as $hook) {
            if (!$this->module->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install admin tab
     *
     * @return bool
     */
    private function installTab(): bool
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminCurrencyRate';
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Currency Rates';
        }

        $parentTabId = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminTools"'
        );

        $tab->id_parent = $parentTabId > 0 ? $parentTabId : 0;
        $tab->module = $this->module->name;

        return $tab->add();
    }

    /**
     * Uninstall admin tab
     *
     * @return bool
     */
    private function uninstallTab(): bool
    {
        $idTab = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab`
            WHERE `class_name` = "AdminCurrencyRate" AND `module` = "' . pSQL($this->module->name) . '"'
        );

        if ($idTab) {
            $tab = new Tab($idTab);

            return $tab->delete();
        }

        return true;
    }
}
