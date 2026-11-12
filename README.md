# Cookie Consent Bundle

[![CI](https://github.com/FlorentinGarnier/cookie-consent-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/FlorentinGarnier/cookie-consent-bundle/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/florentingarnier/cookie-consent-bundle.svg)](https://packagist.org/packages/florentingarnier/cookie-consent-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/florentingarnier/cookie-consent-bundle.svg)](https://packagist.org/packages/florentingarnier/cookie-consent-bundle)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)
![Symfony](https://img.shields.io/badge/symfony-5.4%20%7C%206.4%20%7C%207.4%20%7C%208-black.svg)

Symfony integration of [florentingarnier/cookie-consent](https://github.com/FlorentinGarnier/cookie-consent). It asks
the visitors for their consent to cookies, purpose by purpose, blocks the scripts and the embedded contents until they
consent, and keeps a proof of each choice. It follows the recommendations of the French data protection authority
(CNIL):

- nothing optional is loaded before the visitor chooses;
- rejecting is as easy as accepting: both buttons have the same style, on the banner and in the preferences;
- the visitor can accept or reject each purpose, and sees the third parties behind it;
- the visitor can change or withdraw the consent at any time, from a link in the footer; the cookies of a withdrawn
  purpose are deleted;
- the choice is kept for 6 months by default (13 months at most), then the visitor is asked again;
- each choice is recorded, without any personal data, so that it can be proven.

No external service, no build step: one module script and one style sheet, themable with CSS custom properties.

Using Sylius? See [atriametal/sylius-cookie-consent-plugin](https://github.com/atriametal/sylius-cookie-consent-plugin),
which renders the banner in the shop and lists the records in the admin.

## Requirements

- PHP 8.2 or later
- Symfony 5.4, 6.4, 7.4 or 8.x, with Twig, the assets and the translator enabled. Symfony 8 requires PHP 8.4.
- Doctrine ORM, to store the records
- JavaScript in the visitor's browser: without it, nothing optional is loaded

## Installation

1. Require the bundle:

   ```bash
   composer require florentingarnier/cookie-consent-bundle
   ```

2. If you do not use Symfony Flex, enable the bundle:

   ```php
   // config/bundles.php
   return [
       // ...
       FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle::class => ['all' => true],
   ];
   ```

3. Import the route receiving the choices. Keep it outside any locale prefix:

   ```yaml
   # config/routes/florentin_garnier_cookie_consent.yaml
   florentin_garnier_cookie_consent:
       resource: '@FlorentinGarnierCookieConsentBundle/config/routes.php'
   ```

4. Install the assets and create the `cookie_consent_record` table:

   ```bash
   bin/console assets:install
   bin/console doctrine:migrations:diff
   bin/console doctrine:migrations:migrate
   ```

5. Render the templates in your layout: the style sheet in the `<head>`, before your Google tags, and the banner at the
   top of the `<body>`, so that keyboard and screen reader users reach it first:

   ```twig
   <head>
       {{ include('@FlorentinGarnierCookieConsent/stylesheets.html.twig') }}
       {# your Google tags #}
   </head>
   <body>
       {{ include('@FlorentinGarnierCookieConsent/banner.html.twig') }}
       {# … #}
       <footer>{{ include('@FlorentinGarnierCookieConsent/preferences_link.html.twig') }}</footer>
       {{ include('@FlorentinGarnierCookieConsent/javascripts.html.twig') }}
   </body>
   ```

6. Describe your cookies (see [Configuration](#configuration)) and block your scripts and embedded contents (see
   [Blocking](#blocking-scripts-and-embedded-contents)).

7. Remove the consent records older than the retention period every day, for instance with cron:

   ```bash
   bin/console cookie-consent:purge-records
   ```

## Configuration

```yaml
# config/packages/florentin_garnier_cookie_consent.yaml
florentin_garnier_cookie_consent:
    # Change it to ask every visitor again, for instance after adding a category or a service
    policy_version: '1'
    cookie:
        name: cookie_consent
        lifetime: 182 # days, 395 at most
        domain: ~ # ".example.com" shares the consent between subdomains
    privacy_policy:
        route: ~ # the page describing your cookies, linked from the banner
        parameters: {}
    # Replace the default categories: necessary (required), analytics, marketing and media
    categories:
        necessary:
            required: true # always granted: only for the cookies strictly necessary to the site
        analytics:
            services: ['Google Analytics'] # shown to the visitor
            cookies: ['_ga', '_ga_*'] # deleted when the visitor withdraws the consent
        media:
            services: ['YouTube']
    google_consent_mode:
        enabled: false
        signals: # the category granting each signal
            analytics_storage: analytics
            ad_storage: marketing
            ad_user_data: marketing
            ad_personalization: marketing
    records:
        retention: 395 # days
        # The Doctrine entity storing the records; it must extend FlorentinGarnier\CookieConsent\ConsentRecord
        class: FlorentinGarnier\CookieConsentBundle\Entity\ConsentRecord
    # A service implementing FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface, recorded with
    # each choice, for an application with several sites or channels
    channel_code_provider: ~
```

The label and the description of each category are translated from
`florentin_garnier_cookie_consent.categories.<name>.label` and `.description`, in the `messages` domain. The bundle
translates the default categories in English, French and German. Override any text in your
`translations/messages.<locale>.yaml`: the texts of the banner must describe the purposes you actually use.

### Storing the records in your own entity

The bundle maps the fields of `FlorentinGarnier\CookieConsent\ConsentRecord` as a mapped superclass. To store the
records in another table, or to add to them, extend it with an entity of your own and set `records.class`: the entity
of the bundle is then not mapped.

```php
#[ORM\Entity]
#[ORM\Table(name: 'app_cookie_consent_record')]
class ConsentRecord extends \FlorentinGarnier\CookieConsent\ConsentRecord
{
}
```

## Blocking scripts and embedded contents

A script runs once a visitor consents to any of the categories listed in its `data-cookie-consent` attribute.
Give it the `text/plain` type, so that the browser does not run it before:

```html
<script type="text/plain" data-cookie-consent="analytics marketing">
    (function(w,d,s,l,i){ /* Google Tag Manager */ })(window,document,'script','dataLayer','GTM-XXXX');
</script>
<script type="text/plain" data-cookie-consent="analytics" src="https://example.com/tracker.js" async></script>
```

A module script keeps its type in `data-type="module"`.

An embedded content gets its address from `data-src`. Until the visitor consents, a placeholder explains why it is
hidden and lets the visitor allow its category; `data-cookie-consent-service` names the third party in it:

```html
<iframe data-cookie-consent="media" data-cookie-consent-service="YouTube"
        data-src="https://www.youtube-nocookie.com/embed/VIDEO_ID" title="…"></iframe>
```

On the server side, `cookie_consent_granted('media')` tells whether the visitor consented. Do not use it in pages
cached for every visitor.

### Google consent mode

With `google_consent_mode.enabled`, the style sheet template sets the default state of the configured signals before
any Google tag loads: granted for what the visitor already consented to, denied otherwise. The script then sends each
new choice with `gtag('consent', 'update', …)`. Render the style sheet template before your Google tags and before any
event you push to the data layer: Google tags evaluate each event with the consent state of that moment. As it reads
the consent cookie, do not render it in pages cached for every visitor. The consent mode does not replace the
blocking: without consent, Google tags still send cookieless pings.

### JavaScript

```js
window.cookieConsent.isGranted('analytics'); // once the script has run
window.cookieConsent.open(); // opens the preferences

// Dispatched when the script starts, then after each choice
document.addEventListener('cookie-consent:update', (event) => {
  if (event.detail.categories.includes('analytics')) {
    // start what needs this consent
  }
});
```

Any element with `data-cookie-consent-action="open"` opens the preferences. A script cannot be unloaded: when the
visitor withdraws a consent, the page is loaded again, after the cookies of the category are deleted.

## Theming

Override the custom properties of `.cookie-consent` and `.cookie-consent-placeholder`:

```css
.cookie-consent,
.cookie-consent-placeholder {
  --cookie-consent-font-family: 'Inter', sans-serif;
  --cookie-consent-text: #121212;
  --cookie-consent-button-background: #121212;
  --cookie-consent-radius: 0;
}
```

See [public/cookie-consent.css](public/cookie-consent.css) for the full list. Keep accepting and rejecting with the
same style.

## Proof of consent

Each choice is recorded with a random identifier, the version of the policy, the granted categories, the date and,
if you configured a `channel_code_provider`, the channel: no IP address, no user. The identifier stays the same when
the visitor changes the choice, and is shown in the preferences, so that the visitor can refer to it.

## Limitations

- Without JavaScript, nothing is unblocked and the banner is not shown.
- The texts of the banner are yours: they must match the categories and services you configure.
- Another site cannot make a visitor consent: the choice is only accepted as JSON, which browsers cannot send from
  another site without a CORS preflight.

## Development

```bash
composer install
vendor/bin/phpunit               # PHP 8.2 or later, with pdo_sqlite
vendor/bin/phpstan analyse
node --test tests/js/*.test.mjs  # browser script, Node.js 22 or later
```

## License

[MIT](LICENSE)
