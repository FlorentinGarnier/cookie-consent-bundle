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
 * Tells on which site, channel or tenant the visitor consents, for an application that has several.
 */
interface ChannelCodeProviderInterface
{
    public function getChannelCode(): ?string;
}
