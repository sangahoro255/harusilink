<?php
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\LanguageController;
use App\Controllers\AdminController;
use App\Controllers\WeddingController;
use App\Controllers\GuestController;
use App\Controllers\GuestImportController;
use App\Controllers\InvitationController;
use App\Controllers\InvitationDesignController;
use App\Controllers\PublicInvitationController;
use App\Controllers\RsvpController;
use App\Controllers\QrController;
use App\Controllers\VerifyController;
use App\Controllers\CheckinController;
use App\Controllers\ScannerController;
use App\Controllers\ContributionController;
use App\Controllers\MessageController;
use App\Controllers\InvitationSendController;
use App\Controllers\ReminderController;
use App\Controllers\ReportController;
use App\Controllers\WhatsAppWebhookController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/weddings/create', [WeddingController::class, 'create']);
$router->post('/weddings', [WeddingController::class, 'store']);
$router->get('/weddings/{id}', [WeddingController::class, 'show']);
$router->get('/weddings/{id}/details', [WeddingController::class, 'details']);
$router->get('/weddings/{id}/edit', [WeddingController::class, 'edit']);
$router->post('/weddings/{id}/update', [WeddingController::class, 'update']);
$router->post('/weddings/{id}/activate', [WeddingController::class, 'activate']);

$router->get('/weddings/{id}/guests', [GuestController::class, 'index']);
$router->get('/weddings/{id}/guests/create', [GuestController::class, 'create']);
$router->post('/weddings/{id}/guests', [GuestController::class, 'store']);
$router->get('/weddings/{id}/guests/import', [GuestImportController::class, 'show']);
$router->get('/weddings/{id}/guests/import/template', [GuestImportController::class, 'template']);
$router->post('/weddings/{id}/guests/import', [GuestImportController::class, 'import']);
$router->get('/weddings/{id}/guests/{guest}/edit', [GuestController::class, 'edit']);
$router->post('/weddings/{id}/guests/{guest}/update', [GuestController::class, 'update']);
$router->post('/weddings/{id}/guests/{guest}/delete', [GuestController::class, 'delete']);

$router->get('/weddings/{id}/invitation', [InvitationController::class, 'index']);
$router->get('/weddings/{id}/invitation/design', [InvitationDesignController::class, 'show']);
$router->post('/weddings/{id}/invitation/design', [InvitationDesignController::class, 'save']);
$router->get('/weddings/{id}/invitation/{guest}/qr', [QrController::class, 'owner']);
$router->post('/weddings/{id}/invitation/{guest}/toggle', [InvitationController::class, 'toggle']);
$router->post('/weddings/{id}/invitation/{guest}/regenerate', [InvitationController::class, 'regenerate']);

$router->get('/weddings/{id}/rsvp', [RsvpController::class, 'index']);
$router->post('/weddings/{id}/rsvp/{guest}/set', [RsvpController::class, 'set']);

$router->get('/weddings/{id}/contributions', [ContributionController::class, 'index']);
$router->get('/weddings/{id}/contributions/create', [ContributionController::class, 'create']);
$router->post('/weddings/{id}/contributions', [ContributionController::class, 'store']);
$router->get('/weddings/{id}/contributions/settings', [ContributionController::class, 'settings']);
$router->post('/weddings/{id}/contributions/settings', [ContributionController::class, 'saveSettings']);
$router->get('/weddings/{id}/contributions/{contribution}/edit', [ContributionController::class, 'edit']);
$router->post('/weddings/{id}/contributions/{contribution}/update', [ContributionController::class, 'update']);
$router->post('/weddings/{id}/contributions/{contribution}/paid', [ContributionController::class, 'markPaid']);
$router->post('/weddings/{id}/contributions/{contribution}/delete', [ContributionController::class, 'delete']);

$router->get('/weddings/{id}/reminders', [ReminderController::class, 'index']);
$router->post('/weddings/{id}/reminders', [ReminderController::class, 'send']);

$router->get('/weddings/{id}/messages', [MessageController::class, 'index']);
$router->post('/weddings/{id}/messages', [MessageController::class, 'send']);
$router->get('/weddings/{id}/messages/invitations', [InvitationSendController::class, 'show']);
$router->post('/weddings/{id}/messages/invitations', [InvitationSendController::class, 'send']);
$router->get('/weddings/{id}/messages/{message}', [MessageController::class, 'show']);

$router->get('/weddings/{id}/reports', [ReportController::class, 'index']);
$router->get('/weddings/{id}/reports/export/guests', [ReportController::class, 'exportGuests']);
$router->get('/weddings/{id}/reports/export/contributions', [ReportController::class, 'exportContributions']);
$router->get('/weddings/{id}/reports/export/checkins', [ReportController::class, 'exportCheckins']);

$router->get('/weddings/{id}/checkin', [CheckinController::class, 'index']);
$router->post('/weddings/{id}/checkin/pin', [CheckinController::class, 'generatePin']);
$router->post('/weddings/{id}/checkin/pin/disable', [CheckinController::class, 'disablePin']);
$router->post('/weddings/{id}/checkin/{guest}/undo', [CheckinController::class, 'undo']);

$router->get('/i/{token}', [PublicInvitationController::class, 'show']);
$router->post('/i/{token}/rsvp', [PublicInvitationController::class, 'rsvp']);
$router->get('/i/{token}/qr', [QrController::class, 'guest']);

$router->get('/scan/{slug}', [ScannerController::class, 'show']);
$router->post('/scan/{slug}', [ScannerController::class, 'login']);
$router->post('/scan/{slug}/lookup', [ScannerController::class, 'lookup']);
$router->post('/scan/{slug}/search', [ScannerController::class, 'search']);
$router->get('/scan/{slug}/guest/{guest}', [ScannerController::class, 'guest']);
$router->post('/scan/{slug}/guest/{guest}/admit', [ScannerController::class, 'admitGuest']);
$router->post('/scan/{slug}/logout', [ScannerController::class, 'logout']);

$router->get('/verify/{token}', [VerifyController::class, 'show']);
$router->post('/verify/{token}/admit', [VerifyController::class, 'admit']);

$router->get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
$router->post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/status', [AdminController::class, 'updateUserStatus']);
$router->get('/admin/weddings', [AdminController::class, 'weddings']);
$router->get('/admin/activity', [AdminController::class, 'activity']);

$router->post('/lang', [LanguageController::class, 'change']);