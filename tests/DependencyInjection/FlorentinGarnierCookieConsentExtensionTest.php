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

namespace FlorentinGarnier\CookieConsentBundle\Tests\DependencyInjection;

use FlorentinGarnier\CookieConsent\ConsentRecord as BaseConsentRecord;
use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\NoChannelCodeProvider;
use FlorentinGarnier\CookieConsentBundle\DependencyInjection\Configuration;
use FlorentinGarnier\CookieConsentBundle\DependencyInjection\FlorentinGarnierCookieConsentExtension;
use FlorentinGarnier\CookieConsentBundle\Entity\ConsentRecord;
use FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(FlorentinGarnierCookieConsentExtension::class)]
#[CoversClass(Configuration::class)]
final class FlorentinGarnierCookieConsentExtensionTest extends TestCase
{
    public function testTheDefaultCategoriesAskForEveryUsualPurpose(): void
    {
        $container = self::load([]);

        self::assertSame('1', $container->getParameter('florentin_garnier_cookie_consent.policy_version'));
        self::assertSame(
            [
                'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
                'analytics' => ['required' => false, 'services' => [], 'cookies' => []],
                'marketing' => ['required' => false, 'services' => [], 'cookies' => []],
                'media' => ['required' => false, 'services' => [], 'cookies' => []],
            ],
            $container->getParameter('florentin_garnier_cookie_consent.categories'),
        );
        self::assertSame(182, $container->getParameter('florentin_garnier_cookie_consent.cookie.lifetime'));
        self::assertSame([], $container->getParameter('florentin_garnier_cookie_consent.google_consent_mode.signals'));
    }

    public function testTheConfiguredCategoriesReplaceTheDefaultOnes(): void
    {
        $container = self::load([
            ['categories' => ['necessary' => ['required' => true], 'media' => ['services' => ['YouTube']]]],
        ]);

        self::assertEquals(
            [
                'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
                'media' => ['required' => false, 'services' => ['YouTube'], 'cookies' => []],
            ],
            $container->getParameter('florentin_garnier_cookie_consent.categories'),
        );
    }

    public function testTheGoogleConsentModeSendsTheConfiguredSignals(): void
    {
        $container = self::load([['google_consent_mode' => ['enabled' => true]]]);

        self::assertSame(
            ['analytics_storage' => 'analytics', 'ad_storage' => 'marketing', 'ad_user_data' => 'marketing', 'ad_personalization' => 'marketing'],
            $container->getParameter('florentin_garnier_cookie_consent.google_consent_mode.signals'),
        );
    }

    public function testAGoogleConsentModeSignalMustReferToAConfiguredCategory(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The Google consent mode signal "ad_storage" refers to the "marketing" category, which is not configured.');

        self::load([['categories' => ['necessary' => ['required' => true], 'analytics' => []], 'google_consent_mode' => ['enabled' => true]]]);
    }

    public function testACategoryNameMustBeUsableInTheTranslationKeysAndTheAttributes(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load([['categories' => ['Social networks' => []]]]);
    }

    public function testTheCookieCannotOutliveTheConsent(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load([['cookie' => ['lifetime' => 396]]]);
    }

    public function testTheRecordsAreStoredWithTheEntityOfTheBundleByDefault(): void
    {
        $container = self::load([]);

        self::assertSame(ConsentRecord::class, $container->getParameter('florentin_garnier_cookie_consent.records.class'));
        self::assertTrue($container->hasParameter(FlorentinGarnierCookieConsentBundle::DEFAULT_ENTITY_PARAMETER));
        self::assertSame(NoChannelCodeProvider::class, (string) $container->getAlias(ChannelCodeProviderInterface::class));
    }

    public function testTheApplicationCanStoreTheRecordsWithItsOwnEntity(): void
    {
        $container = self::load([['records' => ['class' => ApplicationConsentRecord::class], 'channel_code_provider' => 'app.channel_code_provider']]);

        self::assertSame(ApplicationConsentRecord::class, $container->getParameter('florentin_garnier_cookie_consent.records.class'));
        self::assertFalse($container->hasParameter(FlorentinGarnierCookieConsentBundle::DEFAULT_ENTITY_PARAMETER));
        self::assertSame('app.channel_code_provider', (string) $container->getAlias(ChannelCodeProviderInterface::class));
    }

    public function testTheEntityOfTheApplicationMustExtendTheRecord(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load([['records' => ['class' => \stdClass::class]]]);
    }

    /**
     * @param list<array<string, mixed>> $configs
     */
    private static function load(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();

        (new FlorentinGarnierCookieConsentExtension())->load($configs, $container);

        return $container;
    }
}

final class ApplicationConsentRecord extends BaseConsentRecord
{
}
