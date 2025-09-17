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
                'groq' => new GroqService,
                'gemini' => new GeminiService,
                default => new GroqService,
            };

            return $service;
        });

        // Register a fallback service
        $this->app->bind('ai.fallback', function ($app) {
            $fallbackProvider = config('services.ai.fallback_provider', 'gemini');

            return match ($fallbackProvider) {
                'groq' => new GroqService,
                'gemini' => new GeminiService,
                default => new GeminiService,
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
