<?php

use App\Http\Controllers\Api\DashboardActivityController;
use App\Http\Controllers\Api\DashboardAnalyticsController;
use App\Http\Controllers\Api\DashboardNotificationController;
use App\Http\Controllers\Api\DashboardTextAnalyticsController;
use App\Http\Controllers\AsetAspakController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\AsetDokumenController;
use App\Http\Controllers\AsetFotoController;
use App\Http\Controllers\AsetImportController;
use App\Http\Controllers\AsetJenisController;
use App\Http\Controllers\AsetKategoriController;
use App\Http\Controllers\AsetMasterController;
use App\Http\Controllers\AsetMasterCsvController;
use App\Http\Controllers\AsetMutasiLokasiController;
use App\Http\Controllers\AsetMutasiLokasiPrintController;
use App\Http\Controllers\AsetNonAlkesController;
use App\Http\Controllers\AsetPeminjamanController;
use App\Http\Controllers\AsetPeminjamanPrintController;
use App\Http\Controllers\AsetPublicController;
use App\Http\Controllers\AsetRuangController;
use App\Http\Controllers\AsetSinkronController;
use App\Http\Controllers\AuditAsetController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DailyActivityReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentReportController;
use App\Http\Controllers\DepartmentReportPrintController;
use App\Http\Controllers\EmergencyReportWebController;
use App\Http\Controllers\EmployeeSalaryWebImportController;
use App\Http\Controllers\Integrations\SikatInboundSsoController;
use App\Http\Controllers\Integrations\SikatSsoRedirectController;
use App\Http\Controllers\InventarisBarangController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\InventarisGambarController;
use App\Http\Controllers\InventarisJenisController;
use App\Http\Controllers\InventarisKategoriController;
use App\Http\Controllers\InventarisMerkController;
use App\Http\Controllers\InventarisProdusenController;
use App\Http\Controllers\InventarisRuangController;
use App\Http\Controllers\MonitoringDeviceController;
use App\Http\Controllers\MutuCategoryController;
use App\Http\Controllers\MutuIndicatorController;
use App\Http\Controllers\MutuRealisationController;
use App\Http\Controllers\PatroliAreaController;
use App\Http\Controllers\PatroliCheckinController;
use App\Http\Controllers\PatroliLaporanController;
use App\Http\Controllers\PatroliTemplateController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequesterReportController;
use App\Http\Controllers\Settings\AsetPenyusutanSettingsController;
use App\Http\Controllers\Settings\MonitoringKategoriSettingsController;
use App\Http\Controllers\SimmutuDashboardController;
use App\Http\Controllers\SimmutuDepartmentRecapController;
use App\Http\Controllers\SimmutuUnitKerjaController;
use App\Http\Controllers\SlaReportController;
use App\Http\Controllers\Tatanaskah\DokumenController;
use App\Http\Controllers\Tatanaskah\PegawaiSearchController;
use App\Http\Controllers\TechnicianReportController;
use App\Http\Controllers\TechnicianReportPrintController;
use App\Http\Controllers\TianjiLaporanController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCollaboratorController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketDocumentationController;
use App\Http\Controllers\TicketIssueController;
use App\Http\Controllers\TicketRecommendationController;
use App\Http\Controllers\TicketSparepartItemController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\TicketVendorCostController;
use App\Http\Controllers\UserOnlineController;
use App\Http\Controllers\UserPresenceController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WebOfficial\WebOfficialArticleWebController;
use App\Http\Controllers\WebOfficial\WebOfficialDashboardController;
use App\Http\Controllers\WebOfficial\WebOfficialExternalRssWebController;
use App\Http\Controllers\WebOfficial\WebOfficialFeedbackWebController;
use App\Http\Controllers\WebOfficial\WebOfficialInstagramWebController;
use App\Http\Controllers\WebOfficial\WebOfficialPartnerWebController;
use App\Http\Controllers\WebOfficial\WebOfficialPolyclinicWebController;
use App\Http\Controllers\WebOfficial\WebOfficialPromoWebController;
use App\Http\Controllers\WebOfficial\WebOfficialRoomWebController;
use App\Http\Controllers\WorkNoteController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/');

// Scan QR aset — publik, tanpa login
Route::get('q/{aset}', [AsetPublicController::class, 'show'])->name('aset.public.show');
Route::get('q/{aset}/foto', [AsetPublicController::class, 'foto'])->name('aset.public.foto');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('sso/sikat', SikatInboundSsoController::class)
    ->name('sso.sikat');

