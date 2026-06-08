<?php
/**
 * LibTrack ERP - Books: Edit
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'ID buku tidak valid.');
    redirect('/libtrack/views/books/index.php');
}

// Load existing record
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('danger', 'Buku tidak ditemukan.');
    redirect('/libtrack/views/books/index.php');
}

$errors = [];
$input  = $book; // pre-fill form

$cats = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $input = [
        'title'     => cleanInput($_POST['title']     ?? ''),
        'author'    => cleanInput($_POST['author']    ?? ''),
        'isbn'      => cleanInput($_POST['isbn']      ?? '') ?: null,
        'category'  => cleanInput($_POST['category']  ?? ''),
        'publisher' => cleanInput($_POST['publisher'] ?? '') ?: null,
        'year'      => (int)($_POST['year']           ?? 0) ?: null,
        'stock'     => max(1, (int)($_POST['stock']   ?? 1)),
        'status'    => in_array($_POST['status'] ?? '', ['Tersedia','Dipinjam']) ? $_POST['status'] : $book['status'],
    ];

    if ($input['title']    === '') $errors[] = 'Judul buku wajib diisi.';
    if ($input['author']   === '') $errors[] = 'Nama pengarang wajib diisi.';
    if ($input['category'] === '') $errors[] = 'Kategori wajib diisi.';

    // Check duplicate ISBN (excluding self)
    if ($input['isbn']) {
        $chk = $pdo->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
        $chk->execute([$input['isbn'], $id]);
        if ($chk->fetch()) $errors[] = 'ISBN sudah digunakan oleh buku lain.';
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE books SET title=:title, author=:author, isbn=:isbn,
            category=:category, publisher=:publisher, year=:year,
            stock=:stock, status=:status
            WHERE id=:id
        ");
        $upd->execute(array_merge($input, ['id' => $id]));

        setFlash('success', 'Buku "' . $input['title'] . '" berhasil diperbarui.');
        redirect('/libtrack/views/books/index.php');
    }
}

$pageTitle   = 'Edit Buku';
$currentPage = 'books';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-pencil-square me-2"></i>Edit Buku</h2>
                <a href="/libtrack/views/books/index.php" class="btn-lt-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="lt-card-body">

                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row g-3">

                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Judul Buku *</label>
                                <input type="text" name="title" class="lt-form-control"
                                       value="<?= e($input['title']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Pengarang *</label>
                                <input type="text" name="author" class="lt-form-control"
                                       value="<?= e($input['author']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">ISBN</label>
                                <input type="text" name="isbn" class="lt-form-control"
                                       value="<?= e($input['isbn'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Kategori *</label>
                                <input type="text" name="category" class="lt-form-control"
                                       list="categoryList"
                                       value="<?= e($input['category']) ?>" required>
                                <datalist id="categoryList">
                                    <?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Penerbit</label>
                                <input type="text" name="publisher" class="lt-form-control"
                                       value="<?= e($input['publisher'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Tahun Terbit</label>
                                <input type="number" name="year" class="lt-form-control"
                                       value="<?= e($input['year'] ?? '') ?>"
                                       min="1800" max="<?= date('Y') + 1 ?>">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Jumlah Stok</label>
                                <input type="number" name="stock" class="lt-form-control"
                                       value="<?= e($input['stock'] ?? 1) ?>" min="1">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Status</label>
                                <select name="status" class="lt-form-control">
                                    <option value="Tersedia" <?= $input['status'] === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="Dipinjam" <?= $input['status'] === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="lt-separator"></div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/views/books/index.php" class="btn-lt-secondary">Batal</a>
                        <button type="submit" class="btn-lt-primary">
                            <i class="bi bi-check-circle me-1"></i> Perbarui Buku
                        </button>
                    </div>
                </form>

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
