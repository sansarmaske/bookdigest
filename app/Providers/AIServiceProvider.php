<?php

namespace App\Providers;

use App\Services\GroqService;
use App\Services\GeminiService;
use App\Contracts\AIServiceInterface;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register individual AI services
        $this->app->bind(GeminiService::class, function ($app) {
            return new GeminiService;
        });

        $this->app->bind(GroqService::class, function ($app) {
            return new GroqService;
        });

        // Register the primary AI service interface based on configuration
        $this->app->bind(AIServiceInterface::class, function ($app) {
            $defaultProvider = config('services.ai.default_provider', 'groq');

            $service = match ($defaultProvider) {
                'groq' => $app->make(GroqService::class),
                'gemini' => $app->make(GeminiService::class),
                default => $app->make(GroqService::class),
            };

            // Log which provider is being used
            \Illuminate\Support\Facades\Log::info('AI Service Provider Resolved', [
                'configured_provider' => $defaultProvider,
                'actual_provider' => $service->getProviderName(),
                'service_available' => $service->isAvailable(),
            ]);

            return $service;
        });

        // Register a fallback service
        $this->app->bind('ai.fallback', function ($app) {
            $fallbackProvider = config('services.ai.fallback_provider', 'gemini');

            return match ($fallbackProvider) {
                'groq' => $app->make(GroqService::class),
                'gemini' => $app->make(GeminiService::class),
                default => $app->make(GeminiService::class),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
