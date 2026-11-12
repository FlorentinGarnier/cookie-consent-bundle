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

namespace FlorentinGarnier\CookieConsentBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FlorentinGarnier\CookieConsent\Consent;
use FlorentinGarnier\CookieConsent\ConsentRecord;
use FlorentinGarnier\CookieConsent\ConsentRecordRepositoryInterface;

final class ConsentRecordRepository implements ConsentRecordRepositoryInterface
{
    /**
     * @param class-string<ConsentRecord> $class The entity storing the records
     */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly string $class,
    ) {
    }

    public function record(Consent $consent, ?string $channelCode = null): ConsentRecord
    {
        $record = $this->class::fromConsent($consent, $channelCode);

        $manager = $this->manager();
        $manager->persist($record);
        $manager->flush();

        return $record;
    }

    public function removeCreatedBefore(\DateTimeImmutable $date): int
    {
        $removed = $this->manager()->createQueryBuilder()
            ->delete($this->class, 'o')
            ->where('o.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute()
        ;

        return \is_int($removed) ? $removed : 0;
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass($this->class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(\sprintf('No Doctrine ORM entity manager maps the consent records of class "%s".', $this->class));
        }

        return $manager;
    }
}
