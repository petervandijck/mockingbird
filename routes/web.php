<?php

use Illuminate\Support\Facades\Route;
use PeterVanDijck\Mockingbird\Http\Controllers\WebhookController;
use PeterVanDijck\Mockingbird\Http\Controllers\TestController;

Route::post(config('llamaparse.webhook_path', '/llamaparse/webhook'), [WebhookController::class, 'handle'])
    ->name('llamaparse.webhook');

Route::get('/test-llamaparse', [TestController::class, 'test'])
    ->name('llamaparse.test');

Route::get('/llamaparse-status/{jobId}', [TestController::class, 'status'])
    ->name('llamaparse.status');

Route::get('/llamaparse-result/{jobId}', [TestController::class, 'result'])
    ->name('llamaparse.result');