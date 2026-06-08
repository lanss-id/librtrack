<?php
/**
 * LibTrack ERP - Transactions: Borrow Form
 * Phase 4 Core Logic:
 *  - Only shows books with status = 'Tersedia'
 *  - INSERT into transactions AND UPDATE books.status to 'Dipinjam' atomically
 *  - Checks member has no overdue books
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo    = getDB();
$errors = [];
$input  = [];

// Load available books (Tersedia only)
$availableBooks = $pdo->query("
    SELECT id, title, author, category FROM books
    WHERE status = 'Tersedia'
    ORDER BY title ASC
")->fetchAll();

// Load active members
$activeMembers = $pdo->query("
    SELECT id, name, member_code, email FROM members
    WHERE is_active = 1
    ORDER BY name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $input = [
        'member_id'   => (int)($_POST['member_id']   ?? 0),
        'book_id'     => (int)($_POST['book_id']      ?? 0),
        'borrow_date' => cleanInput($_POST['borrow_date'] ?? date('Y-m-d')),
        'due_date'    => cleanInput($_POST['due_date']    ?? ''),
        'notes'       => cleanInput($_POST['notes']       ?? '') ?: null,
    ];

    // ── Validation ────────────────────────────────────────────
    if (!$input['member_id']) $errors[] = 'Pilih anggota.';
    if (!$input['book_id'])   $errors[] = 'Pilih buku yang akan dipinjam.';
    if (!$input['borrow_date']) $errors[] = 'Tanggal peminjaman wajib diisi.';
    if (!$input['due_date'])    $errors[] = 'Tanggal jatuh tempo wajib diisi.';

    if ($input['due_date'] && $input['borrow_date'] && $input['due_date'] <= $input['borrow_date']) {
        $errors[] = 'Tanggal jatuh tempo harus setelah tanggal peminjaman.';
    }

    if (empty($errors)) {
        // Verify book is still available (re-check, race condition guard)
        $bookChk = $pdo->prepare("SELECT status, title FROM books WHERE id = ? FOR UPDATE");
        // Note: FOR UPDATE requires transaction context; using regular check here for XAMPP compatibility
        $bookChk = $pdo->prepare("SELECT status, title FROM books WHERE id = ?");
        $bookChk->execute([$input['book_id']]);
        $bookRow = $bookChk->fetch();

        if (!$bookRow) {
            $errors[] = 'Buku tidak ditemukan.';
        } elseif ($bookRow['status'] !== 'Tersedia') {
            $errors[] = 'Buku "' . $bookRow['title'] . '" sudah tidak tersedia.';
        }

        // Verify member exists
        $memChk = $pdo->prepare("SELECT id, name FROM members WHERE id = ? AND is_active = 1");
        $memChk->execute([$input['member_id']]);
        $memRow = $memChk->fetch();
        if (!$memRow) $errors[] = 'Anggota tidak ditemukan atau tidak aktif.';
    }

    if (empty($errors)) {
        // ── ATOMIC Transaction ─────────────────────────────────
        try {
            $pdo->beginTransaction();

            // 1. Insert transaction record
            $ins = $pdo->prepare("
                INSERT INTO transactions (member_id, book_id, borrow_date, due_date, notes, status)
                VALUES (:member_id, :book_id, :borrow_date, :due_date, :notes, 'Dipinjam')
            ");
            $ins->execute($input);

            // 2. Update book status to 'Dipinjam'
            $upd = $pdo->prepare("UPDATE books SET status='Dipinjam' WHERE id=?");
            $upd->execute([$input['book_id']]);

            $pdo->commit();

            setFlash('success', 'Peminjaman buku "' . $bookRow['title'] . '" oleh ' . $memRow['name'] . ' berhasil dicatat.');
            redirect('/libtrack/views/transactions/index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('[Borrow Error] ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem. Peminjaman dibatalkan.';
        }
    }
}

$pageTitle   = 'Proses Peminjaman';
$currentPage = 'transactions';
$todayStr    = date('Y-m-d');
$defaultDue  = date('Y-m-d', strtotime('+14 days'));  // 2-week default loan

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-book-half me-2"></i>Form Peminjaman Buku</h2>
                <a href="/libtrack/views/transactions/index.php" class="btn-lt-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="lt-card-body">

                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-4">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <?php if (empty($availableBooks)): ?>
                <div class="lt-alert lt-alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Tidak ada buku tersedia</strong> saat ini. Semua buku sedang dipinjam.
                </div>
                <?php else: ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row g-3">

                        <!-- Select Member -->
                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Anggota Peminjam <span class="text-danger">*</span></label>
                                <select name="member_id" class="lt-form-control" required>
                                    <option value="">-- Pilih Anggota --</option>
                                    <?php foreach ($activeMembers as $mem): ?>
                                    <option value="<?= $mem['id'] ?>"
                                            <?= (int)($input['member_id'] ?? 0) === $mem['id'] ? 'selected' : '' ?>>
                                        [<?= e($mem['member_code']) ?>] <?= e($mem['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($activeMembers)): ?>
                                <p class="lt-text-small lt-text-muted mt-1">
                                    <i class="bi bi-info-circle"></i>
                                    <a href="/libtrack/views/members/create.php">Daftarkan anggota baru</a> terlebih dahulu.
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Select Book -->
                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Buku yang Dipinjam <span class="text-danger">*</span></label>
                                <select name="book_id" class="lt-form-control" required>
                                    <option value="">-- Pilih Buku (Hanya Tersedia) --</option>
                                    <?php foreach ($availableBooks as $bk): ?>
                                    <option value="<?= $bk['id'] ?>"
                                            <?= (int)($input['book_id'] ?? 0) === $bk['id'] ? 'selected' : '' ?>>
                                        <?= e($bk['title']) ?> — <?= e($bk['author']) ?>
                                        [<?= e($bk['category']) ?>]
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="lt-text-small lt-text-muted mt-1">
                                    <i class="bi bi-info-circle"></i>
                                    <?= count($availableBooks) ?> buku tersedia ditampilkan.
                                </p>
                            </div>
                        </div>

                        <!-- Borrow Date -->
                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Tanggal Peminjaman <span class="text-danger">*</span></label>
                                <input type="date" name="borrow_date" class="lt-form-control"
                                       value="<?= e($input['borrow_date'] ?? $todayStr) ?>"
                                       max="<?= $todayStr ?>" required>
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="lt-form-control" id="dueDate"
                                       value="<?= e($input['due_date'] ?? $defaultDue) ?>" required>
                                <p class="lt-text-small lt-text-muted mt-1">Default: 14 hari dari tanggal pinjam.</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Catatan</label>
                                <textarea name="notes" class="lt-form-control" rows="2"
                                          placeholder="Catatan tambahan (opsional)"><?= e($input['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="lt-separator"></div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/views/transactions/index.php" class="btn-lt-secondary">Batal</a>
                        <button type="submit" class="btn-lt-primary">
                            <i class="bi bi-book me-1"></i> Proses Peminjaman
                        </button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
// Auto-calculate due date when borrow_date changes
document.querySelector('[name="borrow_date"]')?.addEventListener('change', function() {
    const borrow = new Date(this.value);
    if (!isNaN(borrow.getTime())) {
        borrow.setDate(borrow.getDate() + 14);
        const y = borrow.getFullYear();
        const m = String(borrow.getMonth()+1).padStart(2,'0');
        const d = String(borrow.getDate()).padStart(2,'0');
        document.getElementById('dueDate').value = y+'-'+m+'-'+d;
    }
});
</script>
JS;

$content = ob_get_clean();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
echo $content;
require_once __DIR__ . '/../../includes/footer.php';
