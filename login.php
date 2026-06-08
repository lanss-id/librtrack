<?php
/**
 * LibTrack ERP - Login Page
 * Implements: session fixation protection, CSRF, brute-force rate limit,
 * password_verify() against bcrypt hash.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

startSecureSession();

// Already logged in — send to dashboard
if (!empty($_SESSION['user_id'])) {
    redirect('/libtrack/index.php');
}

$error = '';

// ── Simple rate limiting via session counter ─────────────────
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_attempt'] = 0;
}

$maxAttempts  = 5;
$lockoutTime  = 300; // 5 minutes
$isLockedOut  = ($_SESSION['login_attempts'] >= $maxAttempts)
              && (time() - $_SESSION['login_last_attempt'] < $lockoutTime);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLockedOut) {
    validateCsrf();

    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['login_attempts'] = 0;
                regenerateSession();

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                redirect('/libtrack/index.php');
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['login_last_attempt'] = time();
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            error_log('[Login Error] ' . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Coba lagi.';
        }
    }
}

$remaining = $lockoutTime - (time() - $_SESSION['login_last_attempt']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; LibTrack ERP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/libtrack/assets/css/app.css">
    <style>
        body { margin: 0; padding: 0; }
        .lt-login-input-icon { position: relative; }
        .lt-login-input-icon .bi { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--lt-ink-4); font-size: .95rem; pointer-events: none; }
        .lt-login-input-icon .lt-form-control { padding-left: 2.4rem; }
        .lt-login-toggle-pwd { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--lt-ink-4); cursor: pointer; padding: 0; font-size: .95rem; }
        .lt-login-toggle-pwd:hover { color: var(--lt-amber); }
        .lt-decor { position: absolute; font-size: 7rem; color: rgba(255,255,255,.04); pointer-events: none; line-height: 1; }
    </style>
</head>
<body>
<div class="lt-login-bg">

    <!-- Decorative icons -->
    <span class="lt-decor" style="top:5%;left:5%">📚</span>
    <span class="lt-decor" style="bottom:10%;right:8%">📖</span>

    <div class="lt-login-card animate__animated">

        <!-- Header -->
        <div class="lt-login-header">
            <div class="lt-login-logo">
                <i class="bi bi-book-half"></i>
            </div>
            <h1 class="lt-login-title">LibTrack ERP</h1>
            <p class="lt-login-sub">Sistem Manajemen Perpustakaan Akademik</p>
        </div>

        <!-- Lockout Banner -->
        <?php if ($isLockedOut): ?>
        <div class="lt-alert lt-alert-danger mb-3 text-center">
            <i class="bi bi-lock-fill me-1"></i>
            Terlalu banyak percobaan. Coba lagi dalam
            <strong><?= ceil($remaining / 60) ?> menit</strong>.
        </div>
        <?php endif; ?>

        <!-- Error Banner -->
        <?php if ($error && !$isLockedOut): ?>
        <div class="lt-alert lt-alert-danger mb-3">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= e($error) ?>
            <?php if ($_SESSION['login_attempts'] >= 3): ?>
            <div class="mt-1 lt-text-small">
                Percobaan gagal: <?= $_SESSION['login_attempts'] ?> / <?= $maxAttempts ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="/libtrack/login.php" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Username -->
            <div class="lt-form-group">
                <label class="lt-form-label" for="username">Username</label>
                <div class="lt-login-input-icon">
                    <i class="bi bi-person"></i>
                    <input type="text" id="username" name="username"
                           class="lt-form-control"
                           placeholder="Masukkan username"
                           value="<?= e($_POST['username'] ?? '') ?>"
                           required
                           <?= $isLockedOut ? 'disabled' : '' ?>>
                </div>
            </div>

            <!-- Password -->
            <div class="lt-form-group">
                <label class="lt-form-label" for="password">Password</label>
                <div class="lt-login-input-icon">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password"
                           class="lt-form-control"
                           style="padding-right:2.5rem"
                           placeholder="Masukkan password"
                           required
                           <?= $isLockedOut ? 'disabled' : '' ?>>
                    <button type="button" class="lt-login-toggle-pwd" id="togglePwd">
                        <i class="bi bi-eye" id="togglePwdIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-lt-primary mt-2"
                    style="width:100%; justify-content: center; padding:.75rem 1.4rem; font-size:.95rem;"
                    <?= $isLockedOut ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk ke Sistem
            </button>
        </form>

        <!-- Footer hint -->
        <div class="text-center mt-3 lt-text-small lt-text-muted">
            Default: <code>admin</code> / <code>password</code>
            &mdash; Ubah setelah login pertama.
        </div>

    </div><!-- /.lt-login-card -->
</div><!-- /.lt-login-bg -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password visibility toggle
    document.getElementById('togglePwd')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('togglePwdIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
</body>
</html>
