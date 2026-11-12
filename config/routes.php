<?php

/*
 * This file is part of the florentingarnier/cookie-consent-bundle package.
 *
 * (c) Florentin Garnier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use FlorentinGarnier\CookieConsentBundle\Controller\SaveConsentAction;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('florentin_garnier_cookie_consent_save', '/cookie-consent')
        ->controller(SaveConsentAction::class)
        ->methods(['POST'])
    ;
};
