<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use MaintenancePro\Domain\ValueObjects\TimePeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimePeriod::class)]
class TimePeriodTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_valid_start_and_end_dates(): void
    {
        $start = new \DateTimeImmutable('2025-01-01 10:00:00');
        $end = new \DateTimeImmutable('2025-01-01 11:00:00');
        $period = new TimePeriod($start, $end);

        $this->assertSame(3600, $period->getDurationInSeconds());
    }

    #[Test]
    public function it_throws_an_exception_if_start_date_is_after_end_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $start = new \DateTimeImmutable('2025-01-01 12:00:00');
        $end = new \DateTimeImmutable('2025-01-01 10:00:00');
        new TimePeriod($start, $end);
    }

    #[Test]
    public function it_can_check_if_a_datetime_is_contained_within_the_period(): void
    {
        $start = new \DateTimeImmutable('2025-01-01 10:00:00');
        $end = new \DateTimeImmutable('2025-01-01 12:00:00');
        $period = new TimePeriod($start, $end);

        $containedDate = new \DateTimeImmutable('2025-01-01 11:00:00');
        $outsideDate = new \DateTimeImmutable('2025-01-01 13:00:00');

        $this->assertTrue($period->contains($containedDate));
        $this->assertFalse($period->contains($outsideDate));
    }
}