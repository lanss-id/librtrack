<?php
/**
 * LibTrack ERP - Settings: Fine Configuration
 * Allows admin to set the daily fine rate.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo    = getDB();
$errors = [];

// ── Current Value ────────────────────────────────────────────
$currentFine = getSettingValue($pdo, 'fine_per_day', '1000');

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $newFine = cleanInput($_POST['fine_per_day'] ?? '');

    if ($newFine === '' || !is_numeric($newFine)) {
        $errors[] = 'Nilai denda harus berupa angka.';
    } elseif ((float)$newFine < 0) {
        $errors[] = 'Nilai denda tidak boleh negatif.';
    }

    if (empty($errors)) {
        $fineValue = (int)round((float)$newFine);

        // Upsert setting
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES ('fine_per_day', :val)
            ON DUPLICATE KEY UPDATE setting_value = :val2
        ");
        $stmt->execute(['val' => $fineValue, 'val2' => $fineValue]);

        $currentFine = (string)$fineValue;
        setFlash('success', 'Konfigurasi denda berhasil diperbarui menjadi ' . formatRupiah($fineValue) . ' per hari.');
        redirect('/libtrack/views/settings/settings.php');
    }
}

$pageTitle   = 'Pengaturan Denda';
$currentPage = 'settings';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-gear me-2"></i>Konfigurasi Denda Keterlambatan</h2>
            </div>
            <div class="lt-card-body">

                <!-- Info Box -->
                <div class="lt-alert lt-alert-info mb-4" style="background:rgba(15,118,110,.08);border-left:4px solid var(--teal);color:var(--lt-text);">
                    <i class="bi bi-info-circle me-2" style="color:var(--teal)"></i>
                    Denda akan otomatis dihitung saat proses pengembalian buku. Jika anggota mengembalikan buku melewati tanggal jatuh tempo, denda dihitung berdasarkan <strong>jumlah hari keterlambatan × tarif per hari</strong>.
                </div>

                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-4">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="lt-form-group mb-4">
                        <label class="lt-form-label">
                            <i class="bi bi-cash-coin me-1"></i>
                            Denda per Hari (Rp) <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="lt-text-muted" style="font-weight:600">Rp</span>
                            <input type="number" name="fine_per_day" class="lt-form-control"
                                   value="<?= e($currentFine) ?>"
                                   min="0" step="100" required
                                   style="max-width:250px; font-size:1.1rem; font-weight:600;">
                            <span class="lt-text-muted">/ hari</span>
                        </div>
                        <p class="lt-text-small lt-text-muted mt-2">
                            <i class="bi bi-calculator me-1"></i>
                            Contoh: Jika terlambat 3 hari dengan tarif <?= formatRupiah((float)$currentFine) ?>,
                            maka denda = <strong><?= formatRupiah((float)$currentFine * 3) ?></strong>
                        </p>
                    </div>

                    <!-- Current Value Display -->
                    <div style="background:var(--lt-bg-subtle);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="lt-text-small lt-text-muted">Tarif Saat Ini:</span>
                            <span style="font-size:1.3rem;font-weight:700;color:var(--teal)">
                                <?= formatRupiah((float)$currentFine) ?><span class="lt-text-small lt-text-muted" style="font-weight:400"> /hari</span>
                            </span>
                        </div>
                    </div>

                    <div class="lt-separator"></div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/index.php" class="btn-lt-secondary">Kembali</a>
                        <button type="submit" class="btn-lt-primary">
                            <i class="bi bi-check-circle me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Fine Summary -->
        <?php
        $fineStats = $pdo->query("
            SELECT
                COUNT(CASE WHEN fine_amount > 0 THEN 1 END) AS total_fined,
                COALESCE(SUM(fine_amount), 0) AS total_fine_amount
            FROM transactions
        ")->fetch();
        ?>
        <div class="lt-card mt-3">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-bar-chart me-2"></i>Ringkasan Denda</h2>
            </div>
            <div class="lt-card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div style="font-size:1.5rem;font-weight:700;color:var(--amber)"><?= (int)$fineStats['total_fined'] ?></div>
                            <div class="lt-text-small lt-text-muted">Transaksi Kena Denda</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--crimson)"><?= formatRupiah((float)$fineStats['total_fine_amount']) ?></div>
                            <div class="lt-text-small lt-text-muted">Total Denda Keseluruhan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
echo $content;
require_once __DIR__ . '/../../includes/footer.php';
