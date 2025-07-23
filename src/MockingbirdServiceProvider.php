<?php

namespace PeterVanDijck\Mockingbird;

use Illuminate\Support\ServiceProvider;
use PeterVanDijck\Mockingbird\Console\LlamaParseResultCommand;
use PeterVanDijck\Mockingbird\Console\LlamaParseStatusCommand;
use PeterVanDijck\Mockingbird\Console\TestLlamaParseCommand;

class MockingbirdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/llamaparse.php', 'llamaparse'
        );

        // Register the main service
        $this->app->singleton(LlamaParseService::class, function ($app) {
            return new LlamaParseService();
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/llamaparse.php' => config_path('llamaparse.php'),
        ], 'llamaparse-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/create_llama_parse_jobs_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_llama_parse_jobs_table.php'),
        ], 'llamaparse-migrations');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                LlamaParseResultCommand::class,
                LlamaParseStatusCommand::class,
                TestLlamaParseCommand::class,
            ]);
        }
    }
}