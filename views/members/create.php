<?php
/**
 * LibTrack ERP - Members: Create
 * Registers a new library member with auto-generated member code.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo    = getDB();
$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $input = [
        'name'      => cleanInput($_POST['name']  ?? ''),
        'email'     => cleanInput($_POST['email'] ?? '') ?: null,
        'phone'     => cleanInput($_POST['phone'] ?? '') ?: null,
        'address'   => cleanInput($_POST['address'] ?? '') ?: null,
        'is_active' => 1,
    ];

    // Validation
    if ($input['name'] === '') $errors[] = 'Nama anggota wajib diisi.';
    if ($input['email'] && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    // Duplicate email check
    if ($input['email']) {
        $chk = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $chk->execute([$input['email']]);
        if ($chk->fetch()) $errors[] = 'Email sudah terdaftar.';
    }

    if (empty($errors)) {
        $input['member_code'] = generateMemberCode($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO members (name, member_code, email, phone, address, is_active)
            VALUES (:name, :member_code, :email, :phone, :address, :is_active)
        ");
        $stmt->execute($input);

        setFlash('success', 'Anggota "' . $input['name'] . '" berhasil didaftarkan dengan kode ' . $input['member_code'] . '.');
        redirect('/libtrack/views/members/index.php');
    }
}

$pageTitle   = 'Tambah Anggota';
$currentPage = 'members';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-person-plus me-2"></i>Daftarkan Anggota Baru</h2>
                <a href="/libtrack/views/members/index.php" class="btn-lt-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="lt-card-body">

                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row g-3">

                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="lt-form-control"
                                       value="<?= e($input['name'] ?? '') ?>"
                                       placeholder="Masukkan nama lengkap" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       class="lt-form-control"
                                       value="<?= e($input['email'] ?? '') ?>"
                                       placeholder="email@contoh.com">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="phone">No. Telepon</label>
                                <input type="tel" id="phone" name="phone"
                                       class="lt-form-control"
                                       value="<?= e($input['phone'] ?? '') ?>"
                                       placeholder="08xx-xxxx-xxxx">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label" for="address">Alamat</label>
                                <textarea id="address" name="address"
                                          class="lt-form-control" rows="2"
                                          placeholder="Alamat lengkap (opsional)"><?= e($input['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="lt-separator"></div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/views/members/index.php" class="btn-lt-secondary">Batal</a>
                        <button type="submit" class="btn-lt-primary">
                            <i class="bi bi-check-circle me-1"></i> Daftarkan Anggota
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
