<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        try {
            $checks = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
            ];

            $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

            $response = [
                'status' => $allHealthy ? 'ok' : 'error',
                'timestamp' => now()->toISOString(),
                'checks' => $checks,
            ];

            $statusCode = $allHealthy ? 200 : 503;

            if (! $allHealthy) {
                Log::warning('Health check failed', $response);
            }

            return response()->json($response, $statusCode);

        } catch (\Exception $e) {
            Log::error('Health check exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'error' => 'Health check failed',
            ], 503);
        }
    }

    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'message' => 'Service is alive',
        ]);
    }

    public function readiness(): JsonResponse
    {
        try {
            // Quick database check
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toISOString(),
                'message' => 'Service is ready to receive traffic',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'timestamp' => now()->toISOString(),
                'message' => 'Service is not ready to receive traffic',
            ], 503);
        }
    }

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $duration,
                'message' => 'Database connection successful',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'health_check_'.time();
            $value = 'test_value';

            $start = microtime(true);
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $value) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $duration,
                    'message' => 'Cache is working',
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache value mismatch',
                ];
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/health_check_test.txt');
            $testContent = 'health_check_'.time();

            $start = microtime(true);
            file_put_contents($testFile, $testContent);
            $retrieved = file_get_contents($testFile);
            unlink($testFile);
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $duration,
                    'message' => 'Storage is writable',
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Storage content mismatch',
                ];
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
