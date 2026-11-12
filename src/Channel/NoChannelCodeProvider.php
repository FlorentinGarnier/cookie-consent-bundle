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

namespace FlorentinGarnier\CookieConsentBundle\Channel;

/**
 * For an application with a single site.
 */
final class NoChannelCodeProvider implements ChannelCodeProviderInterface
{
    public function getChannelCode(): ?string
    {
        return null;
    }
}
