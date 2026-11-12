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

namespace FlorentinGarnier\CookieConsentBundle\DependencyInjection;

use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\NoChannelCodeProvider;
use FlorentinGarnier\CookieConsentBundle\Entity\ConsentRecord;
use FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class FlorentinGarnierCookieConsentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{policy_version: string, cookie: array{name: string, lifetime: int, domain: ?string}, privacy_policy: array{route: ?string, parameters: array<string, mixed>}, categories: array<string, array{required: bool, services: list<string>, cookies: list<string>}>, google_consent_mode: array{enabled: bool, signals: array<string, string>}, records: array{retention: int, class: string}, channel_code_provider: ?string} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        if ($config['google_consent_mode']['enabled']) {
            self::checkGoogleConsentModeSignals($config['google_consent_mode']['signals'], $config['categories']);
        }

        $parameters = [
            'policy_version' => $config['policy_version'],
            'categories' => $config['categories'],
            'cookie.name' => $config['cookie']['name'],
            'cookie.lifetime' => $config['cookie']['lifetime'],
            'cookie.domain' => $config['cookie']['domain'],
            'privacy_policy.route' => $config['privacy_policy']['route'],
            'privacy_policy.parameters' => $config['privacy_policy']['parameters'],
            'google_consent_mode.signals' => $config['google_consent_mode']['enabled'] ? $config['google_consent_mode']['signals'] : [],
            'records.retention' => $config['records']['retention'],
            'records.class' => $config['records']['class'],
        ];
        foreach ($parameters as $name => $value) {
            $container->setParameter('florentin_garnier_cookie_consent.' . $name, $value);
        }

        if (ConsentRecord::class === $config['records']['class']) {
            $container->setParameter(FlorentinGarnierCookieConsentBundle::DEFAULT_ENTITY_PARAMETER, true);
        }

        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config')))->load('services.php');

        $container->setAlias(ChannelCodeProviderInterface::class, $config['channel_code_provider'] ?? NoChannelCodeProvider::class);
    }

    /**
     * @param array<string, string> $signals
     * @param array<string, mixed>  $categories
     */
    private static function checkGoogleConsentModeSignals(array $signals, array $categories): void
    {
        foreach ($signals as $signal => $category) {
            if (!isset($categories[$category])) {
                throw new InvalidConfigurationException(\sprintf('The Google consent mode signal "%s" refers to the "%s" category, which is not configured.', $signal, $category));
            }
        }
    }
}
