<?php
/**
 * LibTrack ERP - Transactions: Active Borrow List
 * Shows all currently borrowed books with return action.
 * Includes fine calculation on return.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();

// ── Read fine rate for display ───────────────────────────────
$finePerDay = (float) getSettingValue($pdo, 'fine_per_day', '1000');

// ── Handle Return via GET (confirmed via SweetAlert) ─────────
if (isset($_GET['action']) && $_GET['action'] === 'return') {
    $txnId = (int)($_GET['id'] ?? 0);

    // CSRF check via token in URL
    $token  = $_GET['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF validation failed.');
    }

    if ($txnId) {
        $txnRow = $pdo->prepare("SELECT t.*, b.title AS book_title FROM transactions t INNER JOIN books b ON b.id = t.book_id WHERE t.id = ? AND t.status='Dipinjam'");
        $txnRow->execute([$txnId]);
        $txn = $txnRow->fetch();

        if ($txn) {
            try {
                $pdo->beginTransaction();

                // Calculate fine
                [$overdueDays, $fineAmount] = calculateFine($pdo, $txn['due_date']);

                // 1. Update transaction: set return_date + status + fine_amount
                $updTxn = $pdo->prepare("UPDATE transactions SET return_date=CURDATE(), status='Dikembalikan', fine_amount=?, updated_at=NOW() WHERE id=?");
                $updTxn->execute([$fineAmount, $txnId]);

                // 2. Update book status back to 'Tersedia'
                $updBook = $pdo->prepare("UPDATE books SET status='Tersedia' WHERE id=?");
                $updBook->execute([$txn['book_id']]);

                $pdo->commit();

                // Build flash message with fine info
                $msg = 'Buku "' . $txn['book_title'] . '" berhasil dikembalikan.';
                if ($fineAmount > 0) {
                    $msg .= ' Denda keterlambatan ' . $overdueDays . ' hari: ' . formatRupiah($fineAmount) . '.';
                }
                setFlash('success', $msg);

            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('danger', 'Gagal memproses pengembalian. Coba lagi.');
            }
        } else {
            setFlash('danger', 'Transaksi tidak ditemukan atau sudah dikembalikan.');
        }
    }
    redirect('/libtrack/views/transactions/index.php');
}

// ── Fetch Active Transactions ─────────────────────────────────
$transactions = $pdo->query("
    SELECT t.id, t.borrow_date, t.due_date, t.status, t.notes,
           m.name AS member_name, m.member_code, m.email AS member_email,
           b.title AS book_title, b.author AS book_author, b.category
    FROM transactions t
    INNER JOIN members m ON m.id = t.member_id
    INNER JOIN books   b ON b.id = t.book_id
    WHERE t.status = 'Dipinjam'
    ORDER BY t.due_date ASC
")->fetchAll();

$pageTitle   = 'Peminjaman Aktif';
$currentPage = 'transactions';

ob_start();
?>

<div class="lt-page-header mb-4">
    <div></div>
    <a href="/libtrack/views/transactions/borrow.php" class="btn-lt-primary">
        <i class="bi bi-plus-circle"></i> Pinjam Buku Baru
    </a>
</div>

<div class="lt-card">
    <div class="lt-card-header">
        <h2 class="lt-card-title"><i class="bi bi-arrow-left-right me-2"></i>Peminjaman Aktif</h2>
        <div class="d-flex align-items-center gap-2">
            <span class="lt-badge lt-badge--borrowed"><?= count($transactions) ?> aktif</span>
        </div>
    </div>
    <div class="lt-card-body">
        <?php if (empty($transactions)): ?>
        <div class="lt-empty-state">
            <div class="lt-empty-icon"><i class="bi bi-inbox"></i></div>
            <p>Tidak ada peminjaman aktif saat ini.</p>
            <a href="/libtrack/views/transactions/borrow.php" class="btn-lt-primary">
                <i class="bi bi-plus-circle me-1"></i>Proses Peminjaman
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="lt-table lt-datatable w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Sisa Hari</th>
                    <th>Estimasi Denda</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $i => $t): ?>
            <?php
                $overdueDays = daysOverdue($t['due_date']);
                $isOverdue   = $overdueDays > 0;
                $estFine     = $isOverdue ? ($overdueDays * $finePerDay) : 0;
            ?>
            <tr <?= $isOverdue ? 'style="background:rgba(190,18,60,.04)"' : '' ?>>
                <td class="lt-text-muted lt-text-small"><?= $i + 1 ?></td>
                <td>
                    <div class="lt-fw-medium"><?= e($t['member_name']) ?></div>
                    <div class="lt-text-small lt-text-muted"><?= e($t['member_code']) ?></div>
                </td>
                <td>
                    <div class="lt-fw-medium" style="max-width:180px"><?= e($t['book_title']) ?></div>
                    <div class="lt-text-small lt-text-muted"><?= e($t['book_author']) ?></div>
                </td>
                <td class="lt-text-small"><?= formatDate($t['borrow_date']) ?></td>
                <td class="lt-text-small">
                    <?= formatDate($t['due_date']) ?>
                </td>
                <td>
                    <?php if ($isOverdue): ?>
                    <span class="lt-badge lt-badge--overdue">
                        +<?= abs($overdueDays) ?> hari
                    </span>
                    <?php else: ?>
                    <span class="lt-badge lt-badge--available">
                        <?= abs($overdueDays) ?> hari
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isOverdue): ?>
                    <span style="color:var(--crimson);font-weight:600;font-size:.85rem">
                        <?= formatRupiah($estFine) ?>
                    </span>
                    <?php else: ?>
                    <span class="lt-text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isOverdue): ?>
                    <span class="lt-badge lt-badge--overdue">Terlambat</span>
                    <?php else: ?>
                    <span class="lt-badge lt-badge--borrowed">Dipinjam</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/libtrack/views/transactions/index.php?action=return&id=<?= $t['id'] ?>&csrf_token=<?= csrfToken() ?>"
                       class="btn-lt-action btn-lt-return btn-confirm-return"
                       data-book="<?= e($t['book_title']) ?>"
                       data-overdue="<?= $isOverdue ? '1' : '0' ?>"
                       data-days="<?= $isOverdue ? abs($overdueDays) : 0 ?>"
                       data-fine="<?= $isOverdue ? formatRupiah($estFine) : '' ?>"
                       data-bs-toggle="tooltip" title="Kembalikan Buku">
                        <i class="bi bi-arrow-return-left"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
echo $content;
require_once __DIR__ . '/../../includes/footer.php';