// Dashboard API endpoints (for Inertia frontend tabs)
Route::prefix('api/dashboard')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/analytics', [DashboardAnalyticsController::class, 'index']);
    Route::get('/activities', [DashboardActivityController::class, 'index']);
    Route::get('/notifications', [DashboardNotificationController::class, 'index']);
    Route::post('/notifications/mark-read', [DashboardNotificationController::class, 'markAsRead']);
    Route::get('/text-analytics', [DashboardTextAnalyticsController::class, 'index']);
});

// Broadcast auth untuk private channel (chat)
Broadcast::routes(['middleware' => ['web', 'auth']]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('integrations/sikat/go', SikatSsoRedirectController::class)
        ->name('integrations.sikat.go');

    Route::get('users', [UsersController::class, 'index'])->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::get('pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/sla', SlaReportController::class)->name('reports.sla');
    Route::get('reports/department', DepartmentReportController::class)->name('reports.department');
    Route::get('reports/department/print', DepartmentReportPrintController::class)->name('reports.department.print');
    Route::get('reports/requesters', RequesterReportController::class)->name('reports.requesters');
    Route::get('reports/daily-activity', DailyActivityReportController::class)->name('reports.daily-activity');
    Route::get('reports/technician', TechnicianReportController::class)->name('reports.technician');
    Route::get('reports/technician/print', TechnicianReportPrintController::class)->name('reports.technician.print');

    // Laporan Darurat (Emergency / Panic Button) — admin & staff
    Route::get('emergency-reports', [EmergencyReportWebController::class, 'index'])->name('emergency-reports.index');
    Route::get('emergency-reports/create', [EmergencyReportWebController::class, 'create'])->name('emergency-reports.create');
    Route::post('emergency-reports', [EmergencyReportWebController::class, 'store'])->name('emergency-reports.store');
    Route::get('emergency-reports/{emergency_report}', [EmergencyReportWebController::class, 'show'])->name('emergency-reports.show');
    Route::patch('emergency-reports/{emergency_report}/respond', [EmergencyReportWebController::class, 'respond'])->name('emergency-reports.respond');
    Route::post('emergency-reports/{emergency_report}/cancel', [EmergencyReportWebController::class, 'cancel'])->name('emergency-reports.cancel');
    Route::delete('emergency-reports/{emergency_report}', [EmergencyReportWebController::class, 'destroy'])->name('emergency-reports.destroy');

    // Staff Mobile - Panic Button Acceptance
    Route::get('panic-staff', [EmergencyReportWebController::class, 'staff'])->name('emergency-reports.staff');

    // Inventaris SIMRS (read-only) — write dinonaktifkan, gunakan modul Aset
    Route::get('inventaris/export', [InventarisController::class, 'export'])->name('inventaris.export');
    Route::get('inventaris/audit', [InventarisController::class, 'audit'])->name('inventaris.audit');
    Route::get('inventaris/label-print-batch', [InventarisController::class, 'labelPrintBatch'])->name('inventaris.label-print-batch');
    Route::get('inventaris/{inventaris}/label-print', [InventarisController::class, 'labelPrint'])->name('inventaris.label-print');
    Route::get('inventaris/{inventaris}/photo', [InventarisGambarController::class, 'show'])->name('inventaris.photo');
    Route::resource('inventaris', InventarisController::class)
        ->only(['index', 'show'])
        ->parameters(['inventaris' => 'inventaris:no_inventaris']);

    // Inventaris Barang (read-only SIMRS)
    Route::resource('inventaris-barang', InventarisBarangController::class)
        ->only(['index', 'show'])
        ->parameters(['inventaris-barang' => 'barang'])
        ->where(['barang' => '.*']);

    // Master lookup inventaris (read-only SIMRS)
    Route::resource('inventaris-ruang', InventarisRuangController::class)
        ->only(['index'])
        ->parameters(['inventaris-ruang' => 'ruang']);
    Route::resource('inventaris-kategori', InventarisKategoriController::class)
        ->only(['index'])
        ->parameters(['inventaris-kategori' => 'kategori']);
    Route::resource('inventaris-jenis', InventarisJenisController::class)
        ->only(['index'])
        ->parameters(['inventaris-jenis' => 'jenis']);
    Route::resource('inventaris-merk', InventarisMerkController::class)
        ->only(['index'])
        ->parameters(['inventaris-merk' => 'merk']);
    Route::resource('inventaris-produsen', InventarisProdusenController::class)
        ->only(['index'])
        ->parameters(['inventaris-produsen' => 'produsen']);

    // Aset portal (database utama)
    Route::get('aset/sinkron', [AsetSinkronController::class, 'index'])->name('aset.sinkron.index');
    Route::post('aset/sinkron/preview', [AsetSinkronController::class, 'preview'])->name('aset.sinkron.preview');
    Route::post('aset/sinkron/apply', [AsetSinkronController::class, 'apply'])->name('aset.sinkron.apply');

    Route::get('aset/audit', [AuditAsetController::class, 'index'])->name('aset.audit.index');
    Route::get('aset/audit/create', [AuditAsetController::class, 'create'])->name('aset.audit.create');
    Route::post('aset/audit', [AuditAsetController::class, 'store'])->name('aset.audit.store');
    Route::get('aset/audit/{audit}', [AuditAsetController::class, 'show'])->name('aset.audit.show');
    Route::post('aset/audit/{audit}/scan', [AuditAsetController::class, 'scan'])->name('aset.audit.scan');
    Route::post('aset/audit/{audit}/selesai', [AuditAsetController::class, 'selesai'])->name('aset.audit.selesai');
    Route::post('aset/audit/{audit}/setujui', [AuditAsetController::class, 'setujui'])->name('aset.audit.setujui');
    Route::patch('aset/audit/{audit}/item/{item}', [AuditAsetController::class, 'updateItem'])->name('aset.audit.item.update');
    Route::post('aset/audit/{audit}/item/{item}/bukti', [AuditAsetController::class, 'storeBukti'])->name('aset.audit.item.bukti');

    Route::get('aset/created', [AsetController::class, 'created'])->name('aset.created');
    Route::post('aset/bulk-delete', [AsetController::class, 'bulkDestroy'])->name('aset.bulk-destroy');

    Route::get('aset/import', [AsetImportController::class, 'create'])->name('aset.import');
    Route::get('aset/import/template', [AsetImportController::class, 'template'])->name('aset.import.template');
    Route::post('aset/import/preview', [AsetImportController::class, 'preview'])->name('aset.import.preview');
    Route::post('aset/import', [AsetImportController::class, 'store'])->name('aset.import.store');
    Route::delete('aset/import/preview', [AsetImportController::class, 'clear'])->name('aset.import.clear');

    Route::get('aset-peminjaman/search-aset', [AsetPeminjamanController::class, 'searchAset'])->name('aset-peminjaman.search-aset');
    Route::get('aset-peminjaman/search-pegawai', [AsetPeminjamanController::class, 'searchPegawai'])->name('aset-peminjaman.search-pegawai');
    Route::get('aset-peminjaman/search-user', [AsetPeminjamanController::class, 'searchUser'])->name('aset-peminjaman.search-user');
    Route::post('aset-peminjaman/{peminjaman}/kembalikan', [AsetPeminjamanController::class, 'kembalikan'])->name('aset-peminjaman.kembalikan');
    Route::get('aset-peminjaman/{peminjaman}/print', AsetPeminjamanPrintController::class)->name('aset-peminjaman.print');
    Route::resource('aset-peminjaman', AsetPeminjamanController::class)
        ->parameters(['aset-peminjaman' => 'peminjaman'])
        ->only(['index', 'create', 'store', 'show']);

    Route::get('aset-mutasi-lokasi/search-aset', [AsetMutasiLokasiController::class, 'searchAset'])->name('aset-mutasi-lokasi.search-aset');
    Route::get('aset-mutasi-lokasi/search-pegawai', [AsetMutasiLokasiController::class, 'searchPegawai'])->name('aset-mutasi-lokasi.search-pegawai');
    Route::get('aset-mutasi-lokasi/search-user', [AsetMutasiLokasiController::class, 'searchUser'])->name('aset-mutasi-lokasi.search-user');
    Route::get('aset-mutasi-lokasi/{mutasi}/print', AsetMutasiLokasiPrintController::class)->name('aset-mutasi-lokasi.print');
    Route::resource('aset-mutasi-lokasi', AsetMutasiLokasiController::class)
        ->parameters(['aset-mutasi-lokasi' => 'mutasi'])
        ->only(['index', 'create', 'store', 'show']);

    Route::post('aset/master/{tipe}', [AsetMasterController::class, 'store'])
        ->whereIn('tipe', ['kategori', 'jenis', 'merk', 'produsen', 'distributor'])
        ->name('aset.master.store');
    Route::get('aset/master/non-alkes/search', [AsetMasterController::class, 'searchNonAlkes'])
        ->name('aset.master.non-alkes.search');
    Route::get('aset/master/non-alkes/suggest-kode', [AsetNonAlkesController::class, 'suggestKode'])
        ->name('aset.master.non-alkes.suggest-kode');
    Route::get('aset/master/non-alkes', [AsetNonAlkesController::class, 'index'])
        ->name('aset.master.non-alkes.index');
    Route::post('aset/master/non-alkes', [AsetNonAlkesController::class, 'store'])
        ->name('aset.master.non-alkes.store');
    Route::patch('aset/master/non-alkes/{nonAlkes}/kategori', [AsetNonAlkesController::class, 'updateKategori'])
        ->name('aset.master.non-alkes.kategori');
    Route::patch('aset/master/non-alkes/{nonAlkes}/nama', [AsetNonAlkesController::class, 'updateNama'])
        ->name('aset.master.non-alkes.nama');
    Route::patch('aset/master/non-alkes/{nonAlkes}', [AsetNonAlkesController::class, 'update'])
        ->name('aset.master.non-alkes.update');
    Route::delete('aset/master/non-alkes/{nonAlkes}', [AsetNonAlkesController::class, 'destroy'])
        ->name('aset.master.non-alkes.destroy');
    Route::get('aset/master/aspak/search', [AsetMasterController::class, 'searchAspak'])
        ->name('aset.master.aspak.search');
    Route::get('aset/master/aspak/suggest-kode', [AsetAspakController::class, 'suggestKode'])
        ->name('aset.master.aspak.suggest-kode');
    Route::get('aset/master/aspak', [AsetAspakController::class, 'index'])
        ->name('aset.master.aspak.index');
    Route::post('aset/master/aspak', [AsetAspakController::class, 'store'])
        ->name('aset.master.aspak.store');
    Route::patch('aset/master/aspak/{aspak}', [AsetAspakController::class, 'update'])
        ->name('aset.master.aspak.update');
    Route::delete('aset/master/aspak/{aspak}', [AsetAspakController::class, 'destroy'])
        ->name('aset.master.aspak.destroy');
    Route::get('aset/master/ruang', [AsetRuangController::class, 'index'])
        ->name('aset.master.ruang.index');
    Route::get('aset/master/{tipe}/csv/template', [AsetMasterCsvController::class, 'template'])
        ->whereIn('tipe', ['ruang', 'aspak', 'non_alkes'])
        ->name('aset.master.csv.template');
    Route::get('aset/master/{tipe}/csv/export', [AsetMasterCsvController::class, 'export'])
        ->whereIn('tipe', ['ruang', 'aspak', 'non_alkes'])
        ->name('aset.master.csv.export');
    Route::post('aset/master/{tipe}/csv/import', [AsetMasterCsvController::class, 'import'])
        ->whereIn('tipe', ['ruang', 'aspak', 'non_alkes'])
        ->name('aset.master.csv.import');
    Route::get('aset/master/jenis', [AsetJenisController::class, 'index'])
        ->name('aset.master.jenis.index');
    Route::patch('aset/master/jenis/{jenis}/merk', [AsetJenisController::class, 'updateMerk'])
        ->name('aset.master.jenis.merk');
    Route::get('aset/master/kategori', [AsetKategoriController::class, 'index'])
        ->name('aset.master.kategori.index');
    Route::post('aset/master/kategori/simpan', [AsetKategoriController::class, 'store'])
        ->name('aset.master.kategori.store');
    Route::patch('aset/master/kategori/{kategori}', [AsetKategoriController::class, 'update'])
        ->name('aset.master.kategori.update');
    Route::delete('aset/master/kategori/{kategori}', [AsetKategoriController::class, 'destroy'])
        ->name('aset.master.kategori.destroy');
    Route::post('aset/master/kategori/{kategori}/merge', [AsetKategoriController::class, 'merge'])
        ->name('aset.master.kategori.merge');
    Route::get('aset/pengaturan-penyusutan', [AsetPenyusutanSettingsController::class, 'edit'])
        ->name('aset.pengaturan-penyusutan.edit');
    Route::put('aset/pengaturan-penyusutan', [AsetPenyusutanSettingsController::class, 'update'])
        ->name('aset.pengaturan-penyusutan.update');

    Route::post('aset/{aset}/foto', [AsetFotoController::class, 'store'])->name('aset.foto.store');
    Route::delete('aset/{aset}/foto/{foto}', [AsetFotoController::class, 'destroy'])->name('aset.foto.destroy');
    Route::post('aset/{aset}/dokumen', [AsetDokumenController::class, 'store'])->name('aset.dokumen.store');
    Route::get('aset/{aset}/dokumen/{dokumen}/unduh', [AsetDokumenController::class, 'unduh'])->name('aset.dokumen.unduh');
    Route::delete('aset/{aset}/dokumen/{dokumen}', [AsetDokumenController::class, 'destroy'])->name('aset.dokumen.destroy');
    Route::post('aset/{aset}/verifikasi', [AsetController::class, 'verifikasi'])->name('aset.verifikasi');
    Route::patch('aset/{aset}/monitoring', [AsetController::class, 'updateMonitoring'])
        ->name('aset.monitoring.update');
    Route::get('aset/{aset}/label-print', [AsetController::class, 'labelPrint'])->name('aset.label-print');
    Route::get('aset/{aset}/foto-sumber', [AsetFotoController::class, 'showSumber'])->name('aset.foto-sumber');
    Route::resource('aset', AsetController::class)->parameters(['aset' => 'aset']);

    Route::get('monitoring', [MonitoringDeviceController::class, 'index'])->name('monitoring.index');
    Route::get('monitoring/pengaturan-kategori', [MonitoringKategoriSettingsController::class, 'edit'])
        ->name('monitoring.pengaturan-kategori.edit');
    Route::put('monitoring/pengaturan-kategori', [MonitoringKategoriSettingsController::class, 'update'])
        ->name('monitoring.pengaturan-kategori.update');
    Route::get('monitoring/{device}', [MonitoringDeviceController::class, 'show'])->name('monitoring.show');
    Route::get('monitoring/{device}/desktop', [MonitoringDeviceController::class, 'desktop'])
        ->name('monitoring.desktop');
    Route::post('monitoring/{device}/commands', [MonitoringDeviceController::class, 'storeCommand'])
        ->name('monitoring.commands.store');
    Route::patch('monitoring/{device}/aset', [MonitoringDeviceController::class, 'updateAset'])
        ->name('monitoring.aset.update');
    Route::delete('monitoring/{device}', [MonitoringDeviceController::class, 'destroy'])
        ->name('monitoring.destroy');

    Route::get('infrastruktur', [TianjiLaporanController::class, 'index'])->name('infrastruktur.index');
    Route::redirect('laporan-tianji', '/infrastruktur');
    Route::get('laporan-tianji/export/ringkasan', [TianjiLaporanController::class, 'exportRingkasan'])
        ->name('laporan-tianji.export.ringkasan');
    Route::get('laporan-tianji/export/harian', [TianjiLaporanController::class, 'exportHarian'])
        ->name('laporan-tianji.export.harian');
    Route::get('laporan-tianji/export/gangguan', [TianjiLaporanController::class, 'exportGangguan'])
        ->name('laporan-tianji.export.gangguan');
    Route::get('laporan-tianji/export/agent', [TianjiLaporanController::class, 'exportAgent'])
        ->name('laporan-tianji.export.agent');
    Route::get('laporan-tianji/export/detail', [TianjiLaporanController::class, 'exportDetail'])
        ->name('laporan-tianji.export.detail');

    // Rencana / Project (tracking per project)
    Route::resource('projects', ProjectController::class);

    // Ticket routes (board + statuses must be before resource so not caught as ticket id)
    Route::get('tickets/board', [TicketController::class, 'board'])->name('tickets.board');
    Route::get('tickets/statuses', [TicketStatusController::class, 'index'])->name('tickets.statuses.index');
    Route::get('tickets/search-for-link', [TicketController::class, 'searchForLink'])->name('tickets.search-for-link');
    Route::get('tickets/search-for-inventaris', [TicketController::class, 'searchForInventaris'])->name('tickets.search-for-inventaris');
    Route::get('tickets/search-for-user', [TicketController::class, 'searchForUser'])->name('tickets.search-for-user');
    Route::get('tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    Route::get('tickets/import', [TicketController::class, 'importForm'])->name('tickets.import');
    Route::get('tickets/import/template', [TicketController::class, 'importTemplate'])->name('tickets.import.template');
    Route::post('tickets/import', [TicketController::class, 'import'])->name('tickets.import.store');
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/assign-self', [TicketController::class, 'assignToSelf'])->name('tickets.assign-self');
    Route::post('tickets/{ticket}/transfer-department', [TicketController::class, 'transferDepartment'])->name('tickets.transfer-department');
    Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/confirm', [TicketController::class, 'confirm'])->name('tickets.confirm');
    Route::post('tickets/{ticket}/complain', [TicketController::class, 'complain'])->name('tickets.complain');
    Route::post('tickets/{ticket}/publish', [TicketController::class, 'publish'])->name('tickets.publish');

    // Bantuan AI — rekomendasi solusi
    Route::get('tickets/{ticket}/recommendation', TicketRecommendationController::class)->name('tickets.recommendation');
    // Generate dokumen resolution
    Route::get('tickets/{ticket}/documentation', TicketDocumentationController::class)->name('tickets.documentation');

    // Ticket issues
    Route::post('tickets/{ticket}/issues', [TicketIssueController::class, 'store'])->name('tickets.issues.store');
    Route::patch('tickets/{ticket}/issues/{issue}/resolve', [TicketIssueController::class, 'resolve'])->name('tickets.issues.resolve');

    // Ticket comments
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::delete('tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy'])->name('tickets.comments.destroy');

    // Ticket attachments
    Route::post('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])->name('tickets.attachments.store');
    Route::delete('tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy'])->name('tickets.attachments.destroy');

    // Ticket collaborators (rekan)
    Route::post('tickets/{ticket}/collaborators', [TicketCollaboratorController::class, 'store'])->name('tickets.collaborators.store');
    Route::delete('tickets/{ticket}/collaborators/{collaborator}', [TicketCollaboratorController::class, 'destroy'])->name('tickets.collaborators.destroy');

    // Ticket vendor costs
    Route::post('tickets/{ticket}/vendor-costs', [TicketVendorCostController::class, 'store'])->name('tickets.vendor-costs.store');
    Route::patch('tickets/{ticket}/vendor-costs/{vendorCost}', [TicketVendorCostController::class, 'update'])->name('tickets.vendor-costs.update');
    Route::delete('tickets/{ticket}/vendor-costs/{vendorCost}', [TicketVendorCostController::class, 'destroy'])->name('tickets.vendor-costs.destroy');

    // Ticket spare part (perbaikan sendiri)
    Route::post('tickets/{ticket}/sparepart-items', [TicketSparepartItemController::class, 'store'])->name('tickets.sparepart-items.store');
    Route::patch('tickets/{ticket}/sparepart-items/{sparepartItem}', [TicketSparepartItemController::class, 'update'])->name('tickets.sparepart-items.update');
    Route::delete('tickets/{ticket}/sparepart-items/{sparepartItem}', [TicketSparepartItemController::class, 'destroy'])->name('tickets.sparepart-items.destroy');

    // User Presence API
    Route::get('api/users/online', [UserPresenceController::class, 'index'])->name('api.users.online');
    Route::get('api/users/online/count', [UserPresenceController::class, 'count'])->name('api.users.online.count');
    Route::get('api/users/{user}/online', [UserPresenceController::class, 'check'])->name('api.users.online.check');

    // User Online Page
    Route::get('users/online', [UserOnlineController::class, 'index'])->name('users.online');
    Route::get('api/users-online', [UserOnlineController::class, 'api'])->name('api.users-online');

    // Catatan Kerja (work notes)
    Route::get('catatan', [WorkNoteController::class, 'index'])->name('catatan.index');
    Route::post('catatan', [WorkNoteController::class, 'store'])->name('catatan.store');
    Route::patch('catatan/{workNote}', [WorkNoteController::class, 'update'])->name('catatan.update');
    Route::delete('catatan/{workNote}', [WorkNoteController::class, 'destroy'])->name('catatan.destroy');

    // Chat
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');

    // SIMMUTU (sistem informasi manajemen mutu)
    Route::prefix('simmutu')->name('simmutu.')->group(function (): void {
        Route::get('/', SimmutuDashboardController::class)
            ->middleware('simmutu.view')
            ->name('dashboard');
        Route::get('recap/departments', SimmutuDepartmentRecapController::class)
            ->middleware('simmutu.view')
            ->name('recap.departments');

        Route::get('unit-kerja', [SimmutuUnitKerjaController::class, 'index'])
            ->middleware('simmutu.view')
            ->name('unit-kerja.index');
        Route::get('unit-kerja/{dep}', [SimmutuUnitKerjaController::class, 'show'])
            ->middleware('simmutu.view')
            ->where('dep', '[A-Za-z0-9._-]+')
            ->name('unit-kerja.show');
        Route::get('unit-kerja/{dep}/kategori/{category}', [SimmutuUnitKerjaController::class, 'category'])
            ->middleware('simmutu.view')
            ->where('dep', '[A-Za-z0-9._-]+')
            ->name('unit-kerja.category');

        Route::get('realisations', [MutuRealisationController::class, 'index'])
            ->middleware('simmutu.view')
            ->name('realisations.index');

        Route::middleware(['simmutu.view', 'simmutu.manage'])->group(function (): void {
            Route::resource('categories', MutuCategoryController::class)->except(['show']);
            Route::resource('indicators', MutuIndicatorController::class)->except(['show']);
        });

        Route::middleware(['simmutu.view', 'simmutu.input'])->group(function (): void {
            Route::get('realisations/create', [MutuRealisationController::class, 'create'])->name('realisations.create');
            Route::post('realisations', [MutuRealisationController::class, 'store'])->name('realisations.store');
            Route::get('realisations/{realisation}/edit', [MutuRealisationController::class, 'edit'])->name('realisations.edit');
            Route::patch('realisations/{realisation}', [MutuRealisationController::class, 'update'])->name('realisations.update');
            Route::delete('realisations/{realisation}', [MutuRealisationController::class, 'destroy'])->name('realisations.destroy');
            Route::get('realisations/{realisation}', [MutuRealisationController::class, 'show'])->name('realisations.show');
        });
    });

    // Website Official RS — kelola konten berita & kamar inap
    Route::prefix('web-official')->name('web-official.')->middleware('webofficial.admin')->group(function (): void {
        Route::get('/', WebOfficialDashboardController::class)->name('dashboard');
        Route::resource('articles', WebOfficialArticleWebController::class)->except(['show']);
        Route::resource('rooms', WebOfficialRoomWebController::class)->except(['show']);
        Route::resource('promosi', WebOfficialPromoWebController::class)->except(['show']);
        Route::resource('poliklinik', WebOfficialPolyclinicWebController::class)->except(['show']);
        Route::resource('rekanan', WebOfficialPartnerWebController::class)->except(['show']);
        Route::resource('kritik-saran', WebOfficialFeedbackWebController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('instagram', [WebOfficialInstagramWebController::class, 'index'])->name('instagram.index');
        Route::post('instagram/sync', [WebOfficialInstagramWebController::class, 'sync'])->name('instagram.sync');
        Route::get('berita-eksternal', [WebOfficialExternalRssWebController::class, 'index'])->name('berita-eksternal.index');
        Route::post('berita-eksternal/sync', [WebOfficialExternalRssWebController::class, 'sync'])->name('berita-eksternal.sync');
    });

    // Tata Naskah — Naskah Dinas Arahan (Fase 1)
    Route::prefix('tatanaskah')->name('tatanaskah.')->group(function (): void {
        Route::get('pegawai/search', PegawaiSearchController::class)->name('pegawai.search');
        Route::get('dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
        Route::get('dokumen/create', [DokumenController::class, 'create'])->name('dokumen.create');
        Route::post('dokumen', [DokumenController::class, 'store'])->name('dokumen.store');
        Route::get('dokumen/{dokumen}', [DokumenController::class, 'show'])->name('dokumen.show');
        Route::get('dokumen/{dokumen}/file', [DokumenController::class, 'file'])->name('dokumen.file');
        Route::post('dokumen/{dokumen}/transition', [DokumenController::class, 'transition'])->name('dokumen.transition');
        Route::delete('dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');
    });

    // Payroll / Gaji Karyawan
    Route::middleware('payroll.access')->group(function (): void {
        Route::get('payroll', [EmployeeSalaryWebImportController::class, 'index'])->name('payroll.index');
        Route::get('payroll/dashboard', [EmployeeSalaryWebImportController::class, 'dashboard'])->name('payroll.dashboard');
        Route::get('payroll/import', [EmployeeSalaryWebImportController::class, 'create'])->name('payroll.import');
        Route::get('payroll/import/template', [EmployeeSalaryWebImportController::class, 'importTemplate'])->name('payroll.import.template');
        Route::post('payroll/import', [EmployeeSalaryWebImportController::class, 'store'])->name('payroll.import.store');
        Route::get('payroll/import-history', [EmployeeSalaryWebImportController::class, 'importHistory'])->name('payroll.import-history');
        Route::get('payroll/import/{payrollImport}/warnings', [EmployeeSalaryWebImportController::class, 'importWarnings'])->name('payroll.import-warnings');
        Route::get('payroll/audit-logs', [EmployeeSalaryWebImportController::class, 'auditLogs'])->name('payroll.audit-logs');
        Route::post('payroll/import/{payrollImport}/rollback', [EmployeeSalaryWebImportController::class, 'rollbackImport'])->name('payroll.rollback');
        Route::post('payroll/import/{payrollImport}/approve', [EmployeeSalaryWebImportController::class, 'approveImport'])->name('payroll.approve');
        Route::post('payroll/import/{payrollImport}/reject', [EmployeeSalaryWebImportController::class, 'rejectImport'])->name('payroll.reject');
        Route::post('payroll/bulk-delete', [EmployeeSalaryWebImportController::class, 'bulkDestroy'])->name('payroll.bulk-destroy');
        Route::post('payroll/bulk-email', [EmployeeSalaryWebImportController::class, 'sendBulkEmail'])->name('payroll.bulk-email');
        Route::post('payroll/{employeeSalary}/send-email', [EmployeeSalaryWebImportController::class, 'sendEmail'])->name('payroll.send-email');
        Route::get('payroll/employee-history', [EmployeeSalaryWebImportController::class, 'employeeHistorySearch'])->name('payroll.employee-history');
        Route::get('payroll/employee/{nik}', [EmployeeSalaryWebImportController::class, 'employeeHistory'])->name('payroll.employee.show');
        Route::get('payroll/{employeeSalary}', [EmployeeSalaryWebImportController::class, 'show'])->name('payroll.show');
        Route::get('payroll/{employeeSalary}/print', [EmployeeSalaryWebImportController::class, 'print'])->name('payroll.print');
        Route::patch('payroll/{employeeSalary}', [EmployeeSalaryWebImportController::class, 'update'])->name('payroll.update');
        Route::delete('payroll/{employeeSalary}', [EmployeeSalaryWebImportController::class, 'destroy'])->name('payroll.destroy');
    });

    // Patroli Security
    Route::middleware('patroli.access')->prefix('patroli')->name('patroli.')->group(function (): void {
        Route::get('checkin', [PatroliCheckinController::class, 'index'])->name('checkin.index');
        Route::get('scan/{ruang}', [PatroliCheckinController::class, 'scan'])->name('scan');
        Route::post('checkin', [PatroliCheckinController::class, 'store'])->name('checkin.store');
        Route::get('checkin/{checkin}', [PatroliCheckinController::class, 'show'])->name('checkin.show');

        Route::get('laporan', [PatroliLaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/export', [PatroliLaporanController::class, 'export'])->name('laporan.export');

        Route::get('templates', [PatroliTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create', [PatroliTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates', [PatroliTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{template}/edit', [PatroliTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}', [PatroliTemplateController::class, 'update'])->name('templates.update');

        Route::get('area', [PatroliAreaController::class, 'index'])->name('area.index');
        Route::get('area/create', [PatroliAreaController::class, 'create'])->name('area.create');
        Route::post('area', [PatroliAreaController::class, 'store'])->name('area.store');
        Route::get('area/{area}', [PatroliAreaController::class, 'show'])->name('area.show');
        Route::put('area/{area}', [PatroliAreaController::class, 'update'])->name('area.update');
        Route::post('area/{area}/ruang', [PatroliAreaController::class, 'storeRuang'])->name('area.ruang.store');
        Route::put('area/{area}/ruang/{ruang}', [PatroliAreaController::class, 'updateRuang'])->name('area.ruang.update');
        Route::delete('area/{area}/ruang/{ruang}', [PatroliAreaController::class, 'destroyRuang'])->name('area.ruang.destroy');
        Route::get('area/{area}/ruang/{ruang}/label', [PatroliAreaController::class, 'labelPrint'])->name('area.ruang.label');

        Route::redirect('titik', '/patroli/area');
    });
});

require __DIR__.'/settings.php';
