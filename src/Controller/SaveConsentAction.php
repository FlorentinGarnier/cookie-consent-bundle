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

namespace FlorentinGarnier\CookieConsentBundle\Controller;

use FlorentinGarnier\CookieConsent\ConsentCookie;
use FlorentinGarnier\CookieConsent\ConsentPolicy;
use FlorentinGarnier\CookieConsent\ConsentRecordRepositoryInterface;
use FlorentinGarnier\CookieConsent\UnknownCategoryException;
use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the choice of the visitor, sent as {"categories": […]}, and stores it in the consent cookie.
 *
 * Only JSON is accepted: a browser cannot send it from another site without a CORS preflight, which this action does
 * not answer. Another site therefore cannot make a visitor consent without knowing it.
 */
final class SaveConsentAction
{
    public function __construct(
        private readonly ConsentPolicy $policy,
        private readonly ConsentCookie $cookie,
        private readonly ConsentRecordRepositoryInterface $records,
        private readonly ChannelCodeProviderInterface $channelCodeProvider,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
            return self::error('The consent must be sent as JSON.', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $chosen = self::chosenCategories($request);
        if (null === $chosen) {
            return self::error('The consent must be sent as {"categories": [...]}.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $consent = $this->policy->consent($chosen, $this->cookie->readId($request->cookies->get($this->cookie->name())));
        } catch (UnknownCategoryException $exception) {
            return self::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $this->records->record($consent, $this->channelCodeProvider->getChannelCode());

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(Cookie::create(
            $this->cookie->name(),
            $this->cookie->encode($consent),
            $this->cookie->expiresAt($consent),
            '/',
            $this->cookie->domain(),
            $request->isSecure(),
            false, // The browser script reads it
            false,
            Cookie::SAMESITE_LAX,
        ));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private static function chosenCategories(Request $request): ?array
    {
        try {
            $data = json_decode($request->getContent(), true, 3, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($data) && \is_array($data['categories'] ?? null) ? $data['categories'] : null;
    }

    private static function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
