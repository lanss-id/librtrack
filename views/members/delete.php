<?php
/**
 * LibTrack ERP - Action: Delete Member
 * Constraint: Cannot delete if active borrows exist.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();
validateCsrf();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','ID tidak valid.'); redirect('/libtrack/views/members/index.php'); }

$pdo = getDB();

$stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) { setFlash('danger','Anggota tidak ditemukan.'); redirect('/libtrack/views/members/index.php'); }

// Block delete if active borrows
$check = $pdo->prepare("SELECT id FROM transactions WHERE member_id = ? AND status='Dipinjam' LIMIT 1");
$check->execute([$id]);
if ($check->fetch()) {
    setFlash('danger', '"' . $member['name'] . '" masih memiliki buku yang dipinjam dan tidak dapat dihapus.');
    redirect('/libtrack/views/members/index.php');
}

$pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
setFlash('success', 'Anggota "' . $member['name'] . '" berhasil dihapus.');
redirect('/libtrack/views/members/index.php');
