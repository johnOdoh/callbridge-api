<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyPaypalWebhook;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::patch('/verify-phone', [AuthController::class, 'verifyPhone']);
    Route::post('/resend-verification-token', [AuthController::class, 'resendVerificationToken']);
    Route::post('/send-reset-token', [AuthController::class, 'sendPasswordResetToken']);
    Route::patch('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('payment/paypal/success', [PaymentController::class, 'paypalPaymentSuccess'])->name('payment.paypal.success');
Route::get('payment/paypal/cancel', [PaymentController::class, 'paypalPaymentCancel'])->name('payment.paypal.cancel');
Route::post('payment/paypal/payouts', [PaymentController::class, 'paypalPayouts']);

Route::get('payment/paystack/success', [PaymentController::class, 'paystackPaymentSuccess'])->name('payment.paystack.success');
Route::get('payment/paystack/cancel', [PaymentController::class, 'paystackPaymentCancel'])->name('payment.paystack.cancel');

Route::get('payment/stripe/success', [PaymentController::class, 'stripePaymentSuccess'])->name('payment.stripe.success');
Route::get('payment/stripe/cancel', [PaymentController::class, 'stripePaymentCancel'])->name('payment.stripe.cancel');

Route::post('paypal/webhooks', [WebhookController::class, 'paypalWebhooks'])->middleware(VerifyPaypalWebhook::class);
Route::post('stripe/webhooks', [WebhookController::class, 'stripeWebhooks']);

ROute::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user/affiliate', [UserController::class, 'affiliate']);
    Route::get('user/leaderboard', [UserController::class, 'leaderboard']);
    Route::put('user/update-image', [UserController::class, 'updateImage']);
    Route::delete('user/logout', [UserController::class, 'logout']);
});
