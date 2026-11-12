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

namespace FlorentinGarnier\CookieConsentBundle\Tests\Controller;

use FlorentinGarnier\CookieConsent\Category;
use FlorentinGarnier\CookieConsent\Consent;
use FlorentinGarnier\CookieConsent\ConsentCookie;
use FlorentinGarnier\CookieConsent\ConsentPolicy;
use FlorentinGarnier\CookieConsent\ConsentRecord;
use FlorentinGarnier\CookieConsent\ConsentRecordRepositoryInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use FlorentinGarnier\CookieConsentBundle\Channel\NoChannelCodeProvider;
use FlorentinGarnier\CookieConsentBundle\Controller\SaveConsentAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * The HTTP behaviour is covered by the functional tests: these tests cover the channel and the cookie attributes.
 */
#[CoversClass(SaveConsentAction::class)]
#[CoversClass(NoChannelCodeProvider::class)]
final class SaveConsentActionTest extends TestCase
{
    public function testTheRecordTellsOnWhichChannelTheVisitorConsented(): void
    {
        $channelCodeProvider = self::createStub(ChannelCodeProviderInterface::class);
        $channelCodeProvider->method('getChannelCode')->willReturn('FASHION_WEB');

        self::assertSame('FASHION_WEB', $this->recordSavedWith($channelCodeProvider)->getChannelCode());
    }

    public function testAConsentIsRecordedWithoutChannelByDefault(): void
    {
        self::assertNull($this->recordSavedWith(new NoChannelCodeProvider())->getChannelCode());
    }

    public function testTheBrowserScriptCanReadTheCookieOfTheWholeSite(): void
    {
        $policy = new ConsentPolicy('1', [new Category('necessary', true)]);
        $action = new SaveConsentAction($policy, new ConsentCookie($policy, 'cookie_consent', 182, '.example.com'), self::createStub(ConsentRecordRepositoryInterface::class), new NoChannelCodeProvider());

        $response = $action(new Request(server: ['CONTENT_TYPE' => 'application/json', 'HTTPS' => 'on'], content: '{"categories": []}'));

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertFalse($cookies[0]->isHttpOnly());
        self::assertTrue($cookies[0]->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookies[0]->getSameSite());
        self::assertSame('/', $cookies[0]->getPath());
        self::assertSame('.example.com', $cookies[0]->getDomain());
        self::assertEqualsWithDelta((new \DateTimeImmutable('+182 days'))->getTimestamp(), $cookies[0]->getExpiresTime(), 5);
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    private function recordSavedWith(ChannelCodeProviderInterface $channelCodeProvider): ConsentRecord
    {
        $policy = new ConsentPolicy('1', [new Category('necessary', true)]);
        $records = $this->createMock(ConsentRecordRepositoryInterface::class);
        $saved = null;
        $records->expects(self::once())->method('record')->willReturnCallback(static function (Consent $consent, ?string $channelCode) use (&$saved): ConsentRecord {
            return $saved = ConsentRecord::fromConsent($consent, $channelCode);
        });

        $action = new SaveConsentAction($policy, new ConsentCookie($policy), $records, $channelCodeProvider);
        $action(new Request(server: ['CONTENT_TYPE' => 'application/json'], content: '{"categories": []}'));

        self::assertInstanceOf(ConsentRecord::class, $saved);

        return $saved;
    }
}
