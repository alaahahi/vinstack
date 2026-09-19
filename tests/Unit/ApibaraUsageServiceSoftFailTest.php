<?php

namespace Tests\Unit;

use App\Models\ApibaraRequestLog;
use App\Services\ApibaraUsageService;
use App\Services\AuctionApiProviderService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PDOException;
use Tests\TestCase;

class ApibaraUsageServiceSoftFailTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_does_not_throw_when_create_hits_sqlite_lock(): void
    {
        ApibaraRequestLog::creating(function (): void {
            throw new QueryException(
                'sqlite',
                'insert into "apibara_request_logs"',
                [],
                new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
            );
        });

        $providers = Mockery::mock(AuctionApiProviderService::class);
        $service = new ApibaraUsageService($providers);

        $service->record(
            user: null,
            endpoint: '/api/search',
            method: 'GET',
            query: ['q' => 'camry'],
            status: 200,
            cached: true,
            billed: false,
            elapsedMs: 12,
        );

        $this->assertSame(0, ApibaraRequestLog::query()->count());
    }

    public function test_record_writes_log_when_database_is_available(): void
    {
        $providers = Mockery::mock(AuctionApiProviderService::class);
        $service = new ApibaraUsageService($providers);

        $service->record(
            user: null,
            endpoint: '/api/search',
            method: 'GET',
            query: ['q' => 'camry'],
            status: 200,
            cached: true,
            billed: false,
            elapsedMs: 12,
        );

        $this->assertDatabaseHas('apibara_request_logs', [
            'endpoint' => '/api/search',
            'cached' => 1,
            'billed' => 0,
        ]);
    }
}
