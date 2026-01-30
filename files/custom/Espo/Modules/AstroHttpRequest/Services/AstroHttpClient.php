<?php

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 * 
 * AstroHttpRequest - Open Source HTTP Request Module for EspoCRM.
 * Copyright (C) 2026 Tanel Aavistu
 ************************************************************************/

namespace Espo\Modules\AstroHttpRequest\Services;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;

class AstroHttpClient {
    private const CONNECT_TIMEOUT = 5;
    private const TIMEOUT = 10;

    private static ?array $lastResponse = null;

    public function __construct(private Config $config) {
    }

    /**
     * @param array<int, mixed> $dataList
     * @throws Error
     */
    public function request(string $method, string $url, ?array $data = null, ?array $headers = []): bool {
        $connectTimeout = $this->config->get('httpRequestConnectTimeout', self::CONNECT_TIMEOUT);
        $timeout = $this->config->get('httpRequestTimeout', self::TIMEOUT);

        $headerList = [];
        foreach ($headers as $key => $value) {
            $headerList[] = $key . ': ' . $value;
        }

        $payload = null;
        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $payload = Json::encode($data);

            if (!isset($headers['Content-Type'])) {
                $headerList[] = 'Content-Type: application/json';
            }
            $headerList[] = 'Content-Length: ' . strlen($payload);
        }

        $handler = curl_init($url);

        if ($handler === false) {
            throw new Error("Could not init CURL for URL {$url}.");
        }

        curl_setopt($handler, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handler, \CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handler, \CURLOPT_HEADER, true);
        curl_setopt($handler, \CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($handler, \CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($handler, \CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handler, \CURLOPT_PROTOCOLS, \CURLPROTO_HTTPS | \CURLPROTO_HTTP);
        curl_setopt($handler, \CURLOPT_REDIR_PROTOCOLS, \CURLPROTO_HTTPS);
        curl_setopt($handler, \CURLOPT_HTTPHEADER, $headerList);

        if ($payload !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($handler, \CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($handler);

        $headerSize = curl_getinfo($handler, \CURLINFO_HEADER_SIZE);
        $responseBody = is_string($response) ? substr($response, $headerSize) : '';
        $responseHeaders = is_string($response) ? substr($response, 0, $headerSize) : '';

        $code = curl_getinfo($handler, \CURLINFO_HTTP_CODE);

        if (!is_numeric($code)) {
            $code = 0;
        }

        if (!is_int($code)) {
            $code = intval($code);
        }

        $errorNumber = curl_errno($handler);
        $errorMessage = curl_error($handler);

        if (
            $errorNumber &&
            in_array($errorNumber, [\CURLE_OPERATION_TIMEDOUT, \CURLE_OPERATION_TIMEOUTED])
        ) {
            $code = 408;
        }

        curl_close($handler);

        $success = $code >= 200 && $code < 300;

        $decodedBody = null;
        if ($responseBody) {
            $decoded = json_decode($responseBody, true);
            $decodedBody = $decoded !== null ? $decoded : $responseBody;
        }

        self::$lastResponse = [
            'statusCode' => $code,
            'body' => $decodedBody,
            'rawBody' => $responseBody,
            'headers' => $this->parseHeaders($responseHeaders),
            'success' => $success,
            'error' => $errorNumber ? $errorMessage : null,
        ];

        return $success;
    }

    public function getLastResponse(): ?array {
        return self::$lastResponse;
    }

    private function parseHeaders(string $headerString): array {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }
}
