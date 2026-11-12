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

use FlorentinGarnier\CookieConsentBundle\Command\PurgeConsentRecordsCommand;
use FlorentinGarnier\CookieConsentBundle\Entity\ConsentRecord;
use FlorentinGarnier\CookieConsentBundle\Doctrine\ConsentRecordRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(PurgeConsentRecordsCommand::class)]
#[CoversClass(ConsentRecordRepository::class)]
final class PurgeConsentRecordsCommandTest extends KernelTestCase
{
    use DatabaseTrait;

    public function testItRemovesTheRecordsOlderThanTheRetentionPeriod(): void
    {
        $kernel = self::bootKernel();
        self::createSchema();
        $entityManager = self::entityManager();

        // The default retention period is 395 days
        foreach (['-396 days' => 'expired', '-394 days' => 'kept'] as $age => $consentId) {
            $entityManager->persist(new ConsentRecord($consentId, '2', ['necessary'], new \DateTimeImmutable($age)));
        }
        $entityManager->flush();
        $entityManager->clear();

        $command = new CommandTester((new Application($kernel))->find('cookie-consent:purge-records'));
        $command->execute([]);

        $command->assertCommandIsSuccessful();
        self::assertStringContainsString('Removed 1 consent record(s) older than 395 days.', $command->getDisplay());
        self::assertSame(
            ['kept'],
            array_map(static fn (ConsentRecord $record): string => $record->getConsentId(), $entityManager->getRepository(ConsentRecord::class)->findAll()),
        );
    }
}
