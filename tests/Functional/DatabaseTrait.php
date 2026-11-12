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

namespace FlorentinGarnier\CookieConsentBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use FlorentinGarnier\CookieConsentBundle\Entity\ConsentRecord;

trait DatabaseTrait
{
    private static function createSchema(): void
    {
        $entityManager = self::entityManager();
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @return list<ConsentRecord>
     */
    private static function records(): array
    {
        self::entityManager()->clear();

        return self::entityManager()->getRepository(ConsentRecord::class)->findAll();
    }

    private static function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
