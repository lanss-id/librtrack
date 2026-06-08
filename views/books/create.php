<?php
/**
 * LibTrack ERP - Books: Create
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo    = getDB();
$errors = [];
$input  = [];

// Fetch existing categories for autocomplete datalist
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
    ];

    // Validation
    if ($input['title']    === '') $errors[] = 'Judul buku wajib diisi.';
    if ($input['author']   === '') $errors[] = 'Nama pengarang wajib diisi.';
    if ($input['category'] === '') $errors[] = 'Kategori wajib diisi.';
    if ($input['year'] && ($input['year'] < 1800 || $input['year'] > (int)date('Y') + 1)) {
        $errors[] = 'Tahun terbit tidak valid.';
    }

    // Check duplicate ISBN
    if ($input['isbn']) {
        $chk = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
        $chk->execute([$input['isbn']]);
        if ($chk->fetch()) $errors[] = 'ISBN sudah terdaftar di sistem.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, isbn, category, publisher, year, stock, status)
            VALUES (:title, :author, :isbn, :category, :publisher, :year, :stock, 'Tersedia')
        ");
        $stmt->execute($input);

        setFlash('success', 'Buku "' . $input['title'] . '" berhasil ditambahkan.');
        redirect('/libtrack/views/books/index.php');
    }
}

$pageTitle   = 'Tambah Buku';
$currentPage = 'books';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-plus-circle me-2"></i>Tambah Buku Baru</h2>
                <a href="/libtrack/views/books/index.php" class="btn-lt-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="lt-card-body">

                <!-- Error List -->
                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row g-3">

                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="title">Judul Buku <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title"
                                       class="lt-form-control"
                                       value="<?= e($input['title'] ?? '') ?>"
                                       placeholder="Masukkan judul buku" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="author">Pengarang <span class="text-danger">*</span></label>
                                <input type="text" id="author" name="author"
                                       class="lt-form-control"
                                       value="<?= e($input['author'] ?? '') ?>"
                                       placeholder="Nama pengarang" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="isbn">ISBN</label>
                                <input type="text" id="isbn" name="isbn"
                                       class="lt-form-control"
                                       value="<?= e($input['isbn'] ?? '') ?>"
                                       placeholder="978-xxx-xxx-xxxx">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="category">Kategori <span class="text-danger">*</span></label>
                                <input type="text" id="category" name="category"
                                       class="lt-form-control"
                                       list="categoryList"
                                       value="<?= e($input['category'] ?? '') ?>"
                                       placeholder="Pilih atau ketik kategori" required>
                                <datalist id="categoryList">
                                    <?php foreach ($cats as $c): ?>
                                    <option value="<?= e($c) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="publisher">Penerbit</label>
                                <input type="text" id="publisher" name="publisher"
                                       class="lt-form-control"
                                       value="<?= e($input['publisher'] ?? '') ?>"
                                       placeholder="Nama penerbit">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="year">Tahun Terbit</label>
                                <input type="number" id="year" name="year"
                                       class="lt-form-control"
                                       value="<?= e($input['year'] ?? '') ?>"
                                       min="1800" max="<?= date('Y') + 1 ?>"
                                       placeholder="<?= date('Y') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="stock">Jumlah Stok</label>
                                <input type="number" id="stock" name="stock"
                                       class="lt-form-control"
                                       value="<?= e($input['stock'] ?? 1) ?>"
                                       min="1" max="999">
                            </div>
                        </div>

                    </div><!-- /.row -->

                    <div class="lt-separator"></div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/views/books/index.php" class="btn-lt-secondary">Batal</a>
                        <button type="submit" class="btn-lt-primary">
                            <i class="bi bi-check-circle me-1"></i> Simpan Buku
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
