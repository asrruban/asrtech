<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Api\LicenseVerifyController;
use App\Http\Controllers\Client\AccountController;
use App\Http\Controllers\Client\AccountDetailsController;
use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\CartCheckoutController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\CartPromotionController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\CreditNoteController;
use App\Http\Controllers\Client\EmailVerificationController;
use App\Http\Controllers\Client\GatewayCallbackController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\LicenseReissueController;
use App\Http\Controllers\Client\ProductDocumentationController;
use App\Http\Controllers\Client\ProductReleaseDownloadController;
use App\Http\Controllers\Client\ProductReviewController;
use App\Http\Controllers\Client\PublicPageController;
use App\Http\Controllers\Client\PublicSupportController;
use App\Http\Controllers\Client\RefundRequestController;
use App\Http\Controllers\Client\SocialAuthController;
use App\Http\Controllers\Client\SoftwareDevelopmentController;
use App\Http\Controllers\Client\StorefrontProductController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Http\Controllers\Client\SupportTicketController;
use App\Models\Order;
use App\Payments\GatewayRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('products', [StorefrontProductController::class, 'index'])->name('products.index');
Route::get('categories/{category:slug}', [StorefrontProductController::class, 'category'])
    ->name('categories.show');
Route::get('categories/{category:slug}/{group:slug}', [StorefrontProductController::class, 'subcategory'])
    ->scopeBindings()
    ->name('subcategories.show');
Route::get('products/{productType:slug}/{product:slug}/documentation', ProductDocumentationController::class)
    ->scopeBindings()
    ->name('products.documentation');
Route::get('products/{product:slug}/documentation', [StorefrontProductController::class, 'legacyDocumentation'])
    ->name('products.documentation.legacy');
Route::get('products/{productType:slug}/{product:slug}', [StorefrontProductController::class, 'show'])
    ->scopeBindings()
    ->name('products.show');
Route::get('products/{product:slug}', [StorefrontProductController::class, 'legacyShow'])
    ->name('products.show.legacy');
Route::get('cart', [CartController::class, 'index'])->name('cart.index');
Route::post('cart/{product:slug}/prices/{price}', [CartController::class, 'store'])
    ->scopeBindings()
    ->name('cart.store');
