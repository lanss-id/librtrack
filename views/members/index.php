<?php
/**
 * LibTrack ERP - Members: List
 * Shows all members with search, CRUD actions.
 * Constraint: Cannot delete members who have active borrows.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();

// ── Fetch All Members ─────────────────────────────────────────
$members = $pdo->query("
    SELECT m.id, m.name, m.member_code, m.email, m.phone, m.is_active,
           COUNT(CASE WHEN t.status='Dipinjam' THEN 1 END) AS active_borrows
    FROM members m
    LEFT JOIN transactions t ON t.member_id = m.id
    GROUP BY m.id
    ORDER BY m.name ASC
")->fetchAll();

$pageTitle   = 'Manajemen Anggota';
$currentPage = 'members';

ob_start();
?>

<div class="lt-page-header mb-4">
    <div></div>
    <a href="/libtrack/views/members/create.php" class="btn-lt-primary">
        <i class="bi bi-person-plus"></i> Tambah Anggota
    </a>
</div>

<div class="lt-card">
    <div class="lt-card-header">
        <h2 class="lt-card-title"><i class="bi bi-people me-2"></i>Daftar Anggota</h2>
        <span class="lt-badge lt-badge--available"><?= count($members) ?> anggota</span>
    </div>
    <div class="lt-card-body">
        <?php if (empty($members)): ?>
        <div class="lt-empty-state">
            <div class="lt-empty-icon"><i class="bi bi-person-x"></i></div>
            <p>Belum ada anggota terdaftar.</p>
            <a href="/libtrack/views/members/create.php" class="btn-lt-primary">
                <i class="bi bi-person-plus me-1"></i> Daftarkan Anggota
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="lt-table lt-datatable w-100" id="membersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Anggota</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Status</th>
                    <th>Pinjaman Aktif</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $i => $m): ?>
            <tr>
                <td class="lt-text-muted lt-text-small"><?= $i + 1 ?></td>
                <td>
                    <code class="lt-text-small" style="background:var(--lt-border);padding:.15rem .4rem;border-radius:4px">
                        <?= e($m['member_code']) ?>
                    </code>
                </td>
                <td class="lt-fw-medium"><?= e($m['name']) ?></td>
                <td class="lt-text-small"><?= $m['email'] ? e($m['email']) : '—' ?></td>
                <td class="lt-text-small"><?= $m['phone'] ? e($m['phone']) : '—' ?></td>
                <td>
                    <?php if ($m['is_active']): ?>
                    <span class="lt-badge lt-badge--available">Aktif</span>
                    <?php else: ?>
                    <span class="lt-badge lt-badge--overdue">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($m['active_borrows'] > 0): ?>
                    <span class="lt-badge lt-badge--borrowed"><?= (int)$m['active_borrows'] ?></span>
                    <?php else: ?>
                    <span class="lt-text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/libtrack/views/members/detail.php?id=<?= $m['id'] ?>"
                           class="btn-lt-action" style="color:var(--teal)"
                           data-bs-toggle="tooltip" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/libtrack/views/members/edit.php?id=<?= $m['id'] ?>"
                           class="btn-lt-action btn-lt-edit"
                           data-bs-toggle="tooltip" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <?php if ((int)$m['active_borrows'] === 0): ?>
                        <a href="/libtrack/views/members/delete.php?id=<?= $m['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn-lt-action btn-lt-delete btn-confirm-delete"
                           data-name="<?= e($m['name']) ?>"
                           data-bs-toggle="tooltip" title="Hapus">
                            <i class="bi bi-trash3"></i>
                        </a>
                        <?php else: ?>
                        <span class="btn-lt-action" style="opacity:.35;cursor:not-allowed"
                              data-bs-toggle="tooltip" title="Tidak bisa dihapus — masih ada pinjaman aktif">
                            <i class="bi bi-trash3"></i>
                        </span>
                        <?php endif; ?>
                    </div>
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
