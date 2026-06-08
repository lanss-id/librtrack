<?php
/**
 * LibTrack ERP - Members: Edit
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) { setFlash('danger', 'ID tidak valid.'); redirect('/libtrack/views/members/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) { setFlash('danger', 'Anggota tidak ditemukan.'); redirect('/libtrack/views/members/index.php'); }

$errors = [];
$input  = $member;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $input = [
        'name'      => cleanInput($_POST['name']    ?? ''),
        'email'     => cleanInput($_POST['email']   ?? '') ?: null,
        'phone'     => cleanInput($_POST['phone']   ?? '') ?: null,
        'address'   => cleanInput($_POST['address'] ?? '') ?: null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($input['name'] === '') $errors[] = 'Nama wajib diisi.';
    if ($input['email'] && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($input['email']) {
        $chk = $pdo->prepare("SELECT id FROM members WHERE email=? AND id!=?");
        $chk->execute([$input['email'], $id]);
        if ($chk->fetch()) $errors[] = 'Email sudah digunakan.';
    }
    if (empty($errors)) {
        $pdo->prepare("UPDATE members SET name=:name,email=:email,phone=:phone,address=:address,is_active=:is_active WHERE id=:id")
            ->execute(array_merge($input, ['id' => $id]));
        setFlash('success', 'Data anggota berhasil diperbarui.');
        redirect('/libtrack/views/members/index.php');
    }
}

$pageTitle   = 'Edit Anggota';
$currentPage = 'members';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="lt-card">
            <div class="lt-card-header">
                <h2 class="lt-card-title"><i class="bi bi-person-gear me-2"></i>Edit Data Anggota</h2>
                <a href="/libtrack/views/members/index.php" class="btn-lt-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
            <div class="lt-card-body">
                <div class="mb-3">
                    <span class="lt-text-small lt-text-muted">Kode Anggota: </span>
                    <code style="background:var(--lt-border);padding:.2rem .5rem;border-radius:4px"><?= e($member['member_code']) ?></code>
                </div>
                <?php if (!empty($errors)): ?>
                <div class="lt-alert lt-alert-danger mb-3"><ul class="mb-0 ps-3"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Nama *</label>
                                <input type="text" name="name" class="lt-form-control" value="<?= e($input['name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Email</label>
                                <input type="email" name="email" class="lt-form-control" value="<?= e($input['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="lt-form-group">
                                <label class="lt-form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="lt-form-control" value="<?= e($input['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="lt-form-group">
                                <label class="lt-form-label">Alamat</label>
                                <textarea name="address" class="lt-form-control" rows="2"><?= e($input['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="lt-form-label d-flex align-items-center gap-2" style="cursor:pointer">
                                <input type="checkbox" name="is_active" value="1" <?= $input['is_active'] ? 'checked' : '' ?>>
                                Anggota Aktif
                            </label>
                        </div>
                    </div>
                    <div class="lt-separator"></div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/libtrack/views/members/index.php" class="btn-lt-secondary">Batal</a>
                        <button type="submit" class="btn-lt-primary"><i class="bi bi-check-circle me-1"></i> Simpan</button>
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
