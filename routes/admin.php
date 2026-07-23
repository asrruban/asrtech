<?php

use App\Http\Controllers\Admin\AiProductContentController;
use App\Http\Controllers\Admin\AiProductIconController;
use App\Http\Controllers\Admin\AiSeoController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentationController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GatewaySettingController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentReliabilityController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductReleaseController;
use App\Http\Controllers\Admin\ProductReviewController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\PromotionCodeController;
use App\Http\Controllers\Admin\RefundRequestController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\StorageSettingController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\TicketDepartmentController;
use App\Http\Controllers\Admin\TicketDepartmentFieldController;
use App\Http\Controllers\Admin\TwoFactorChallengeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthController::class, 'store'])
        ->middleware('throttle:auth-login')
        ->name('login.store');
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('two-factor.challenge.store');

    Route::middleware(['auth:admin', 'admin.audit'])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('docs', DocumentationController::class)->name('docs.index');
        Route::get('security', [SecurityController::class, 'index'])->name('security.index');
        Route::post('security/two-factor/setup', [SecurityController::class, 'setup'])->name('security.two-factor.setup');
        Route::post('security/two-factor/confirm', [SecurityController::class, 'confirm'])->name('security.two-factor.confirm');
        Route::delete('security/two-factor', [SecurityController::class, 'disable'])->name('security.two-factor.disable');
        Route::post('security/two-factor/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('security.two-factor.recovery-codes');
        Route::patch('security/admins/{admin}/role', [SecurityController::class, 'updateRole'])
            ->middleware('admin.permission:admins.manage')
            ->name('security.admins.role');
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:catalog.manage');
        Route::resource('subcategories', GroupController::class)
            ->parameters(['subcategories' => 'group'])
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:catalog.manage');
        Route::resource('groups', GroupController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:catalog.manage');
        Route::post('seo/generate', AiSeoController::class)
            ->middleware(['admin.permission:catalog.manage', 'throttle:10,1'])
            ->name('seo.generate');
        Route::post('products/ai/content', AiProductContentController::class)
            ->middleware(['admin.permission:catalog.manage', 'throttle:10,1'])
            ->name('products.ai.content');
        Route::post('products/ai/icon', AiProductIconController::class)
            ->middleware(['admin.permission:catalog.manage', 'throttle:5,1'])
            ->name('products.ai.icon');
        Route::resource('products', ProductController::class)
            ->except(['show'])
            ->middleware('admin.permission:catalog.manage');
        Route::get('products/{product}/releases', [ProductReleaseController::class, 'index'])
            ->middleware('admin.permission:catalog.manage')
            ->name('products.releases.index');
        Route::post('products/{product}/releases', [ProductReleaseController::class, 'store'])
            ->middleware('admin.permission:catalog.manage')
            ->name('products.releases.store');
        Route::put('products/{product}/releases/{release}', [ProductReleaseController::class, 'update'])
            ->middleware('admin.permission:catalog.manage')
            ->name('products.releases.update');
        Route::delete('products/{product}/releases/{release}', [ProductReleaseController::class, 'destroy'])
            ->middleware('admin.permission:catalog.manage')
            ->name('products.releases.destroy');
        Route::get('product-reviews', [ProductReviewController::class, 'index'])
            ->middleware('admin.permission:catalog.manage')
            ->name('product-reviews.index');
        Route::patch('product-reviews/{review}', [ProductReviewController::class, 'update'])
            ->middleware('admin.permission:catalog.manage')
            ->name('product-reviews.update');
        Route::resource('product-types', ProductTypeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:catalog.manage');
        Route::resource('pages', PageController::class)
            ->except(['show'])
            ->middleware('admin.permission:content.manage');
        Route::get('users', [UserController::class, 'index'])->middleware('admin.permission:users.view')->name('users.index');
        Route::post('users', [UserController::class, 'store'])->middleware('admin.permission:users.manage')->name('users.store');
        Route::get('users/{user}/invoice/{invoice}', [InvoiceController::class, 'manage'])->middleware('admin.permission:billing.manage')->name('users.invoice');
        Route::get('users/{user}/{tab?}', [UserController::class, 'show'])
            ->where('tab', 'summary|profile|services|invoices|orders')
            ->middleware('admin.permission:users.view')
            ->name('users.show');
        Route::patch('users/{user}', [UserController::class, 'update'])->middleware('admin.permission:users.manage')->name('users.update');
        Route::patch('users/{user}/notes', [UserController::class, 'updateNotes'])->middleware('admin.permission:users.manage')->name('users.notes');
        Route::post('users/{user}/orders', [UserOrderController::class, 'store'])->middleware('admin.permission:billing.manage')->name('users.orders.store');
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])->middleware('admin.permission:admins.manage')->name('users.impersonate');
        Route::post('orders/{order}/invoice', [InvoiceController::class, 'store'])->middleware('admin.permission:billing.manage')->name('orders.invoice.store');
        Route::get('licenses/{license}', [LicenseController::class, 'show'])->middleware('admin.permission:licenses.view')->name('licenses.show');
        Route::patch('licenses/{license}', [LicenseController::class, 'update'])->middleware('admin.permission:licenses.manage')->name('licenses.update');
        Route::get('invoices', [InvoiceController::class, 'index'])->middleware('admin.permission:billing.manage')->name('invoices.index');
        Route::get('refund-requests', [RefundRequestController::class, 'index'])->middleware('admin.permission:billing.manage')->name('refund-requests.index');
        Route::get('refund-requests/{refundRequest}', [RefundRequestController::class, 'show'])->middleware('admin.permission:billing.manage')->name('refund-requests.show');
        Route::post('refund-requests/{refundRequest}/approve', [RefundRequestController::class, 'approve'])->middleware('admin.permission:billing.manage')->name('refund-requests.approve');
        Route::post('refund-requests/{refundRequest}/reject', [RefundRequestController::class, 'reject'])->middleware('admin.permission:billing.manage')->name('refund-requests.reject');
        Route::get('payments', [PaymentReliabilityController::class, 'index'])->middleware('admin.permission:billing.manage')->name('payments.index');
        Route::get('payments/webhooks/{webhookEvent}', [PaymentReliabilityController::class, 'show'])->middleware('admin.permission:billing.manage')->name('payments.webhooks.show');
        Route::post('payments/webhooks/{webhookEvent}/replay', [PaymentReliabilityController::class, 'replay'])->middleware('admin.permission:billing.manage')->name('payments.webhooks.replay');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->middleware('admin.permission:billing.manage')->name('subscriptions.index');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->middleware('admin.permission:billing.manage')->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->middleware('admin.permission:billing.manage')->name('subscriptions.cancel');
        Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->middleware('admin.permission:billing.manage')->name('subscriptions.resume');
        Route::resource('promotions', PromotionCodeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:billing.manage');
        Route::resource('tax-rates', TaxRateController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('admin.permission:billing.manage');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('admin.permission:billing.manage')->name('invoices.show');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->middleware('admin.permission:billing.manage')->name('invoices.download');
        Route::get('credit-notes/{creditNote}/download', [CreditNoteController::class, 'download'])->middleware('admin.permission:billing.manage')->name('credit-notes.download');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->middleware('admin.permission:billing.manage')->name('invoices.pay');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->middleware('admin.permission:billing.manage')->name('invoices.void');
        Route::post('invoices/{invoice}/add-payment', [InvoiceController::class, 'addPayment'])->middleware('admin.permission:billing.manage')->name('invoices.payment');
        Route::post('invoices/{invoice}/refund', [InvoiceController::class, 'refund'])->middleware('admin.permission:billing.manage')->name('invoices.refund');
        Route::patch('invoices/{invoice}/notes', [InvoiceController::class, 'updateNotes'])->middleware('admin.permission:billing.manage')->name('invoices.notes');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->middleware('admin.permission:billing.manage')->name('invoices.send');
        Route::get('support/tickets', [SupportTicketController::class, 'index'])->middleware('admin.permission:support.manage')->name('support.tickets.index');
        Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])->middleware('admin.permission:support.manage')->name('support.tickets.show');
        Route::post('support/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->middleware('admin.permission:support.manage')->name('support.tickets.reply');
        Route::patch('support/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->middleware('admin.permission:support.manage')->name('support.tickets.status');
        Route::delete('support/tickets/{ticket}', [SupportTicketController::class, 'destroy'])->middleware('admin.permission:support.manage')->name('support.tickets.destroy');
        Route::get('support/departments', [TicketDepartmentController::class, 'index'])->middleware('admin.permission:support.manage')->name('support.departments.index');
        Route::get('support/departments/create', [TicketDepartmentController::class, 'create'])->middleware('admin.permission:support.manage')->name('support.departments.create');
        Route::post('support/departments', [TicketDepartmentController::class, 'store'])->middleware('admin.permission:support.manage')->name('support.departments.store');
        Route::post('support/departments/test-mail', [TicketDepartmentController::class, 'testMail'])->middleware('admin.permission:support.manage')->name('support.departments.test-mail');
        Route::get('support/departments/{department}/edit', [TicketDepartmentController::class, 'edit'])->middleware('admin.permission:support.manage')->name('support.departments.edit');
        Route::put('support/departments/{department}', [TicketDepartmentController::class, 'update'])->middleware('admin.permission:support.manage')->name('support.departments.update');
        Route::delete('support/departments/{department}', [TicketDepartmentController::class, 'destroy'])->middleware('admin.permission:support.manage')->name('support.departments.destroy');
        Route::post('support/departments/{department}/move', [TicketDepartmentController::class, 'move'])->middleware('admin.permission:support.manage')->name('support.departments.move');
        Route::post('support/departments/{department}/fields', [TicketDepartmentFieldController::class, 'store'])->middleware('admin.permission:support.manage')->name('support.departments.fields.store');
        Route::put('support/departments/{department}/fields/{field}', [TicketDepartmentFieldController::class, 'update'])->scopeBindings()->middleware('admin.permission:support.manage')->name('support.departments.fields.update');
        Route::delete('support/departments/{department}/fields/{field}', [TicketDepartmentFieldController::class, 'destroy'])->scopeBindings()->middleware('admin.permission:support.manage')->name('support.departments.fields.destroy');
        // Site & Branding merged into General Configuration.
        Route::redirect('settings', '/admin/settings/general')->name('settings.edit');
        Route::get('settings/general', [GeneralSettingController::class, 'edit'])->middleware('admin.permission:settings.manage')->name('settings.general.edit');
        Route::put('settings/general', [GeneralSettingController::class, 'update'])->middleware('admin.permission:settings.manage')->name('settings.general.update');
        Route::post('settings/branding', [BrandingController::class, 'store'])->middleware('admin.permission:settings.manage')->name('settings.branding');
        Route::get('settings/gateways', [GatewaySettingController::class, 'edit'])->middleware('admin.permission:billing.manage')->name('settings.gateways.edit');
        Route::post('settings/gateways/{gateway}/activate', [GatewaySettingController::class, 'activate'])->middleware('admin.permission:billing.manage')->name('settings.gateways.activate');
        Route::delete('settings/gateways/{gateway}', [GatewaySettingController::class, 'deactivate'])->middleware('admin.permission:billing.manage')->name('settings.gateways.deactivate');
        Route::put('settings/gateways/{gateway}', [GatewaySettingController::class, 'update'])->middleware('admin.permission:billing.manage')->name('settings.gateways.update');
        Route::get('settings/emailtemplates', [EmailTemplateController::class, 'index'])->middleware('admin.permission:settings.manage')->name('settings.emailtemplates.index');
        Route::post('settings/emailtemplates', [EmailTemplateController::class, 'store'])->middleware('admin.permission:settings.manage')->name('settings.emailtemplates.store');
        Route::get('settings/emailtemplates/{emailtemplate}', [EmailTemplateController::class, 'edit'])->middleware('admin.permission:settings.manage')->name('settings.emailtemplates.edit');
        Route::put('settings/emailtemplates/{emailtemplate}', [EmailTemplateController::class, 'update'])->middleware('admin.permission:settings.manage')->name('settings.emailtemplates.update');
        Route::delete('settings/emailtemplates/{emailtemplate}', [EmailTemplateController::class, 'destroy'])->middleware('admin.permission:settings.manage')->name('settings.emailtemplates.destroy');
        Route::get('settings/storage', [StorageSettingController::class, 'edit'])->middleware('admin.permission:settings.manage')->name('settings.storage.edit');
        Route::put('settings/storage', [StorageSettingController::class, 'update'])->middleware('admin.permission:settings.manage')->name('settings.storage.update');
        Route::get('settings/seo', [SeoSettingController::class, 'edit'])->middleware('admin.permission:settings.manage')->name('settings.seo.edit');
        Route::put('settings/seo', [SeoSettingController::class, 'update'])->middleware('admin.permission:settings.manage')->name('settings.seo.update');
        Route::post('logout', [AuthController::class, 'destroy'])
            ->name('logout');
    });
});
