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

namespace FlorentinGarnier\CookieConsentBundle\Entity;

use FlorentinGarnier\CookieConsent\ConsentRecord as BaseConsentRecord;

/**
 * The record stored in the cookie_consent_record table, unless the records.class option names another entity.
 */
class ConsentRecord extends BaseConsentRecord
{
}
