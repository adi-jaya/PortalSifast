<?php

use App\Http\Controllers\Api\ApiAuthSessionController;
use App\Http\Controllers\Api\ApiTicketController;
use App\Http\Controllers\Api\EmergencyDashboardController;
use App\Http\Controllers\Api\EmergencyReportController;
use App\Http\Controllers\Api\EmployeeSalaryController;
use App\Http\Controllers\Api\EmployeeSalaryImportController;
use App\Http\Controllers\Api\FcmController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\OfficerAuthController;
use App\Http\Controllers\Api\OfficerLocationController;
use App\Http\Controllers\Api\SimmutuApiController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\WebOfficial\DoctorProfileController;
use App\Http\Controllers\Api\WebOfficial\DoctorScheduleController;
use App\Http\Controllers\Api\WebOfficial\ExternalRssFeedAdminController;
use App\Http\Controllers\Api\WebOfficial\ExternalRssFeedController;
use App\Http\Controllers\Api\WebOfficial\InstagramFeedAdminController;
use App\Http\Controllers\Api\WebOfficial\InstagramFeedController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialArticleAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialArticleController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialFeedbackAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialFeedbackController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialMediaController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPartnerAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPartnerController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPolyclinicAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPolyclinicController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPromoAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialPromoController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialRoomAdminController;
use App\Http\Controllers\Api\WebOfficial\WebOfficialRoomController;
use App\Http\Controllers\Api\WorkNoteController;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes untuk Integrasi Sistem Kepegawaian
|--------------------------------------------------------------------------
|
| Endpoint ini digunakan oleh aplikasi kepegawaian untuk membuat dan
| melihat tiket tanpa user perlu login ke PortalSifast.
|
| Autentikasi: Sanctum Bearer Token
| Cara generate token: php artisan tinker
|   $user = User::where('email', 'api-service@portal.local')->firstOrCreate([...]);
|   $token = $user->createToken('kepegawaian-app')->plainTextToken;
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Catatan Kerja (hanya pembuat dan admin)
    Route::apiResource('work-notes', WorkNoteController::class);

    // Data user by NIK (nama, email, dll.) — untuk tampil "login as" di aplikasi kepegawaian
    Route::get('/user', [ApiTicketController::class, 'userByNik']);

    // Buat tiket baru (identifikasi pelapor pakai NIK)
    Route::post('/tickets', [ApiTicketController::class, 'store']);

    // Daftar tiket milik NIK tertentu
    Route::get('/tickets', [ApiTicketController::class, 'index']);

    // Detail tiket (hanya jika requester sesuai NIK)
    Route::get('/tickets/{ticket}', [ApiTicketController::class, 'show']);

    // Tambah komentar pada tiket (hanya requester sesuai NIK)
    Route::post('/tickets/{ticket}/comments', [ApiTicketController::class, 'storeComment']);

    // =====================================================================
    // Endpoint untuk Frontend Kepegawaian (prefix: /api/sifast/)
    // =====================================================================
    Route::prefix('sifast')->group(function () {
        // Data user by NIK (nama, email, dll.)
        Route::get('/user', [ApiTicketController::class, 'userByNik']);

        // Tiket (dengan pagination)
        Route::get('/ticket', [ApiTicketController::class, 'indexPaginated']);
        Route::post('/ticket', [ApiTicketController::class, 'store']);
        Route::get('/ticket/{ticket}', [ApiTicketController::class, 'show']);
        Route::post('/ticket/{ticket}/comments', [ApiTicketController::class, 'storeComment']);

        // Master Data
        Route::get('/ticket-type', fn () => response()->json(
            TicketType::active()->get(['id', 'name', 'slug', 'description'])
        ));
        Route::get('/ticket-category', fn () => response()->json(
            TicketCategory::active()->with('subcategories:id,name,ticket_category_id,is_active')->get(['id', 'name', 'dep_id', 'ticket_type_id', 'is_development'])
        ));
        Route::get('/ticket-priority', fn () => response()->json(
            TicketPriority::active()->ordered()->get(['id', 'name', 'level', 'color', 'response_hours', 'resolution_hours'])
        ));
        Route::get('/ticket-status', fn () => response()->json(
            TicketStatus::active()->ordered()->get(['id', 'name', 'slug', 'color', 'order', 'is_closed'])
        ));

        // FCM device token (untuk push notification panic button / emergency)
        Route::post('/fcm/register', [FcmController::class, 'register']);

        // Payroll / Gaji Karyawan (import dari CSV)
        Route::post('/payroll/import', EmployeeSalaryImportController::class);
        // Payroll / Gaji Karyawan (untuk pegawai lihat gaji sendiri)
        Route::get('/payroll', [EmployeeSalaryController::class, 'index']);
        Route::get('/payroll/{employeeSalary}', [EmployeeSalaryController::class, 'show']);

        // SIMMUTU (integrasi frontend eksternal)
        Route::prefix('simmutu')->middleware('can:access-simmutu-module')->group(function () {
            Route::get('/indicators', [SimmutuApiController::class, 'indicators']);
            Route::get('/realisations', [SimmutuApiController::class, 'index']);
            Route::post('/realisations', [SimmutuApiController::class, 'store'])->middleware('can:record-mutu-realisation');
            Route::get('/realisations/daily-rows', [SimmutuApiController::class, 'dailyRows']);
            Route::get('/realisations/stats', [SimmutuApiController::class, 'stats']);
            Route::get('/realisations/{realisation}', [SimmutuApiController::class, 'show']);
            Route::patch('/realisations/{realisation}', [SimmutuApiController::class, 'update'])->middleware('can:record-mutu-realisation');
            Route::delete('/realisations/{realisation}', [SimmutuApiController::class, 'destroy'])->middleware('can:record-mutu-realisation');
        });

        // Emergency / Panic Button
        Route::prefix('emergency')->group(function () {
            Route::post('/reports', [EmergencyReportController::class, 'store']);
            Route::get('/reports', [EmergencyReportController::class, 'index']);
            Route::get('/reports/{emergency_report}', [EmergencyReportController::class, 'show']);
            Route::get('/reports/{emergency_report}/officer-location', [EmergencyReportController::class, 'officerLocation']);
            Route::patch('/reports/{emergency_report}/cancel', [EmergencyReportController::class, 'cancel']);
            Route::post('/reports/{emergency_report}/photo', [EmergencyReportController::class, 'uploadPhoto']);
            Route::get('/operator/reports', [EmergencyReportController::class, 'operatorIndex']);
            Route::get('/operator/reports/{emergency_report}', [EmergencyReportController::class, 'operatorShow']);
            Route::patch('/operator/reports/{emergency_report}/respond', [EmergencyReportController::class, 'operatorRespond']);
            Route::put('/operator/reports/{emergency_report}/respond', [EmergencyReportController::class, 'operatorRespond']);

            // Staff mobile: Accept tugas panic
            Route::post('/reports/{emergency_report}/accept', [EmergencyReportController::class, 'acceptReport']);

            // Command Center Dashboard API (admin/staff only)
            Route::get('/dashboard', [EmergencyDashboardController::class, 'index']);
            Route::get('/stats', [EmergencyDashboardController::class, 'stats']);
        });

        // Active officers tracking for Command Center
        Route::get('/officer/active', [EmergencyDashboardController::class, 'activeOfficers']);

        // Officer Tracking (petugas emergency) — butuh token dari login officer
        // Throttle 20/min agar kirim lokasi tiap ~5 detik tidak kena block
        Route::prefix('officer')->middleware(['officer', 'throttle:20,1'])->group(function () {
            Route::post('/location', [OfficerLocationController::class, 'store']);
        });
    });
});

