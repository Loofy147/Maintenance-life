<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use MaintenancePro\Domain\Entity\MaintenanceSession;
use MaintenancePro\Domain\Entity\MaintenanceStatus;
use MaintenancePro\Domain\ValueObjects\TimePeriod;
use PHPUnit\Framework\TestCase;

class MaintenanceSessionTest extends TestCase
{
    private TimePeriod $timePeriod;

    protected function setUp(): void
    {
        $start = new \DateTimeImmutable();
        $end = $start->add(new \DateInterval('PT1H'));
        $this->timePeriod = new TimePeriod($start, $end);
    }

    public function testCanBeCreatedWithScheduledStatus()
    {
        $session = new MaintenanceSession($this->timePeriod, 'Test Reason');

        $this->assertSame(MaintenanceStatus::SCHEDULED, $session->getStatus());
        $this->assertEquals('Test Reason', $session->getReason());
        $this->assertNull($session->getId());
        $this->assertFalse($session->isActive());
    }

    public function testCanBeStarted()
    {
        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->start();

        $this->assertSame(MaintenanceStatus::ACTIVE, $session->getStatus());
        $this->assertTrue($session->isActive());
    }

    public function testCannotStartWhenAlreadyActive()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only start a scheduled maintenance session');

        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->start();
        $session->start();
    }

    public function testCanBeCompleted()
    {
        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->start();
        $session->complete();

        $this->assertSame(MaintenanceStatus::COMPLETED, $session->getStatus());
        $this->assertFalse($session->isActive());
    }

    public function testCannotCompleteUnlessActive()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only complete an active maintenance session');

        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->complete();
    }

    public function testCanBeCancelledWhenScheduled()
    {
        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->cancel();

        $this->assertSame(MaintenanceStatus::CANCELLED, $session->getStatus());
        $this->assertFalse($session->isActive());
    }

    public function testCanBeCancelledWhenActive()
    {
        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->start();
        $session->cancel();

        $this->assertSame(MaintenanceStatus::CANCELLED, $session->getStatus());
        $this->assertFalse($session->isActive());
    }

    public function testCannotCancelWhenCompleted()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot cancel a completed maintenance session');

        $session = new MaintenanceSession($this->timePeriod, 'Test');
        $session->start();
        $session->complete();
        $session->cancel();
    }

    public function testGettersReturnCorrectValues()
    {
        $metadata = ['key' => 'value'];
        $session = new MaintenanceSession($this->timePeriod, 'Getter Test', $metadata);

        $this->assertSame($this->timePeriod, $session->getPeriod());
        $this->assertEquals('Getter Test', $session->getReason());
        $this->assertEquals($metadata, $session->getMetadata());
    }
}