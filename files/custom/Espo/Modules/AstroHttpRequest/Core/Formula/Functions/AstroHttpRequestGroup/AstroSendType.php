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

namespace Espo\Modules\AstroHttpRequest\Core\Formula\Functions\AstroHttpRequestGroup;

use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Di;
use Espo\Modules\AstroHttpRequest\Services\AstroHttpClient;

class AstroSendType extends BaseFunction implements Di\InjectableFactoryAware {
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args) {
        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $url = $this->evaluate($args[0]);
        $method = count($args) > 1 ? $this->evaluate($args[1]) : 'GET';
        $data = count($args) > 2 ? $this->evaluate($args[2]) : [];
        $headers = count($args) > 3 ? $this->evaluate($args[3]) : [];

        if (!$url || !is_string($url)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$method || !is_string($method)) {
            $this->throwBadArgumentType(2, 'string');
        }

        if (!is_array($data) && !is_object($data)) {
            $this->throwBadArgumentType(3, 'object|array');
        }
        $data = $this->objectToArray($data);

        if (!is_array($headers) && !is_object($headers)) {
            $this->throwBadArgumentType(4, 'object|array');
        }
        $headers = $this->objectToArray($headers);

        $httpClient = $this->injectableFactory->create(AstroHttpClient::class);

        try {
            $success = $httpClient->request($method, $url, $data, $headers);

            if (!$success) {
                $lastResponse = $httpClient->getLastResponse();
                $statusCode = $lastResponse['statusCode'] ?? 0;
                $error = $lastResponse['error'] ?? 'Unknown error';

                if (method_exists($this, 'log')) {
                    $this->log(
                        "HTTP Request failed: {$method} {$url}. " .
                            "Status Code: {$statusCode}. Error: {$error}.",
                        'warning'
                    );
                }
            }

            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recursively convert stdClass objects to arrays
     */
    private function objectToArray($obj) {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }

        if (is_array($obj)) {
            return array_map([$this, 'objectToArray'], $obj);
        }

        return $obj;
    }
}
