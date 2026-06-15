<?php
/**
 * LibTrack ERP - Members: Detail
 * Shows member profile info + full borrowing history with fines.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) { setFlash('danger', 'ID tidak valid.'); redirect('/libtrack/views/members/index.php'); }

// ── Fetch Member ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) { setFlash('danger', 'Anggota tidak ditemukan.'); redirect('/libtrack/views/members/index.php'); }

// ── Fetch Stats ──────────────────────────────────────────────
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*)                                              AS total_borrows,
        SUM(CASE WHEN status='Dipinjam' THEN 1 ELSE 0 END)   AS active_borrows,
        SUM(fine_amount)                                      AS total_fines
    FROM transactions WHERE member_id = ?
");
$statsStmt->execute([$id]);
$memberStats = $statsStmt->fetch();

// ── Fetch All Transactions ───────────────────────────────────
$txnStmt = $pdo->prepare("
    SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.status, t.fine_amount, t.notes,
           b.title AS book_title, b.author AS book_author, b.category
    FROM transactions t
    INNER JOIN books b ON b.id = t.book_id
    WHERE t.member_id = ?
    ORDER BY t.borrow_date DESC
");
$txnStmt->execute([$id]);
$transactions = $txnStmt->fetchAll();

$pageTitle   = 'Detail Anggota';
$currentPage = 'members';

ob_start();
?>

<div class="row g-4">

    <!-- Profile Card -->
    <div class="col-12 col-lg-4">
        <div class="lt-card h-100">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-person-badge me-2"></i>Profil Anggota</h2>
                <a href="/libtrack/views/members/edit.php?id=<?= $member['id'] ?>" class="btn-lt-secondary btn-sm">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </div>
            <div class="lt-card-body">
                <!-- Avatar -->
                <div class="text-center mb-3">
                    <div style="width:72px;height:72px;border-radius:50%;background:var(--teal);color:#fff;
                                display:inline-flex;align-items:center;justify-content:center;
                                font-size:1.75rem;font-weight:700">
                        <?= strtoupper(substr($member['name'], 0, 1)) ?>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <h3 style="font-size:1.1rem;font-weight:700;margin:0"><?= e($member['name']) ?></h3>
                    <code style="background:var(--lt-border);padding:.15rem .5rem;border-radius:4px;font-size:.8rem">
                        <?= e($member['member_code']) ?>
                    </code>
                </div>

                <!-- Info List -->
                <div style="font-size:.875rem;line-height:2">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-envelope text-muted" style="width:20px"></i>
                        <span><?= $member['email'] ? e($member['email']) : '<span class="lt-text-muted">—</span>' ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-telephone text-muted" style="width:20px"></i>
                        <span><?= $member['phone'] ? e($member['phone']) : '<span class="lt-text-muted">—</span>' ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-geo-alt text-muted" style="width:20px"></i>
                        <span><?= $member['address'] ? e($member['address']) : '<span class="lt-text-muted">—</span>' ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-calendar3 text-muted" style="width:20px"></i>
                        <span>Terdaftar: <?= formatDate($member['created_at']) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-circle-fill text-muted" style="width:20px;font-size:.5rem"></i>
                        <?php if ($member['is_active']): ?>
                        <span class="lt-badge lt-badge--available">Aktif</span>
                        <?php else: ?>
                        <span class="lt-badge lt-badge--overdue">Nonaktif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats + History -->
    <div class="col-12 col-lg-8">

        <!-- Stats Row -->
        <div class="row g-3 mb-3">
            <div class="col-4">
                <div class="lt-stat lt-stat--blue">
                    <div class="lt-stat-icon"><i class="bi bi-journal-text"></i></div>
                    <div>
                        <div class="lt-stat-number"><?= (int)$memberStats['total_borrows'] ?></div>
                        <div class="lt-stat-label">Total Pinjaman</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="lt-stat lt-stat--amber">
                    <div class="lt-stat-icon"><i class="bi bi-arrow-left-right"></i></div>
                    <div>
                        <div class="lt-stat-number"><?= (int)$memberStats['active_borrows'] ?></div>
                        <div class="lt-stat-label">Pinjaman Aktif</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="lt-stat lt-stat--red">
                    <div class="lt-stat-icon"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="lt-stat-number" style="font-size:.95rem"><?= formatRupiah((float)($memberStats['total_fines'] ?? 0)) ?></div>
                        <div class="lt-stat-label">Total Denda</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-clock-history me-2"></i>Histori Peminjaman</h2>
                <span class="lt-badge lt-badge--available"><?= count($transactions) ?> transaksi</span>
            </div>
            <div class="lt-card-body">
                <?php if (empty($transactions)): ?>
                <div class="lt-empty-state">
                    <div class="lt-empty-icon"><i class="bi bi-inbox"></i></div>
                    <p>Belum ada histori peminjaman.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="lt-table lt-datatable w-100" id="memberHistoryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $i => $t):
                        $isOverdue = ($t['status'] === 'Dipinjam' && $t['due_date'] < date('Y-m-d'));
                        $fineDisplay = (float)$t['fine_amount'] > 0 ? formatRupiah((float)$t['fine_amount']) : '—';
                        // For active overdue loans, show estimated fine
                        if ($isOverdue && $t['status'] === 'Dipinjam') {
                            [$estDays, $estFine] = calculateFine($pdo, $t['due_date']);
                            $fineDisplay = '<span class="lt-text-small lt-text-muted">~</span> ' . formatRupiah($estFine);
                        }
                    ?>
                    <tr>
                        <td class="lt-text-muted lt-text-small"><?= $i + 1 ?></td>
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
                        <td class="lt-text-small">
                            <?php if ((float)$t['fine_amount'] > 0): ?>
                            <span style="color:var(--crimson);font-weight:600"><?= $fineDisplay ?></span>
                            <?php else: ?>
                            <?= $fineDisplay ?>
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
</div>

<div class="mt-3">
    <a href="/libtrack/views/members/index.php" class="btn-lt-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Anggota
    </a>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
echo $content;
require_once __DIR__ . '/../../includes/footer.php';