// =====================================================================
// Website Official RS (frontend terpisah)
// =====================================================================
Route::prefix('informasi')->group(function () {
    Route::get('/', [WebOfficialArticleController::class, 'index']);
    Route::get('/{slug}', [WebOfficialArticleController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::prefix('kamar-inap')->group(function () {
    Route::get('/', [WebOfficialRoomController::class, 'index']);
    Route::get('/{slug}', [WebOfficialRoomController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::prefix('promosi')->group(function () {
    Route::get('/', [WebOfficialPromoController::class, 'index']);
    Route::get('/{slug}', [WebOfficialPromoController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::prefix('poliklinik')->group(function () {
    Route::get('/', [WebOfficialPolyclinicController::class, 'index']);
    Route::get('/{slug}', [WebOfficialPolyclinicController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::prefix('rekanan')->group(function () {
    Route::get('/', [WebOfficialPartnerController::class, 'index']);
    Route::get('/{slug}', [WebOfficialPartnerController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::post('kritik-saran', [WebOfficialFeedbackController::class, 'store'])
    ->middleware('throttle:kritik-saran');
Route::get('service-units', [WebOfficialFeedbackController::class, 'serviceUnits']);

Route::get('instagram-feed', [InstagramFeedController::class, 'index']);
Route::get('berita-eksternal', [ExternalRssFeedController::class, 'index']);

Route::get('jadwal-dokter/poliklinik', [DoctorScheduleController::class, 'poliklinik']);
Route::get('jadwal-dokter', DoctorScheduleController::class);

Route::get('dokter', [DoctorProfileController::class, 'index']);
Route::get('dokter/{kdDokter}/foto', [DoctorProfileController::class, 'photo'])
    ->where('kdDokter', '[A-Za-z0-9._-]+');
Route::get('dokter/{kdDokter}', [DoctorProfileController::class, 'show'])
    ->where('kdDokter', '[A-Za-z0-9._-]+');

Route::prefix('admin')->middleware(['auth:sanctum', 'webofficial.admin'])->group(function () {
    Route::post('/media/upload', [WebOfficialMediaController::class, 'upload']);
    Route::delete('/media', [WebOfficialMediaController::class, 'destroy']);

    Route::get('/informasi', [WebOfficialArticleAdminController::class, 'index']);
    Route::post('/informasi', [WebOfficialArticleAdminController::class, 'store']);
    Route::get('/informasi/{article}', [WebOfficialArticleAdminController::class, 'show']);
    Route::put('/informasi/{article}', [WebOfficialArticleAdminController::class, 'update']);
    Route::patch('/informasi/{article}', [WebOfficialArticleAdminController::class, 'partialUpdate']);
    Route::delete('/informasi/{article}', [WebOfficialArticleAdminController::class, 'destroy']);

    Route::get('/kamar-inap', [WebOfficialRoomAdminController::class, 'index']);
    Route::post('/kamar-inap', [WebOfficialRoomAdminController::class, 'store']);
    Route::get('/kamar-inap/{room}', [WebOfficialRoomAdminController::class, 'show']);
    Route::put('/kamar-inap/{room}', [WebOfficialRoomAdminController::class, 'update']);
    Route::patch('/kamar-inap/{room}', [WebOfficialRoomAdminController::class, 'partialUpdate']);
    Route::delete('/kamar-inap/{room}', [WebOfficialRoomAdminController::class, 'destroy']);

    Route::get('/promosi', [WebOfficialPromoAdminController::class, 'index']);
    Route::post('/promosi', [WebOfficialPromoAdminController::class, 'store']);
    Route::get('/promosi/{promo}', [WebOfficialPromoAdminController::class, 'show']);
    Route::put('/promosi/{promo}', [WebOfficialPromoAdminController::class, 'update']);
    Route::patch('/promosi/{promo}', [WebOfficialPromoAdminController::class, 'partialUpdate']);
    Route::delete('/promosi/{promo}', [WebOfficialPromoAdminController::class, 'destroy']);

    Route::get('/instagram-feed/status', [InstagramFeedAdminController::class, 'status']);
    Route::post('/instagram-feed/sync', [InstagramFeedAdminController::class, 'sync']);

    Route::get('/berita-eksternal/status', [ExternalRssFeedAdminController::class, 'status']);
    Route::post('/berita-eksternal/sync', [ExternalRssFeedAdminController::class, 'sync']);

    Route::get('/poliklinik/available', [WebOfficialPolyclinicAdminController::class, 'available']);
    Route::get('/poliklinik', [WebOfficialPolyclinicAdminController::class, 'index']);
    Route::post('/poliklinik', [WebOfficialPolyclinicAdminController::class, 'store']);
    Route::get('/poliklinik/{poliklinik}', [WebOfficialPolyclinicAdminController::class, 'show']);
    Route::put('/poliklinik/{poliklinik}', [WebOfficialPolyclinicAdminController::class, 'update']);
    Route::patch('/poliklinik/{poliklinik}', [WebOfficialPolyclinicAdminController::class, 'partialUpdate']);
    Route::delete('/poliklinik/{poliklinik}', [WebOfficialPolyclinicAdminController::class, 'destroy']);

    Route::get('/rekanan', [WebOfficialPartnerAdminController::class, 'index']);
    Route::post('/rekanan', [WebOfficialPartnerAdminController::class, 'store']);
    Route::get('/rekanan/{partner}', [WebOfficialPartnerAdminController::class, 'show']);
    Route::put('/rekanan/{partner}', [WebOfficialPartnerAdminController::class, 'update']);
    Route::patch('/rekanan/{partner}', [WebOfficialPartnerAdminController::class, 'partialUpdate']);
    Route::delete('/rekanan/{partner}', [WebOfficialPartnerAdminController::class, 'destroy']);

    Route::get('/kritik-saran', [WebOfficialFeedbackAdminController::class, 'index']);
    Route::get('/kritik-saran/{feedback}', [WebOfficialFeedbackAdminController::class, 'show']);
    Route::patch('/kritik-saran/{feedback}', [WebOfficialFeedbackAdminController::class, 'partialUpdate']);
    Route::delete('/kritik-saran/{feedback}', [WebOfficialFeedbackAdminController::class, 'destroy']);
});

// Telegram bot webhook (tanpa auth — dipanggil oleh Telegram)
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('api.telegram.webhook');

// Login email + password (untuk frontend eksternal, mis. Sifast)
Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiAuthSessionController::class, 'logout']);
    Route::post('/token/refresh', [ApiAuthSessionController::class, 'refresh']);
});

// Officer login (tanpa auth — mengembalikan token)
Route::post('/sifast/officer/auth/login', [OfficerAuthController::class, 'login']);

// Test endpoint (tanpa auth) untuk cek API berjalan
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API PortalSifast Ticketing',
        'version' => '1.0',
    ]);
});

// Dashboard API endpoints removed - moved to web.php for Inertia compatibility
