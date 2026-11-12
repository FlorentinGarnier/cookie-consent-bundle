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

namespace FlorentinGarnier\CookieConsentBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class FlorentinGarnierCookieConsentBundle extends Bundle
{
    /**
     * Set when the records are stored with the entity of the bundle rather than with an entity of the application.
     */
    public const DEFAULT_ENTITY_PARAMETER = 'florentin_garnier_cookie_consent.records.default_entity';

    /**
     * The bundle lives at the package root (config/, public/, templates/, translations/), not in src/.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * The entity is mapped before the record it extends: Doctrine gives a class to the first mapping whose namespace
     * starts its name, and the namespace of the bundle starts with the one of the library.
     */
    public function build(ContainerBuilder $container): void
    {
        $mappings = \dirname(__DIR__) . '/config/doctrine';

        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver(
            [$mappings . '/entity' => 'FlorentinGarnier\CookieConsentBundle\Entity'],
            [],
            self::DEFAULT_ENTITY_PARAMETER,
        ));
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver(
            [$mappings . '/model' => 'FlorentinGarnier\CookieConsent'],
        ));
    }
}
