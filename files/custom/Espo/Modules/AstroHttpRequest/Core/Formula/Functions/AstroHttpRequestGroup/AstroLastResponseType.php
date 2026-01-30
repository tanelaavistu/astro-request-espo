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

class AstroLastResponseType extends BaseFunction implements Di\InjectableFactoryAware {
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args) {
        $httpClient = $this->injectableFactory->create(AstroHttpClient::class);

        $lastResponse = $httpClient->getLastResponse();

        if ($lastResponse === null) {
            return null;
        }

        if (count($args) > 0) {
            $field = $this->evaluate($args[0]);

            if (isset($lastResponse[$field])) {
                return $lastResponse[$field];
            }

            return null;
        }

        return $lastResponse;
    }
}