Route::delete('cart/items/{price}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('cart', [CartController::class, 'clear'])->name('cart.clear');
Route::post('cart/promotion', [CartPromotionController::class, 'store'])->name('cart.promotion.store');
Route::delete('cart/promotion', [CartPromotionController::class, 'destroy'])->name('cart.promotion.destroy');
Route::get('support', [PublicSupportController::class, 'index'])->name('support.center');
Route::get('support/ticket', [PublicSupportController::class, 'ticket'])->name('support.departments');
Route::get('software-development', SoftwareDevelopmentController::class)->name('software-development');
Route::get('pages/{page:slug}', PublicPageController::class)->name('pages.show');
Route::get('{legalPage}', [PublicPageController::class, 'legal'])
    ->whereIn('legalPage', ['terms-of-service', 'privacy-policy', 'refund-policy'])
    ->name('legal.show');

Route::post('api/license/verify', LicenseVerifyController::class)
    ->middleware('throttle:60,1')
    ->name('license.verify');

Route::post('gateways/callback/{gateway}', [GatewayCallbackController::class, 'webhook'])
    ->name('gateways.webhook');
Route::get('gateways/return/{gateway}', [GatewayCallbackController::class, 'return'])
    ->name('gateways.return');
Route::get('gateways/mock-checkout/{gateway}', function (Request $request, string $gateway) {
    abort_unless(app(GatewayRegistry::class)->find($gateway) !== null, 404);

    $orderId = filter_var($request->query('order_id'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($orderId === false) {
        abort(404);
    }

    $order = Order::query()->findOrFail($orderId);
    $order->loadMissing('product');

    $successUrl = e(route('gateways.return', $gateway).'?order_id='.$order->id);
    $cancelUrl = $order->items->count() > 1
        ? route('cart.index')
        : route('products.show', $order->product->storefrontRouteParameters());
    $cancelUrl = e($cancelUrl);
    $gatewayName = e($gateway);
    $gatewayInitials = e(strtoupper(substr($gateway, 0, 2)));
    $orderNumber = e($order->order_number);
    $productName = e($order->product->name);
    $billingCycle = e($order->billing_cycle->value);
    $amount = e(number_format($order->totalAmount(), 2, '.', ''));

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Payment Gateway - Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full mx-4 bg-white rounded-2xl shadow-xl border border-slate-100 p-8 text-center">
        <div class="flex justify-center mb-6">
            <div class="size-16 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600 font-extrabold text-2xl uppercase">
                {$gatewayInitials}
            </div>
        </div>
        
        <h1 class="text-2xl font-extrabold text-slate-800 capitalize">{$gatewayName} Sandbox Checkout</h1>
        <p class="text-slate-500 text-sm mt-2">You are paying for Order <strong class="text-slate-800">#{$orderNumber}</strong></p>
        
        <div class="my-8 p-4 rounded-xl bg-slate-50 text-left border border-slate-100 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Product</span>
                <span class="font-bold text-slate-800">{$productName}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Billing cycle</span>
                <span class="font-bold text-slate-800 capitalize">{$billingCycle}</span>
            </div>
            <div class="border-t border-slate-200/60 pt-3 flex justify-between">
                <span class="font-bold text-slate-800">Total Amount</span>
                <span class="font-extrabold text-lg text-blue-600">\${$amount}</span>
            </div>
        </div>
        
        <div class="space-y-3">
            <a href="{$successUrl}" class="w-full inline-flex h-12 items-center justify-center rounded-xl bg-[#5cb85c] text-white font-bold hover:bg-[#4cae4c] transition shadow-lg shadow-[#5cb85c]/25">
                Simulate Successful Payment
            </a>
            <a href="{$cancelUrl}" class="w-full inline-flex h-12 items-center justify-center rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition">
                Cancel & Go Back
            </a>
        </div>
        
        <p class="text-[10px] text-slate-400 mt-6">This is a sandbox checkout simulation. No real money will be charged.</p>
    </div>
</body>
</html>
HTML;

    return response($html);
})->where('gateway', '[a-z0-9_-]+')->name('gateways.mock-checkout');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])
        ->middleware('throttle:auth-login')
        ->name('login.store');
    Route::get('register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('register', [AuthController::class, 'storeRegister'])
        ->middleware('throttle:auth-login')
        ->name('register.store');
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('verify-email', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verification.verify');
    Route::post('verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.resend');
});

// Old client-area URLs redirect; the targets enforce authentication.
Route::redirect('account', '/client-area');
Route::redirect('settings', '/client-area/account-details');
Route::redirect('settings/profile', '/client-area/account-details');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    // WHMCS-style client area.
    Route::get('client-area', [AccountController::class, 'index'])->name('account.index');
    Route::get('client-area/products', [AccountController::class, 'products'])->name('account.products');
    Route::get('client-area/subscriptions', [SubscriptionController::class, 'index'])->name('account.subscriptions');
    Route::get('client-area/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('account.subscriptions.show');
    Route::post('client-area/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('account.subscriptions.cancel');
    Route::post('client-area/subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('account.subscriptions.resume');
    Route::get('client-area/subscriptions/{subscription}/extend', [SubscriptionController::class, 'showExtend'])->name('account.subscriptions.extend.show');
    Route::post('client-area/subscriptions/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('account.subscriptions.extend.store');
    Route::post('client-area/subscriptions/{subscription}/billing-portal', [SubscriptionController::class, 'billingPortal'])
        ->middleware('throttle:10,1')
        ->name('account.subscriptions.billing-portal');
    Route::get('client-area/product/{license}', [AccountController::class, 'product'])->name('account.product');
    Route::get('client-area/product/{license}/releases/{release}/download', ProductReleaseDownloadController::class)
        ->middleware('throttle:20,1')
        ->name('account.releases.download');
    Route::get('client-area/invoices', [AccountController::class, 'invoices'])->name('account.invoices');
    Route::get('client-area/invoice/{invoice}', [AccountController::class, 'invoice'])->name('account.invoice');
    Route::get('client-area/invoice/{invoice}/download', [AccountController::class, 'downloadInvoice'])->name('account.invoices.download');
    Route::post('client-area/invoice/{invoice}/refund-requests', [RefundRequestController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('account.refund-requests.store');
    Route::delete('client-area/refund-requests/{refundRequest}', [RefundRequestController::class, 'destroy'])
        ->name('account.refund-requests.destroy');
    Route::get('client-area/credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('account.credit-notes.show');
    Route::get('client-area/credit-notes/{creditNote}/download', [CreditNoteController::class, 'download'])->name('account.credit-notes.download');
    Route::post('client-area/licenses/{license}/reissue', LicenseReissueController::class)
        ->name('account.licenses.reissue');
    Route::post('checkout/{product:slug}/prices/{price}', CheckoutController::class)
        ->scopeBindings()
        ->name('checkout.store');
    Route::post(
        'products/{productType:slug}/{product:slug}/reviews',
        ProductReviewController::class,
    )
        ->scopeBindings()
        ->middleware('throttle:10,1')
        ->name('products.reviews.store');
    Route::get('checkout', [CartCheckoutController::class, 'create'])->name('checkout.create');
    Route::post('checkout', [CartCheckoutController::class, 'store'])->name('checkout.cart.store');
    Route::get('client-area/tickets', [SupportTicketController::class, 'index'])->name('support.index');
    Route::get('client-area/tickets/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('client-area/tickets', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('client-area/ticket/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::post('client-area/ticket/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
    Route::post('client-area/ticket/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');
    Route::get('client-area/account-details', [AccountDetailsController::class, 'edit'])->name('profile.edit');
    Route::patch('client-area/account-details', [AccountDetailsController::class, 'update'])->name('profile.update');
    Route::get('client-area/change-password', [AccountDetailsController::class, 'editPassword'])->name('password.edit');
    Route::patch('client-area/change-password', [AccountDetailsController::class, 'updatePassword'])->name('password.update');
    Route::delete('client-area/account', [AccountDetailsController::class, 'destroy'])->name('profile.destroy');
    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::post('logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('impersonation/leave', [ImpersonationController::class, 'destroy'])
    ->middleware('auth')
    ->name('impersonation.leave');

require __DIR__.'/admin.php';
