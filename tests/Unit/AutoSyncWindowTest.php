<?php

namespace Tests\Unit;

use App\Support\AutoSyncWindow;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutoSyncWindowTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('windowCases')]
    public function test_night_window_boundaries(string $baghdadTime, bool $open): void
    {
        $at = Carbon::parse('2026-09-28 '.$baghdadTime, AutoSyncWindow::TIMEZONE);

        $this->assertSame($open, AutoSyncWindow::isOpen($at));
        $this->assertSame($open, AutoSyncWindow::describe($at)['open']);
        $this->assertSame('22:00', AutoSyncWindow::describe($at)['starts_at']);
        $this->assertSame('06:00', AutoSyncWindow::describe($at)['ends_at']);
    }

    public function test_describe_uses_baghdad_even_when_app_clock_is_utc(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 19:15:00', 'UTC'));

        $described = AutoSyncWindow::describe();

        $this->assertTrue($described['open']);
        $this->assertSame('22:15', $described['local_time']);
        $this->assertSame(AutoSyncWindow::TIMEZONE, $described['timezone']);
    }

    public function test_scheduler_runs_sync_only_inside_the_night_window(): void
    {
        $schedule = app(Schedule::class);

        Carbon::setTestNow(Carbon::parse('2026-09-28 08:07:00', 'UTC'));
        $vinstack = $this->scheduledCommand($schedule, 'vinstack:sync');
        $this->assertTrue($vinstack->isDue($this->app));
        $this->assertFalse($vinstack->filtersPass($this->app));

        Carbon::setTestNow(Carbon::parse('2026-09-28 08:37:00', 'UTC'));
        $autoshipper = $this->scheduledCommand($schedule, 'autoshipper:sync');
        $this->assertTrue($autoshipper->isDue($this->app));
        $this->assertFalse($autoshipper->filtersPass($this->app));

        Carbon::setTestNow(Carbon::parse('2026-09-28 19:07:00', 'UTC'));
        $vinstack = $this->scheduledCommand($schedule, 'vinstack:sync');
        $this->assertTrue($vinstack->isDue($this->app));
        $this->assertTrue($vinstack->filtersPass($this->app));

        Carbon::setTestNow(Carbon::parse('2026-09-28 19:37:00', 'UTC'));
        $autoshipper = $this->scheduledCommand($schedule, 'autoshipper:sync');
        $this->assertTrue($autoshipper->isDue($this->app));
        $this->assertTrue($autoshipper->filtersPass($this->app));
    }

    private function scheduledCommand(Schedule $schedule, string $command): \Illuminate\Console\Scheduling\Event
    {
        $event = collect($schedule->events())->first(
            fn ($event) => str_contains((string) $event->command, $command)
        );

        $this->assertNotNull($event);

        return $event;
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function windowCases(): array
    {
        return [
            'just before 22:00' => ['21:59', false],
            'opens at 22:00' => ['22:00', true],
            'midnight' => ['00:30', true],
            'last minute' => ['05:59', true],
            'closes at 06:00' => ['06:00', false],
            'morning peak' => ['08:42', false],
            'midday' => ['12:00', false],
        ];
    }
}
