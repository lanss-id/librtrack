<?php
/**
 * LibTrack ERP - Sidebar Navigation
 */
$currentPage = $currentPage ?? '';
$navItems = [
    ['page'=>'dashboard',    'href'=>'/libtrack/index.php',                    'icon'=>'bi-grid-1x2',        'label'=>'Dashboard'],
    ['page'=>'books',        'href'=>'/libtrack/views/books/index.php',         'icon'=>'bi-journals',        'label'=>'Manajemen Buku'],
    ['page'=>'members',      'href'=>'/libtrack/views/members/index.php',       'icon'=>'bi-people',          'label'=>'Manajemen Anggota'],
    ['page'=>'transactions', 'href'=>'/libtrack/views/transactions/index.php',  'icon'=>'bi-arrow-left-right','label'=>'Peminjaman Aktif'],
    ['page'=>'history',      'href'=>'/libtrack/views/transactions/history.php','icon'=>'bi-clock-history',   'label'=>'Histori Transaksi'],
];
?>

<!-- Sidebar -->
<aside class="lt-sidebar" id="ltSidebar">
    <div class="lt-sidebar-inner">

        <div class="lt-sidebar-brand">
            <div class="lt-sidebar-logo"><i class="bi bi-book-half"></i></div>
            <div>
                <div class="lt-sidebar-brand-name">LibTrack</div>
                <div class="lt-sidebar-brand-sub">Library System</div>
            </div>
        </div>

        <nav class="lt-nav">
            <div class="lt-nav-label-section">NAVIGASI</div>
            <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['href'] ?>"
               class="lt-nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                <span class="lt-nav-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
                <span><?= $item['label'] ?></span>
                <?php if ($currentPage === $item['page']): ?>
                <span class="lt-nav-dot"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="lt-sidebar-footer">
            <div class="lt-sidebar-user">
                <div class="lt-user-avatar">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="lt-user-info">
                    <div class="lt-user-name"><?= e($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin') ?></div>
                    <div class="lt-user-role"><?= e(ucfirst($_SESSION['role'] ?? 'admin')) ?></div>
                </div>
                <a href="/libtrack/logout.php"
                   id="logoutBtn"
                   class="lt-logout-btn"
                   data-username="<?= e($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin') ?>"
                   title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>
</aside>

<!-- Mobile Overlay -->
<div class="lt-overlay" id="ltOverlay"></div>

<!-- Main Content -->
<main class="lt-main">

    <!-- Mobile Top Bar -->
    <div class="lt-topbar d-lg-none">
        <button class="lt-menu-btn" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <span class="lt-topbar-brand">LibTrack</span>
        <span class="lt-topbar-date"><?= date('d M Y') ?></span>
    </div>

    <div class="lt-content">

        <!-- Page Header -->
        <div class="lt-page-header">
            <div>
                <h1 class="lt-page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb lt-breadcrumb">
                        <li class="breadcrumb-item"><a href="/libtrack/index.php"><i class="bi bi-house"></i> Home</a></li>
                        <?php if ($currentPage !== 'dashboard'): ?>
                        <li class="breadcrumb-item active"><?= e($pageTitle ?? '') ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Flash -->
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="lt-flash lt-flash--<?= $flash['type'] ?>" id="flashMsg">
            <i class="bi <?= $flash['type']==='success' ? 'bi-check-circle' : ($flash['type']==='danger' ? 'bi-x-circle' : 'bi-info-circle') ?>"></i>
            <?= e($flash['message']) ?>
            <button onclick="document.getElementById('flashMsg').remove()" class="lt-flash-close"><i class="bi bi-x"></i></button>
        </div>
        <?php endif; ?>
