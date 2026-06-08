<?php
/**
 * LibTrack ERP - Dashboard
 * Shows: Total Books, Total Members, Active Borrows, Overdue, Recent Transactions.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();

$pdo = getDB();

// ── Dashboard Statistics ──────────────────────────────────────
$stats = [];

// Total books
$stats['total_books']   = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
// Available books
$stats['available']     = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='Tersedia'")->fetchColumn();
// Currently borrowed
$stats['borrowed']      = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='Dipinjam'")->fetchColumn();
// Total members
$stats['total_members'] = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE is_active=1")->fetchColumn();
// Active transactions
$stats['active_txn']    = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='Dipinjam'")->fetchColumn();
// Overdue transactions (due_date < today AND still borrowed)
$stats['overdue']       = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='Dipinjam' AND due_date < CURDATE()")->fetchColumn();

// ── Recent 8 Transactions ─────────────────────────────────────
$recentStmt = $pdo->query("
    SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.status,
           m.name AS member_name, m.member_code,
           b.title AS book_title
    FROM transactions t
    INNER JOIN members m ON t.member_id = m.id
    INNER JOIN books   b ON t.book_id   = b.id
    ORDER BY t.created_at DESC
    LIMIT 8
");
$recentTransactions = $recentStmt->fetchAll();

// ── Categories Chart Data ─────────────────────────────────────
$catStmt = $pdo->query("SELECT category, COUNT(*) AS total FROM books GROUP BY category ORDER BY total DESC LIMIT 8");
$categories = $catStmt->fetchAll();

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';

ob_start();
?>

<!-- ── Stats Row ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
        <div class="lt-stat lt-stat--teal">
            <div class="lt-stat-icon"><i class="bi bi-journals"></i></div>
            <div>
                <div class="lt-stat-number"><?= $stats['total_books'] ?></div>
                <div class="lt-stat-label">Total Buku</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="lt-stat lt-stat--blue">
            <div class="lt-stat-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="lt-stat-number"><?= $stats['total_members'] ?></div>
                <div class="lt-stat-label">Anggota Aktif</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="lt-stat lt-stat--amber">
            <div class="lt-stat-icon"><i class="bi bi-arrow-left-right"></i></div>
            <div>
                <div class="lt-stat-number"><?= $stats['active_txn'] ?></div>
                <div class="lt-stat-label">Sedang Dipinjam</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="lt-stat lt-stat--red">
            <div class="lt-stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="lt-stat-number"><?= $stats['overdue'] ?></div>
                <div class="lt-stat-label">Terlambat</div>
            </div>
        </div>
    </div>

</div>

<!-- ── Row: Recent Transactions + Categories ─────────────── -->
<div class="row g-3 mb-4">

    <!-- Recent Transactions -->
    <div class="col-12 col-xl-8">
        <div class="lt-card h-100">
            <div class="lt-card-header">
                <h2 class="lt-card-title">
                    <i class="bi bi-clock-history me-2 text-muted"></i>Transaksi Terbaru
                </h2>
                <a href="/libtrack/views/transactions/history.php" class="btn-lt-secondary btn-sm">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="lt-card-body p-0">
                <?php if (empty($recentTransactions)): ?>
                <div class="lt-empty-state">
                    <div class="lt-empty-icon"><i class="bi bi-inbox"></i></div>
                    <p>Belum ada transaksi.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="lt-table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentTransactions as $txn): ?>
                    <?php
                        $isOverdue = ($txn['status'] === 'Dipinjam' && $txn['due_date'] < date('Y-m-d'));
                    ?>
                    <tr>
                        <td>
                            <div class="lt-fw-medium"><?= e($txn['member_name']) ?></div>
                            <div class="lt-text-small lt-text-muted"><?= e($txn['member_code']) ?></div>
                        </td>
                        <td>
                            <div style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"
                                 title="<?= e($txn['book_title']) ?>">
                                <?= e($txn['book_title']) ?>
                            </div>
                        </td>
                        <td class="lt-text-small"><?= formatDate($txn['borrow_date']) ?></td>
                        <td class="lt-text-small">
                            <?= formatDate($txn['due_date']) ?>
                            <?php if ($isOverdue): ?>
                            <span class="lt-badge lt-badge--overdue ms-1">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($txn['status'] === 'Dikembalikan'): ?>
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
    </div>

    <!-- Availability + Category Breakdown -->
    <div class="col-12 col-xl-4">

        <!-- Availability Donut Summary -->
        <div class="lt-card mb-3">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-pie-chart me-2 text-muted"></i>Ketersediaan</h2>
            </div>
            <div class="lt-card-body">
                <?php
                $pct = $stats['total_books'] > 0
                    ? round(($stats['available'] / $stats['total_books']) * 100)
                    : 0;
                ?>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="flex:1">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="lt-text-small">Tersedia</span>
                            <span class="lt-text-small lt-fw-medium"><?= $stats['available'] ?></span>
                        </div>
                        <div class="progress" style="height:8px; border-radius:99px; background:var(--lt-border)">
                            <div class="progress-bar" role="progressbar"
                                 style="width:<?= $pct ?>%; background:var(--lt-teal); border-radius:99px;"
                                 aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <span class="lt-badge lt-badge--available"><?= $pct ?>%</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div style="flex:1">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="lt-text-small">Dipinjam</span>
                            <span class="lt-text-small lt-fw-medium"><?= $stats['borrowed'] ?></span>
                        </div>
                        <div class="progress" style="height:8px; border-radius:99px; background:var(--lt-border)">
                            <div class="progress-bar" role="progressbar"
                                 style="width:<?= 100-$pct ?>%; background:var(--lt-amber); border-radius:99px;"></div>
                        </div>
                    </div>
                    <span class="lt-badge lt-badge--borrowed"><?= 100-$pct ?>%</span>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-tag me-2 text-muted"></i>Kategori Buku</h2>
            </div>
            <div class="lt-card-body p-0">
                <?php if (empty($categories)): ?>
                <div class="lt-empty-state"><p>Belum ada buku.</p></div>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                <?php
                $maxCat = max(array_column($categories, 'total'));
                $colors = ['var(--lt-teal)','var(--lt-amber)','var(--lt-blue)','var(--lt-crimson)','#7C3AED','#059669','#DC2626','#2563EB'];
                foreach ($categories as $i => $cat):
                    $barPct = round(($cat['total'] / $maxCat) * 100);
                    $clr    = $colors[$i % count($colors)];
                ?>
                <li style="padding:.65rem 1.25rem; border-bottom:1px solid var(--lt-border-light);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="lt-text-small lt-fw-medium"><?= e($cat['category']) ?></span>
                        <span class="lt-text-small lt-text-muted"><?= $cat['total'] ?> buku</span>
                    </div>
                    <div class="progress" style="height:5px; border-radius:99px; background:var(--lt-border)">
                        <div class="progress-bar" style="width:<?= $barPct ?>%; background:<?= $clr ?>; border-radius:99px;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Quick Action Row ────────────────────────────────────── -->
<div class="row g-3">
    <div class="col-12">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-lightning me-2 text-muted"></i>Aksi Cepat</h2>
            </div>
            <div class="lt-card-body d-flex flex-wrap gap-2">
                <a href="/libtrack/views/books/create.php" class="btn-lt-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Buku
                </a>
                <a href="/libtrack/views/members/create.php" class="btn-lt-primary">
                    <i class="bi bi-person-plus"></i> Tambah Anggota
                </a>
                <a href="/libtrack/views/transactions/borrow.php" class="btn-lt-primary">
                    <i class="bi bi-book"></i> Proses Peminjaman
                </a>
                <a href="/libtrack/views/transactions/index.php" class="btn-lt-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Daftar Aktif
                </a>
                <a href="/libtrack/views/transactions/history.php" class="btn-lt-secondary">
                    <i class="bi bi-clock-history"></i> Histori Lengkap
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
echo $content;
require_once __DIR__ . '/includes/footer.php';
ob_end_flush();
