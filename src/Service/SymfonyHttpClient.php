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

use Exception;
use PrestaShopLogger;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Symfony HTTP Client implementation (PrestaShop 8+)
 */
class SymfonyHttpClient implements HttpClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function get(string $url, array $headers = [], int $timeout = 10)
    {
        $client = HttpClient::create([
            'timeout' => $timeout,
            'headers' => array_merge([
                'Accept' => 'application/json',
            ], $headers),
        ]);

        $response = $client->request('GET', $url);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            PrestaShopLogger::addLog('HTTP Client: No data available for URL: ' . $url, 2);

            return false;
        }

        if ($statusCode !== 200) {
            throw new Exception('HTTP Error: ' . $statusCode);
        }

        $content = $response->getContent();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $data;
    }
}
