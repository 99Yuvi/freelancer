<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentMonitorController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewModerationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocketTokenController;
use App\Http\Controllers\Auth\SocketVerifyController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Client\ProposalController as ClientProposalController;
use App\Http\Controllers\Freelancer\PayoutController as FreelancerPayoutController;
use App\Http\Controllers\Freelancer\ProfileController as FreelancerProfileController;
use App\Http\Controllers\Freelancer\ProposalController as FreelancerProposalController;
use App\Http\Controllers\Internal\MessageController as InternalMessageController;
use App\Http\Controllers\Shared\CategoryController;
use App\Http\Controllers\Shared\ContractController;
use App\Http\Controllers\Shared\ConversationController;
use App\Http\Controllers\Shared\DeliveryFileController;
use App\Http\Controllers\Shared\FreelancerSearchController;
use App\Http\Controllers\Shared\MilestoneController;
use App\Http\Controllers\Shared\NotificationController;
use App\Http\Controllers\Shared\PaymentController;
use App\Http\Controllers\Shared\PaymentWebhookController;
use App\Http\Controllers\Shared\ProjectBrowseController;
use App\Http\Controllers\Shared\ReviewController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureServiceToken;
use App\Http\Middleware\LogAdminAction;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ───────────────────────────────────────────────────────────
    Route::post('register',        [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::post('login',           [LoginController::class,    'store'])->middleware('throttle:10,1');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,60');
    Route::post('reset-password',  [ResetPasswordController::class, 'store']);
    Route::get('projects',           [ProjectBrowseController::class,   'index']);
    Route::get('projects/{project}', [ProjectBrowseController::class,   'show']);
    Route::get('freelancers',        [FreelancerSearchController::class, 'index']);
    Route::get('freelancers/{user}', [FreelancerSearchController::class, 'show']);
    Route::get('categories',         [CategoryController::class, 'index']);
    Route::get('skills',             [CategoryController::class, 'skills']);
    Route::get('users/{user}/reviews', [ReviewController::class, 'forUser']);
    Route::get('settings/public',      [SettingsController::class, 'publicSettings']);

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');

    // ── Razorpay webhook ──────────────────────────────────────────────────
    Route::post('webhooks/razorpay', [PaymentWebhookController::class, 'handle']);

    // ── Internal (Node.js) ────────────────────────────────────────────────
    Route::middleware(EnsureServiceToken::class)->prefix('internal')->group(function () {
        Route::post('messages', [InternalMessageController::class, 'store']);
        Route::get('conversations/{conversation}/members', function (\App\Models\Conversation $conversation) {
            return response()->json([
                'client_id'     => $conversation->client_id,
                'freelancer_id' => $conversation->freelancer_id,
            ]);
        });
    });

    // ── Authenticated ─────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',            [LogoutController::class,            'destroy']);
        Route::get('auth/me',            [MeController::class,                'show']);
        Route::get('auth/socket-verify', [SocketVerifyController::class,      'show']);
        Route::get('auth/socket-token',  [SocketTokenController::class,       'show']);
        Route::post('email/resend',      [EmailVerificationController::class, 'resend'])->middleware('throttle:3,60');

        // Contracts + Milestones
        Route::get('contracts',                                [ContractController::class,  'index']);
        Route::get('contracts/{contract}',                     [ContractController::class,  'show']);
        Route::post('contracts/{contract}/milestones',         [MilestoneController::class, 'store']);
        Route::post('contracts/{contract}/review',             [ReviewController::class,    'store']);
        Route::put('milestones/{milestone}',                   [MilestoneController::class, 'update']);
        Route::delete('milestones/{milestone}',                [MilestoneController::class, 'destroy']);
        Route::post('milestones/{milestone}/deliver',          [MilestoneController::class, 'deliver']);
        Route::post('milestones/{milestone}/approve',          [MilestoneController::class, 'approve']);
        Route::post('milestones/{milestone}/request-revision', [MilestoneController::class, 'requestRevision']);
        Route::get('deliveries/{delivery}/files/{fileId}',     [DeliveryFileController::class, 'download']);

        // Payments
        Route::get('payments',                  [PaymentController::class, 'index']);
        Route::get('payments/{payment}',        [PaymentController::class, 'show']);
        Route::get('payments/{payment}/invoice',[PaymentController::class, 'invoice']);

        // Reviews
        Route::post('reviews/{review}/respond', [ReviewController::class, 'respond']);

        // Conversations
        Route::get('conversations',                         [ConversationController::class, 'index']);
        Route::get('conversations/{conversation}',          [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::patch('conversations/{conversation}/read',   [ConversationController::class, 'markRead']);

        // Notifications — static route MUST be before wildcard {id} route
        Route::get('notifications',             [NotificationController::class, 'index']);
        Route::patch('notifications/read-all',  [NotificationController::class, 'readAll']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'read']);

        // ── Freelancer ────────────────────────────────────────────────────
        Route::middleware(EnsureRole::using('freelancer'))->prefix('freelancer')->group(function () {
            Route::get('profile',    [FreelancerProfileController::class, 'show']);
            Route::put('profile',    [FreelancerProfileController::class, 'update']);
            Route::post('resume',        [FreelancerProfileController::class, 'uploadResume']);
            Route::post('verification',  [FreelancerProfileController::class, 'submitVerification']);
            Route::post('projects/{project}/proposals', [FreelancerProposalController::class, 'store']);
            Route::put('proposals/{proposal}',          [FreelancerProposalController::class, 'update']);
            Route::delete('proposals/{proposal}',       [FreelancerProposalController::class, 'destroy']);
            Route::get('proposals',                     [FreelancerProposalController::class, 'mine']);

            // Payouts
            Route::get('payouts',           [FreelancerPayoutController::class, 'index']);
            Route::post('payouts',          [FreelancerPayoutController::class, 'store']);
            Route::get('payouts/{payout}',  [FreelancerPayoutController::class, 'show']);
        });

        // ── Client ────────────────────────────────────────────────────────
        Route::middleware(EnsureRole::using('client'))->prefix('client')->group(function () {
            Route::get('profile',    [ClientProfileController::class, 'show']);
            Route::put('profile',    [ClientProfileController::class, 'update']);
            Route::get('projects',              [ClientProjectController::class, 'index']);
            Route::post('projects',             [ClientProjectController::class, 'store']);
            Route::get('projects/{project}',    [ClientProjectController::class, 'show']);
            Route::put('projects/{project}',    [ClientProjectController::class, 'update']);
            Route::delete('projects/{project}', [ClientProjectController::class, 'destroy']);
            Route::get('projects/{project}/proposals',     [ClientProposalController::class, 'index']);
            Route::patch('proposals/{proposal}/shortlist', [ClientProposalController::class, 'shortlist']);
            Route::patch('proposals/{proposal}/reject',    [ClientProposalController::class, 'reject']);
            Route::post('proposals/{proposal}/accept',     [ClientProposalController::class, 'accept']);
        });

        // ── Admin (+ audit log middleware) ─────────────────────────────────
        Route::middleware([EnsureRole::using('admin'), LogAdminAction::class])
            ->prefix('admin')
            ->group(function () {
                Route::get('dashboard',                         [AdminDashboardController::class, 'index']);
                Route::get('users',                            [AdminUserController::class, 'index']);
                Route::get('users/{user}',                     [AdminUserController::class, 'show']);
                Route::patch('users/{user}/status',            [AdminUserController::class, 'updateStatus']);
                Route::delete('users/{user}',                  [AdminUserController::class, 'destroy']);
                Route::get('verifications',                    [AdminVerificationController::class, 'index']);
                Route::patch('verifications/{profile}',        [AdminVerificationController::class, 'update']);
                Route::get('verifications/{profile}/documents/{docId}', [AdminVerificationController::class, 'documentUrl']);
                Route::get('categories',                       [AdminCategoryController::class, 'index']);
                Route::post('categories',                      [AdminCategoryController::class, 'store']);
                Route::put('categories/{category}',            [AdminCategoryController::class, 'update']);
                Route::delete('categories/{category}',         [AdminCategoryController::class, 'destroy']);
                Route::get('settings',                         [SettingsController::class, 'index']);
                Route::put('settings',                         [SettingsController::class, 'update']);
                Route::get('payments',                         [PaymentMonitorController::class, 'index']);

                // Payout requests
                Route::get('payouts',                          [AdminPayoutController::class, 'index']);
                Route::post('payouts/{payout}/approve',        [AdminPayoutController::class, 'approve']);
                Route::post('payouts/{payout}/reject',         [AdminPayoutController::class, 'reject']);

                Route::get('reviews',                          [ReviewModerationController::class, 'index']);
                Route::patch('reviews/{review}/hide',          [ReviewModerationController::class, 'hide']);
                Route::patch('reviews/{review}/unhide',        [ReviewModerationController::class, 'unhide']);
                Route::get('audit-logs',                       [AuditLogController::class, 'index']);
                Route::get('reports/overview',                 [ReportController::class, 'overview']);
            });
    });
});
