<?php
/**
 * LibTrack ERP - Helper Functions
 */

// ── Session ──────────────────────────────────────────────────

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true with HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function requireAuth(): void
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: /libtrack/login.php');
        exit;
    }
}

function regenerateSession(): void
{
    session_regenerate_id(true);
}

// ── CSRF ─────────────────────────────────────────────────────

function csrfToken(): string
{
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('CSRF token validation failed.');
    }
}

// ── Output Sanitization ───────────────────────────────────────

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function cleanInput(string $value): string
{
    return trim(strip_tags($value));
}

// ── Flash & Redirect ─────────────────────────────────────────

function setFlash(string $type, string $message): void
{
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    startSecureSession();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// ── Formatting ────────────────────────────────────────────────

function formatDate(?string $dateStr): string
{
    if (!$dateStr) return '-';
    $months = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
               '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
    $parts = explode('-', substr($dateStr, 0, 10));
    if (count($parts) !== 3) return $dateStr;
    return $parts[2] . ' ' . ($months[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
}

/**
 * Positive = overdue days, negative = days remaining.
 */
function daysOverdue(string $dueDate): int
{
    $due  = new DateTime($dueDate);
    $now  = new DateTime(date('Y-m-d'));
    $diff = $now->diff($due);
    return $diff->invert ? (int)$diff->days : -(int)$diff->days;
}

function generateMemberCode(PDO $pdo): string
{
    $row  = $pdo->query("SELECT MAX(id) AS max_id FROM members")->fetch();
    $next = ($row['max_id'] ?? 0) + 1;
    return 'MBR-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// ── Currency ──────────────────────────────────────────────────

function formatRupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ── Settings ──────────────────────────────────────────────────

function getSettingValue(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

/**
 * Calculate fine for a transaction.
 * Returns [overdue_days, fine_amount].
 */
function calculateFine(PDO $pdo, string $dueDate, ?string $returnDate = null): array
{
    $finePerDay  = (float) getSettingValue($pdo, 'fine_per_day', '1000');
    $endDate     = $returnDate ?: date('Y-m-d');
    $overdueDays = max(0, daysOverdue($dueDate));

    // If returned, calculate from actual return date
    if ($returnDate) {
        $due  = new DateTime($dueDate);
        $ret  = new DateTime($returnDate);
        $diff = $ret->diff($due);
        $overdueDays = $diff->invert ? (int)$diff->days : 0;
    }

    return [$overdueDays, $overdueDays * $finePerDay];
}
