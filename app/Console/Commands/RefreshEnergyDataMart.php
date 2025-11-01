<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\EnergyFlowLog;
use Carbon\Carbon;

class RefreshEnergyDataMart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-energy-data-mart {--since=} {--max-minutes=} {--max-days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Incrementally refresh 15-minute and daily energy aggregates';

    public function handle(): int
    {
        $this->info('Refreshing energy data mart...');

        // Ensure required tables exist (idempotent)
        $this->ensureDataMartTables();

        $sinceOption = $this->option('since');
        $since = $sinceOption ? Carbon::parse($sinceOption) : $this->resolveSince();
        $until = Carbon::now();

        if ($since >= $until) {
            $this->info('Nothing to process.');
            return 0;
        }

        // Build dimension scaffolding for the date range
        $this->seedDimDate($since->copy()->startOfDay(), $until->copy()->endOfDay());
        $this->seedDimTime();

        // Process 15-minute windows
        $maxMinutes = $this->option('max-minutes');
        $this->processFifteenMinuteWindows($since, $until, $maxMinutes ? (int)$maxMinutes : null);

        // Process daily aggregates (optional cap)
        $maxDays = $this->option('max-days');
        $this->processDaily($since->copy()->startOfDay(), $until->copy()->endOfDay(), $maxDays ? (int)$maxDays : null);

        $this->info('Energy data mart refresh complete.');
        return 0;
    }

    private function resolveSince(): Carbon
    {
        if (Schema::hasTable('agg_energy_15min')) {
            $row = DB::table('agg_energy_15min')
                ->select(DB::raw('MAX(window_end) as max_end'))
                ->first();

            if ($row && $row->max_end) {
                return Carbon::parse($row->max_end);
            }
        }

        $firstLog = EnergyFlowLog::query()->orderBy('created_at', 'asc')->value('created_at');
        return $firstLog ? Carbon::parse($firstLog) : Carbon::now()->subDay();
    }

