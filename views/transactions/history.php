<?php
/**
 * LibTrack ERP - Transactions: Full History
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();

$transactions = $pdo->query("
    SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.status, t.notes,
           m.name AS member_name, m.member_code,
           b.title AS book_title, b.author AS book_author, b.category
    FROM transactions t
    INNER JOIN members m ON m.id = t.member_id
    INNER JOIN books   b ON b.id = t.book_id
    ORDER BY t.borrow_date DESC
")->fetchAll();

$pageTitle   = 'Histori Transaksi';
$currentPage = 'history';
ob_start();
?>

<div class="lt-card">
    <div class="lt-card-header">
        <h2 class="lt-card-title"><i class="bi bi-clock-history me-2"></i>Histori Semua Transaksi</h2>
        <a href="/libtrack/views/transactions/borrow.php" class="btn-lt-primary">
            <i class="bi bi-plus-circle"></i> Pinjam Buku
        </a>
    </div>
    <div class="lt-card-body">
        <?php if (empty($transactions)): ?>
        <div class="lt-empty-state">
            <div class="lt-empty-icon"><i class="bi bi-inbox"></i></div>
            <p>Belum ada histori transaksi.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="lt-table lt-datatable w-100" id="historyTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $i => $t):
                $isOverdue = ($t['status'] === 'Dipinjam' && $t['due_date'] < date('Y-m-d'));
            ?>
            <tr>
                <td class="lt-text-muted lt-text-small"><?= $i + 1 ?></td>
                <td>
                    <div class="lt-fw-medium"><?= e($t['member_name']) ?></div>
                    <div class="lt-text-small lt-text-muted"><?= e($t['member_code']) ?></div>
                </td>
                <td>
                    <div class="lt-fw-medium" style="max-width:200px"><?= e($t['book_title']) ?></div>
                    <div class="lt-text-small lt-text-muted"><?= e($t['book_author']) ?></div>
                </td>
                <td class="lt-text-small"><?= formatDate($t['borrow_date']) ?></td>
                <td class="lt-text-small">
                    <?= formatDate($t['due_date']) ?>
                    <?php if ($isOverdue): ?><span class="lt-badge lt-badge--overdue ms-1">Overdue</span><?php endif; ?>
                </td>
                <td class="lt-text-small"><?= formatDate($t['return_date']) ?></td>
                <td>
                    <?php if ($t['status'] === 'Dikembalikan'): ?>
                    <span class="lt-badge lt-badge--returned">Dikembalikan</span>
                    <?php elseif ($isOverdue): ?>
                    <span class="lt-badge lt-badge--overdue">Terlambat</span>
                    <?php else: ?>
                    <span class="lt-badge lt-badge--borrowed">Dipinjam</span>
                    <?php endif; ?>
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
