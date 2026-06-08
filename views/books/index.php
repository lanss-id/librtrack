<?php
/**
 * LibTrack ERP - Books: List
 * Shows all books with search, filter by category/status, and CRUD actions.
 * Constraint: Cannot delete books with status 'Dipinjam'.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();

// ── Handle Delete ─────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int)($_GET['id'] ?? 0);
    $token = $_GET['csrf_token'] ?? '';

    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF validation failed.');
    }

    if ($delId) {
        // Check if book is currently borrowed
        $chk = $pdo->prepare("SELECT status, title FROM books WHERE id = ?");
        $chk->execute([$delId]);
        $bk  = $chk->fetch();

        if (!$bk) {
            setFlash('danger', 'Buku tidak ditemukan.');
        } elseif ($bk['status'] === 'Dipinjam') {
            setFlash('danger', 'Buku "' . $bk['title'] . '" sedang dipinjam dan tidak dapat dihapus.');
        } else {
            $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$delId]);
            setFlash('success', 'Buku "' . $bk['title'] . '" berhasil dihapus.');
        }
    }
    redirect('/libtrack/views/books/index.php');
}

// ── Fetch All Books ───────────────────────────────────────────
$books = $pdo->query("
    SELECT id, title, author, isbn, category, publisher, year, stock, status
    FROM books
    ORDER BY title ASC
")->fetchAll();

$pageTitle   = 'Manajemen Buku';
$currentPage = 'books';

ob_start();
?>

<div class="lt-page-header mb-4">
    <div></div>
    <a href="/libtrack/views/books/create.php" class="btn-lt-primary">
        <i class="bi bi-plus-circle"></i> Tambah Buku
    </a>
</div>

<div class="lt-card">
    <div class="lt-card-header">
        <h2 class="lt-card-title"><i class="bi bi-journals me-2"></i>Daftar Buku</h2>
        <span class="lt-badge lt-badge--available"><?= count($books) ?> buku</span>
    </div>
    <div class="lt-card-body">
        <?php if (empty($books)): ?>
        <div class="lt-empty-state">
            <div class="lt-empty-icon"><i class="bi bi-journal-x"></i></div>
            <p>Belum ada buku terdaftar.</p>
            <a href="/libtrack/views/books/create.php" class="btn-lt-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Buku Pertama
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="lt-table lt-datatable w-100" id="booksTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Judul</th>
                    <th>Pengarang</th>
                    <th>Kategori</th>
                    <th>ISBN</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $i => $b): ?>
            <tr>
                <td class="lt-text-muted lt-text-small"><?= $i + 1 ?></td>
                <td>
                    <div class="lt-fw-medium"><?= e($b['title']) ?></div>
                    <?php if ($b['publisher']): ?>
                    <div class="lt-text-small lt-text-muted"><?= e($b['publisher']) ?> <?= $b['year'] ? '(' . e($b['year']) . ')' : '' ?></div>
                    <?php endif; ?>
                </td>
                <td><?= e($b['author']) ?></td>
                <td>
                    <span class="lt-badge" style="background:var(--lt-border);color:var(--lt-ink-2)">
                        <?= e($b['category']) ?>
                    </span>
                </td>
                <td class="lt-text-small lt-text-muted"><?= $b['isbn'] ? e($b['isbn']) : '—' ?></td>
                <td class="text-center"><?= (int)$b['stock'] ?></td>
                <td>
                    <?php if ($b['status'] === 'Tersedia'): ?>
                    <span class="lt-badge lt-badge--available">Tersedia</span>
                    <?php else: ?>
                    <span class="lt-badge lt-badge--borrowed">Dipinjam</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/libtrack/views/books/edit.php?id=<?= $b['id'] ?>"
                           class="btn-lt-action btn-lt-edit"
                           data-bs-toggle="tooltip" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <?php if ($b['status'] !== 'Dipinjam'): ?>
                        <a href="/libtrack/views/books/index.php?action=delete&id=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn-lt-action btn-lt-delete btn-confirm-delete"
                           data-name="<?= e($b['title']) ?>"
                           data-bs-toggle="tooltip" title="Hapus">
                            <i class="bi bi-trash3"></i>
                        </a>
                        <?php else: ?>
                        <span class="btn-lt-action" style="opacity:.35;cursor:not-allowed"
                              data-bs-toggle="tooltip" title="Tidak bisa dihapus — sedang dipinjam">
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
