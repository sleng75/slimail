<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\PlanManagementController;
use App\Http\Controllers\Admin\SystemMonitoringController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\ContactListImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\MockEmailController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\DuplicateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// API Documentation (public)
Route::get('/api/docs', [DocumentationController::class, 'index'])->name('api.docs');

// Tracking routes (public, no auth required)
Route::get('/track/open/{sentEmailId}', [TrackingController::class, 'trackOpen'])->name('track.open');
Route::get('/track/click/{sentEmailId}', [TrackingController::class, 'trackClick'])->name('track.click');
Route::match(['get', 'post'], '/unsubscribe', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');

// Webhook routes (public, no auth required)
Route::post('/webhooks/cinetpay', [WebhookController::class, 'cinetpay'])->name('webhooks.cinetpay');

// Guest routes (non-authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated routes with tenant check
Route::middleware(['auth', 'tenant.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/read', [NotificationController::class, 'destroyRead'])->name('notifications.destroy-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile routes (all authenticated users)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings routes (owner/admin only)
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/logo', [SettingsController::class, 'updateLogo'])->name('settings.logo');
        Route::delete('/settings/logo', [SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
        Route::get('/settings/email', [SettingsController::class, 'emailSettings'])->name('settings.email');
        Route::put('/settings/email', [SettingsController::class, 'updateEmailSettings'])->name('settings.email.update');
    });

    // Contacts routes
    Route::middleware('permission:contacts.view')->group(function () {
        // Import/Export - MUST be before resource routes
        Route::get('/contacts/import', [ContactImportController::class, 'showImportForm'])->name('contacts.import');
        Route::post('/contacts/import', [ContactImportController::class, 'import'])->name('contacts.import.process');
        Route::post('/contacts/import/process', [ContactImportController::class, 'import']); // Alias for tests
        Route::post('/contacts/import/upload', [ContactImportController::class, 'upload'])->name('contacts.import.upload');
        Route::post('/contacts/import/preview', [ContactImportController::class, 'preview'])->name('contacts.import.preview');
        Route::get('/contacts/import/template', [ContactImportController::class, 'downloadTemplate'])->name('contacts.import.template');
        Route::get('/contacts/export', [ContactImportController::class, 'export'])->name('contacts.export');

        // Duplicates - MUST be before resource routes
        Route::get('/contacts/duplicates', [DuplicateController::class, 'index'])->name('contacts.duplicates');
        Route::post('/contacts/duplicates/merge', [DuplicateController::class, 'merge'])->name('contacts.duplicates.merge');
        Route::post('/contacts/duplicates/check', [DuplicateController::class, 'checkSingle'])->name('contacts.duplicates.check');
        Route::get('/contacts/duplicates/stats', [DuplicateController::class, 'stats'])->name('contacts.duplicates.stats');

        // Bulk actions - MUST be before resource routes
        Route::post('/contacts/bulk-destroy', [ContactController::class, 'bulkDestroy'])->name('contacts.bulk-destroy');
        Route::post('/contacts/bulk-add-to-list', [ContactController::class, 'bulkAddToList'])->name('contacts.bulk-add-to-list');
        Route::post('/contacts/bulk-remove-from-list', [ContactController::class, 'bulkRemoveFromList'])->name('contacts.bulk-remove-from-list');
        Route::post('/contacts/bulk-add-tags', [ContactController::class, 'bulkAddTags'])->name('contacts.bulk-add-tags');
        Route::post('/contacts/bulk-remove-tags', [ContactController::class, 'bulkRemoveTags'])->name('contacts.bulk-remove-tags');

        // Resource routes
        Route::resource('contacts', ContactController::class);

        // Contact Lists - Import/Export MUST be before resource routes
        Route::get('/contact-lists/import/template', [ContactListImportController::class, 'downloadTemplate'])->name('contact-lists.import.template');
        Route::get('/contact-lists/{contact_list}/import', [ContactListImportController::class, 'showImportForm'])->name('contact-lists.import');
        Route::post('/contact-lists/{contact_list}/import', [ContactListImportController::class, 'import'])->name('contact-lists.import.process');
        Route::post('/contact-lists/import/preview', [ContactListImportController::class, 'preview'])->name('contact-lists.import.preview');
        Route::get('/contact-lists/{contact_list}/export', [ContactListImportController::class, 'export'])->name('contact-lists.export');

        // Contact Lists Resource
        Route::resource('contact-lists', ContactListController::class);

        // Tags
        Route::resource('tags', TagController::class)->except(['create', 'show', 'edit']);

        // Segments
        Route::post('/segments/preview', [SegmentController::class, 'preview'])->name('segments.preview');
        Route::get('/segments/operators', [SegmentController::class, 'getOperators'])->name('segments.operators');
        Route::post('/segments/{segment}/refresh-count', [SegmentController::class, 'refreshCount'])->name('segments.refresh-count');
        Route::get('/segments/{segment}/export', [SegmentController::class, 'export'])->name('segments.export');
        Route::post('/segments/{segment}/duplicate', [SegmentController::class, 'duplicate'])->name('segments.duplicate');
        Route::resource('segments', SegmentController::class);
    });

    // Campaigns routes
    Route::middleware('permission:campaigns.view')->group(function () {
        // Campaign wizard steps
        Route::put('/campaigns/{campaign}/config', [CampaignController::class, 'updateConfig'])->name('campaigns.update-config');
        Route::put('/campaigns/{campaign}/recipients', [CampaignController::class, 'updateRecipients'])->name('campaigns.update-recipients');
        Route::put('/campaigns/{campaign}/content', [CampaignController::class, 'updateContent'])->name('campaigns.update-content');
        Route::put('/campaigns/{campaign}/variants', [CampaignController::class, 'updateVariants'])->name('campaigns.update-variants');

        // Campaign actions
        Route::post('/campaigns/{campaign}/send-test', [CampaignController::class, 'sendTest'])->name('campaigns.send-test');
        Route::post('/campaigns/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
        Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
        Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');

        // Campaign preview & stats
        Route::get('/campaigns/{campaign}/preview', [CampaignController::class, 'preview'])->name('campaigns.preview');
        Route::get('/campaigns/{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');

        // A/B Test actions
        Route::put('/campaigns/{campaign}/ab-test', [CampaignController::class, 'updateAbTest'])->name('campaigns.update-ab-test');
        Route::post('/campaigns/{campaign}/ab-test/select-winner', [CampaignController::class, 'selectWinner'])->name('campaigns.select-winner');
        Route::post('/campaigns/{campaign}/ab-test/send-remaining', [CampaignController::class, 'sendToRemaining'])->name('campaigns.send-remaining');

        // Resource routes
        Route::resource('campaigns', CampaignController::class);
    });

    // Templates routes
    Route::middleware('permission:templates.manage')->group(function () {
        // Template library
        Route::get('/templates/library', [EmailTemplateController::class, 'library'])->name('templates.library');
        Route::post('/templates/{template}/use', [EmailTemplateController::class, 'useFromLibrary'])->name('templates.use');

        // Template actions
        Route::post('/templates/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::post('/templates/{template}/autosave', [EmailTemplateController::class, 'autosave'])->name('templates.autosave');
        Route::get('/templates/{template}/preview', [EmailTemplateController::class, 'preview'])->name('templates.preview');

        // Resource routes
        Route::resource('templates', EmailTemplateController::class);
    });

    // Automations routes
    Route::middleware('permission:automations.manage')->group(function () {
        // Automation actions
        Route::post('/automations/{automation}/activate', [AutomationController::class, 'activate'])->name('automations.activate');
        Route::post('/automations/{automation}/pause', [AutomationController::class, 'pause'])->name('automations.pause');
        Route::post('/automations/{automation}/duplicate', [AutomationController::class, 'duplicate'])->name('automations.duplicate');
        Route::post('/automations/{automation}/save-workflow', [AutomationController::class, 'saveWorkflow'])->name('automations.save-workflow');

        // Enrollments management
        Route::get('/automations/{automation}/enrollments', [AutomationController::class, 'enrollments'])->name('automations.enrollments');
        Route::post('/automations/{automation}/enroll-contacts', [AutomationController::class, 'enrollContacts'])->name('automations.enroll-contacts');
        Route::delete('/automations/{automation}/enrollments/{enrollment}', [AutomationController::class, 'removeEnrollment'])->name('automations.remove-enrollment');

        // Logs
        Route::get('/automations/{automation}/logs', [AutomationController::class, 'logs'])->name('automations.logs');

        // Resource routes
        Route::resource('automations', AutomationController::class);
    });

    // Statistics routes
    Route::middleware('permission:statistics.view')->group(function () {
        Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('/statistics/data', [StatisticsController::class, 'getData'])->name('statistics.data');
        Route::get('/statistics/export', [StatisticsController::class, 'exportCsv'])->name('statistics.export');
        Route::get('/statistics/export-pdf', [StatisticsController::class, 'exportPdf'])->name('statistics.export-pdf');
        Route::get('/statistics/campaign/{campaign}', [StatisticsController::class, 'campaign'])->name('statistics.campaign');
        Route::get('/statistics/campaign/{campaign}/export', [StatisticsController::class, 'exportCampaignCsv'])->name('statistics.campaign.export');
        Route::get('/statistics/campaign/{campaign}/export-pdf', [StatisticsController::class, 'exportCampaignPdf'])->name('statistics.campaign.export-pdf');
    });

    // API settings routes
    Route::middleware('permission:api.manage')->group(function () {
        Route::get('/api-settings', [ApiKeyController::class, 'index'])->name('api-settings.index');
        Route::post('/api-settings', [ApiKeyController::class, 'store'])->name('api-settings.store');
        Route::put('/api-settings/{apiKey}', [ApiKeyController::class, 'update'])->name('api-settings.update');
        Route::delete('/api-settings/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-settings.destroy');
        Route::post('/api-settings/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-settings.regenerate');
        Route::post('/api-settings/{apiKey}/toggle', [ApiKeyController::class, 'toggle'])->name('api-settings.toggle');
    });

    // Users management (admin only)
    Route::middleware('permission:users.manage')->group(function () {
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
        Route::post('/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');
        Route::resource('users', UserController::class);
    });

    // Billing (owner only)
    Route::middleware('permission:billing.manage')->prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
        Route::post('/subscribe/{plan}', [BillingController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel', [BillingController::class, 'cancel'])->name('cancel');
        Route::get('/payment/{payment}/return', [BillingController::class, 'paymentReturn'])->name('payment.return');
        Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [BillingController::class, 'showInvoice'])->name('invoices.show');
        Route::post('/invoices/{invoice}/pay', [BillingController::class, 'payInvoice'])->name('invoices.pay');
        Route::get('/invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])->name('invoices.download');
        Route::get('/invoices/{invoice}/pdf', [BillingController::class, 'viewInvoicePdf'])->name('invoices.pdf');
        Route::get('/payments', [BillingController::class, 'payments'])->name('payments');
    });

    // Development tools (only in local environment)
    if (app()->environment('local')) {
        Route::prefix('dev')->name('dev.')->group(function () {
            Route::get('/mock-emails', [MockEmailController::class, 'index'])->name('mock-emails.index');
            Route::post('/mock-emails/send-test', [MockEmailController::class, 'sendTest'])->name('mock-emails.send-test');
            Route::post('/mock-emails/{sentEmail}/simulate-event', [MockEmailController::class, 'simulateEvent'])->name('mock-emails.simulate-event');
            Route::get('/mock-emails/status', [MockEmailController::class, 'status'])->name('mock-emails.status');
        });
    }
});

// Super Admin Routes
Route::middleware(['auth', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Tenant Management
    Route::get('/tenants', [TenantManagementController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [TenantManagementController::class, 'show'])->name('tenants.show');
    Route::put('/tenants/{tenant}', [TenantManagementController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/change-subscription', [TenantManagementController::class, 'changeSubscription'])->name('tenants.change-subscription');
    Route::post('/tenants/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [TenantManagementController::class, 'reactivate'])->name('tenants.reactivate');
    Route::delete('/tenants/{tenant}', [TenantManagementController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/tenants/{tenant}/impersonate', [TenantManagementController::class, 'impersonate'])->name('tenants.impersonate');

    // Plan Management
    Route::get('/plans', [PlanManagementController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanManagementController::class, 'create'])->name('plans.create');
    Route::post('/plans', [PlanManagementController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [PlanManagementController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [PlanManagementController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [PlanManagementController::class, 'destroy'])->name('plans.destroy');
    Route::post('/plans/reorder', [PlanManagementController::class, 'reorder'])->name('plans.reorder');

    // System Monitoring
    Route::get('/monitoring', [SystemMonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/ses', [SystemMonitoringController::class, 'ses'])->name('monitoring.ses');
    Route::get('/monitoring/queues', [SystemMonitoringController::class, 'queues'])->name('monitoring.queues');
    Route::post('/monitoring/queues/retry/{uuid}', [SystemMonitoringController::class, 'retryJob'])->name('monitoring.queues.retry');
    Route::delete('/monitoring/queues/{uuid}', [SystemMonitoringController::class, 'deleteJob'])->name('monitoring.queues.delete');
    Route::post('/monitoring/queues/flush', [SystemMonitoringController::class, 'flushFailedJobs'])->name('monitoring.queues.flush');
    Route::post('/monitoring/cache/clear', [SystemMonitoringController::class, 'clearCache'])->name('monitoring.cache.clear');

    // Stop impersonation
    Route::post('/stop-impersonating', function () {
        $originalUserId = session('impersonating_from');
        if ($originalUserId) {
            session()->forget('impersonating_from');
            auth()->loginUsingId($originalUserId);
            return redirect()->route('admin.dashboard')->with('success', 'Retour au compte admin.');
        }
        return redirect()->route('admin.dashboard');
    })->name('stop-impersonating');
});