    private function ensureDataMartTables(): void
    {
        $missing = [];
        foreach (['dim_date', 'dim_time', 'agg_energy_15min', 'agg_energy_daily'] as $table) {
            if (!Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (!empty($missing)) {
            $this->warn('Missing data mart tables: '.implode(', ', $missing).'. Attempting to run migration...');
            try {
                // Try targeted migration first
                $path = 'database/migrations/2025_10_30_000001_create_data_mart_dimensions_and_aggregates.php';
                Artisan::call('migrate', ['--path' => $path, '--force' => true]);
            } catch (\Throwable $e) {
                // Fallback to run all pending migrations
                $this->warn('Targeted migration failed, running all pending migrations...');
                Artisan::call('migrate', ['--force' => true]);
            }
        }
    }

    private function seedDimDate(Carbon $from, Carbon $to): void
    {
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $dateKey = (int)$cursor->format('Ymd');
            DB::table('dim_date')->updateOrInsert(
                ['date_key' => $dateKey],
                [
                    'date' => $cursor->toDateString(),
                    'year' => (int)$cursor->format('Y'),
                    'month' => (int)$cursor->format('n'),
                    'day' => (int)$cursor->format('j'),
                    'week' => (int)$cursor->format('W'),
                    'is_weekend' => in_array($cursor->dayOfWeekIso, [6, 7]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $cursor->addDay();
        }
    }

    private function seedDimTime(): void
    {
        // Populate 24*60 minutes if not present
        $exists = DB::table('dim_time')->count();
        if ($exists >= 1440) {
            return;
        }

        for ($h = 0; $h < 24; $h++) {
            for ($m = 0; $m < 60; $m++) {
                $timeKey = (int)($h * 100 + $m);
                $daypart = $this->daypart($h);
                DB::table('dim_time')->updateOrInsert(
                    ['time_key' => $timeKey],
                    [
                        'hour' => $h,
                        'minute' => $m,
                        'daypart' => $daypart,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function daypart(int $hour): string
    {
        if ($hour >= 5 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 17) return 'afternoon';
        if ($hour >= 17 && $hour < 21) return 'evening';
        return 'night';
    }

    private function floorToFifteen(Carbon $ts): Carbon
    {
        $minute = (int)floor($ts->minute / 15) * 15;
        return $ts->copy()->second(0)->minute($minute);
    }

    private function processFifteenMinuteWindows(Carbon $from, Carbon $to, ?int $maxMinutes = null): void
    {
        $windowStart = $this->floorToFifteen($from->copy());
        $processedMinutes = 0; // minutes inserted
        $scannedMinutes = 0;   // minutes scanned (including empty windows)
        while ($windowStart->lt($to)) {
            $windowEnd = $windowStart->copy()->addMinutes(15);

            // Skip if already aggregated
            $exists = DB::table('agg_energy_15min')
                ->where('window_start', $windowStart)
                ->where('window_end', $windowEnd)
                ->exists();
            if ($exists) {
                $windowStart->addMinutes(15);
                $scannedMinutes += 15;
                if ($maxMinutes !== null && $scannedMinutes >= $maxMinutes) {
                    break;
                }
                continue;
            }

            $logs = EnergyFlowLog::query()
                ->where('created_at', '>=', $windowStart)
                ->where('created_at', '<', $windowEnd)
                ->get(['grid_power', 'total_pv_power', 'home_load_power', 'battery_power', 'zappi_node', 'created_at']);

            if ($logs->isEmpty()) {
                $windowStart->addMinutes(15);
                $scannedMinutes += 15;
                if ($maxMinutes !== null && $scannedMinutes >= $maxMinutes) {
                    break;
                }
                continue;
            }

            $sumGrid = $logs->sum(fn ($r) => (float)($r->grid_power ?? 0));
            $sumPv = $logs->sum(fn ($r) => (float)($r->total_pv_power ?? 0));
            $sumHome = $logs->sum(fn ($r) => (float)($r->home_load_power ?? 0));
            $sumBattery = $logs->sum(fn ($r) => (float)($r->battery_power ?? 0));
            $sumZappi = $logs->sum(fn ($r) => (float)($r->zappi_node ?? 0));

            $dateKey = (int)$windowStart->format('Ymd');
            $timeKey = (int)($windowStart->format('H') * 100 + (int)$windowStart->format('i'));

            DB::table('agg_energy_15min')->insert([
                'date_key' => $dateKey,
                'time_key' => $timeKey,
                'window_start' => $windowStart->toDateTimeString(),
                'window_end' => $windowEnd->toDateTimeString(),
                'sum_grid_power' => $sumGrid,
                'sum_total_pv_power' => $sumPv,
                'sum_home_load_power' => $sumHome,
                'sum_battery_power' => $sumBattery,
                'sum_zappi_node' => $sumZappi,
                'sample_count' => $logs->count(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $windowStart->addMinutes(15);
            $processedMinutes += 15;
            $scannedMinutes += 15;
            // Treat max-minutes as a scan cap to avoid infinite runs when no data
            if ($maxMinutes !== null && $scannedMinutes >= $maxMinutes) {
                break;
            }
        }
    }

    private function processDaily(Carbon $fromDay, Carbon $toDay, ?int $maxDays = null): void
    {
        $cursor = $fromDay->copy()->startOfDay();
        $processedDays = 0; // days written/updated
        $scannedDays = 0;   // days scanned (including empty)
        while ($cursor->lte($toDay)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $dateKey = (int)$cursor->format('Ymd');

            $logs = EnergyFlowLog::query()
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<=', $dayEnd)
                ->get(['grid_power', 'total_pv_power', 'home_load_power', 'battery_power', 'zappi_node']);

            if ($logs->isEmpty()) {
                $cursor->addDay();
                $scannedDays++;
                if ($maxDays !== null && $scannedDays >= $maxDays) {
                    break;
                }
                continue;
            }

            $payload = [
                'date_key' => $dateKey,
                'date' => $cursor->toDateString(),
                'sum_grid_power' => $logs->sum(fn ($r) => (float)($r->grid_power ?? 0)),
                'sum_total_pv_power' => $logs->sum(fn ($r) => (float)($r->total_pv_power ?? 0)),
                'sum_home_load_power' => $logs->sum(fn ($r) => (float)($r->home_load_power ?? 0)),
                'sum_battery_power' => $logs->sum(fn ($r) => (float)($r->battery_power ?? 0)),
                'sum_zappi_node' => $logs->sum(fn ($r) => (float)($r->zappi_node ?? 0)),
                'sample_count' => $logs->count(),
                'updated_at' => now(),
                'created_at' => now(),
            ];

            DB::table('agg_energy_daily')->updateOrInsert(['date_key' => $dateKey], $payload);

            $cursor->addDay();
            $processedDays++;
            $scannedDays++;
            // Treat max-days as a scan cap to avoid traversing years of empty days
            if ($maxDays !== null && $scannedDays >= $maxDays) {
                break;
            }
        }
    }
}


