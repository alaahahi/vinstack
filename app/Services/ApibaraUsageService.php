<?php

namespace App\Services;

use App\Models\ApibaraRequestLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApibaraUsageService
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function record(
        ?User $user,
        string $endpoint,
        string $method,
        array $query,
        ?int $status,
        bool $cached,
        bool $billed,
        ?int $elapsedMs = null,
        ?string $errorCode = null,
    ): void {
        ApibaraRequestLog::query()->create([
            'user_id' => $user?->id,
            'user_role' => $user?->role?->value,
            'user_name' => $user?->name,
            'endpoint' => $endpoint,
            'method' => $method,
            'query' => $query,
            'status' => $status,
            'cached' => $cached,
            'billed' => $billed,
            'elapsed_ms' => $elapsedMs,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Local consumption summary for the current calendar month.
     *
     * @return array<string, mixed>
     */
    public function monthlySummary(?Carbon $month = null): array
    {
        $month = ($month ?? now())->copy()->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $base = ApibaraRequestLog::query()
            ->whereBetween('created_at', [$from, $to]);

        $billed = (clone $base)->where('billed', true)->count();
        $cached = (clone $base)->where('cached', true)->count();
        $total = (clone $base)->count();
        $failed = (clone $base)->where('billed', true)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '>=', 400);
        })->count();

        $byUser = ApibaraRequestLog::query()
            ->select([
                'user_id',
                'user_name',
                'user_role',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN billed = 1 THEN 1 ELSE 0 END) as billed'),
                DB::raw('SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as cached'),
            ])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('user_id', 'user_name', 'user_role')
            ->orderByDesc('billed')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->user_name ?: ('#'.$row->user_id),
                'role' => $row->user_role,
                'total' => (int) $row->total,
                'billed' => (int) $row->billed,
                'cached' => (int) $row->cached,
            ])
            ->all();

        $byEndpoint = ApibaraRequestLog::query()
            ->select([
                'endpoint',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN billed = 1 THEN 1 ELSE 0 END) as billed'),
                DB::raw('SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as cached'),
            ])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('endpoint')
            ->orderByDesc('billed')
            ->get()
            ->map(fn ($row) => [
                'endpoint' => $row->endpoint,
                'total' => (int) $row->total,
                'billed' => (int) $row->billed,
                'cached' => (int) $row->cached,
            ])
            ->all();

        $recent = ApibaraRequestLog::query()
            ->latest('id')
            ->limit(30)
            ->get(['id', 'user_name', 'user_role', 'endpoint', 'cached', 'billed', 'status', 'elapsed_ms', 'created_at'])
            ->map(fn (ApibaraRequestLog $log) => [
                'id' => $log->id,
                'user' => $log->user_name,
                'role' => $log->user_role,
                'endpoint' => $log->endpoint,
                'cached' => $log->cached,
                'billed' => $log->billed,
                'status' => $log->status,
                'elapsed_ms' => $log->elapsed_ms,
                'at' => $log->created_at?->toIso8601String(),
            ])
            ->all();

        $freeQuota = (int) config('apibara.monthly_free_quota', 100);

        return [
            'month' => $from->format('Y-m'),
            'free_quota' => $freeQuota,
            'billed' => $billed,
            'cached' => $cached,
            'total' => $total,
            'failed_billed' => $failed,
            'remaining_estimate' => max(0, $freeQuota - $billed),
            'by_user' => $byUser,
            'by_endpoint' => $byEndpoint,
            'recent' => $recent,
            'cache_ttl_seconds' => (int) config('apibara.cache_ttl', 3600),
            'max_per_page' => (int) config('apibara.max_per_page', 10),
        ];
    }
}
