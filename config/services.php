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

use FlorentinGarnier\CookieConsent\ConsentCookie;
use FlorentinGarnier\CookieConsent\ConsentPolicy;
use FlorentinGarnier\CookieConsent\ConsentRecordRepositoryInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\NoChannelCodeProvider;
use FlorentinGarnier\CookieConsentBundle\Command\PurgeConsentRecordsCommand;
use FlorentinGarnier\CookieConsentBundle\Controller\SaveConsentAction;
use FlorentinGarnier\CookieConsentBundle\Doctrine\ConsentRecordRepository;
use FlorentinGarnier\CookieConsentBundle\Twig\CookieConsentExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ConsentPolicy::class)
        ->factory([ConsentPolicy::class, 'fromConfiguration'])
        ->args([param('florentin_garnier_cookie_consent.policy_version'), param('florentin_garnier_cookie_consent.categories')])
    ;

    $services->set(ConsentCookie::class)->args([
        service(ConsentPolicy::class),
        param('florentin_garnier_cookie_consent.cookie.name'),
        param('florentin_garnier_cookie_consent.cookie.lifetime'),
        param('florentin_garnier_cookie_consent.cookie.domain'),
    ]);

    $services->set(ConsentRecordRepository::class)
        ->args([service('doctrine'), param('florentin_garnier_cookie_consent.records.class')])
    ;
    $services->alias(ConsentRecordRepositoryInterface::class, ConsentRecordRepository::class);

    $services->set(NoChannelCodeProvider::class);

    $services->set(SaveConsentAction::class)
        ->args([
            service(ConsentPolicy::class),
            service(ConsentCookie::class),
            service(ConsentRecordRepositoryInterface::class),
            service(ChannelCodeProviderInterface::class),
        ])
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->set(CookieConsentExtension::class)
        ->args([
            service(ConsentPolicy::class),
            service(ConsentCookie::class),
            service('request_stack'),
            service('router'),
            param('florentin_garnier_cookie_consent.privacy_policy.route'),
            param('florentin_garnier_cookie_consent.privacy_policy.parameters'),
            param('florentin_garnier_cookie_consent.google_consent_mode.signals'),
        ])
        ->tag('twig.extension')
    ;

    $services->set(PurgeConsentRecordsCommand::class)
        ->args([service(ConsentRecordRepositoryInterface::class), param('florentin_garnier_cookie_consent.records.retention')])
        ->tag('console.command')
    ;
};
