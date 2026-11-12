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

namespace FlorentinGarnier\CookieConsentBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Configures the policy version "2" with the necessary, analytics and media categories, a privacy policy and the
 * Google consent mode for the analytics_storage signal.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
        yield new FlorentinGarnierCookieConsentBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'assets' => null,
            'translator' => ['fallbacks' => ['en']],
            'router' => ['utf8' => true],
        ]);
        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///' . \dirname(__DIR__, 2) . '/var/test.sqlite'],
            'orm' => [],
        ]);
        $container->extension('florentin_garnier_cookie_consent', [
            'policy_version' => '2',
            'privacy_policy' => ['route' => 'privacy_policy'],
            'categories' => [
                'necessary' => ['required' => true],
                'analytics' => ['services' => ['Google Analytics'], 'cookies' => ['_ga', '_ga_*']],
                'media' => ['services' => ['YouTube']],
            ],
            'google_consent_mode' => ['enabled' => true, 'signals' => ['analytics_storage' => 'analytics']],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\dirname(__DIR__, 2) . '/config/routes.php');
        $routes->add('privacy_policy', '/privacy');
    }
}
