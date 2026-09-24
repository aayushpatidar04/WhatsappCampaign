<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignExportController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\WhatsappAccountController;
use App\Http\Controllers\CampaignTemplateController;
use Illuminate\Support\Facades\Route;

// Auth Routes (No middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Webhook Routes (No auth, no CSRF)
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhook.whatsapp.receive');

// Protected Routes (Require auth)
Route::middleware('simple.auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('campaigns.index');
    });

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::get('campaigns/{campaign}/analytics', [CampaignController::class, 'analytics'])->name('campaigns.analytics');

    // Export Routes
    Route::get('campaigns/{campaign}/export/csv', [CampaignExportController::class, 'exportCsv'])->name('campaigns.export.csv');
    Route::get('campaigns/{campaign}/export/json', [CampaignExportController::class, 'exportJson'])->name('campaigns.export.json');

    // Admin: WhatsApp Accounts
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('accounts', WhatsappAccountController::class);
        Route::resource('templates', CampaignTemplateController::class);
        Route::get('accounts/{account}/templates', [WhatsappAccountController::class, 'templates'])->name('accounts.templates');
        Route::post('parse-excel', [CampaignController::class, 'parseExcel'])->name('parse-excel');
    });
});
