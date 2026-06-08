/**
 * LibTrack ERP - Application JavaScript
 * Handles: Sidebar toggle, DataTables init, SweetAlert confirm dialogs,
 * and global UI enhancements.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  // ── Sidebar Mobile Toggle ──────────────────────────────────
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar       = document.getElementById('ltSidebar');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ── Initialize DataTables ──────────────────────────────────
  const tables = document.querySelectorAll('.lt-datatable');
  tables.forEach(function (table) {
    $(table).DataTable({
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json',
        // Fallback inline translation
        search:           'Cari:',
        lengthMenu:       'Tampilkan _MENU_ data',
        info:             'Menampilkan _START_ hingga _END_ dari _TOTAL_ data',
        infoEmpty:        'Tidak ada data yang ditampilkan',
        infoFiltered:     '(disaring dari _MAX_ total data)',
        paginate: {
          first:    'Pertama',
          last:     'Terakhir',
          next:     '»',
          previous: '«',
        },
        emptyTable: 'Tidak ada data tersedia',
        zeroRecords: 'Tidak ada hasil yang cocok',
      },
      pageLength: 10,
      responsive: true,
      autoWidth: false,
      columnDefs: [
        { orderable: false, targets: -1 },  // Last column (actions) not sortable
      ],
    });
  });

  // ── Delete Confirmation (SweetAlert2) ─────────────────────
  document.querySelectorAll('.btn-confirm-delete').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const url    = this.getAttribute('href') || this.dataset.url;
      const label  = this.dataset.label || 'data ini';

      Swal.fire({
        title: 'Hapus ' + label + '?',
        text:  'Tindakan ini tidak dapat dibatalkan.',
        icon:  'warning',
        iconColor: '#BE123C',
        showCancelButton: true,
        confirmButtonColor: '#BE123C',
        cancelButtonColor:  '#6B6760',
        confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Ya, Hapus',
        cancelButtonText:  'Batal',
        reverseButtons: true,
        customClass: {
          popup: 'rounded-3 shadow',
        },
      }).then(function (result) {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });

  // ── Return Confirmation ────────────────────────────────────
  document.querySelectorAll('.btn-confirm-return').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const url  = this.getAttribute('href') || this.dataset.url;
      const book = this.dataset.book || 'buku ini';

      Swal.fire({
        title: 'Konfirmasi Pengembalian',
        html:  'Konfirmasi pengembalian <strong>' + book + '</strong>?',
        icon:  'question',
        iconColor: '#0F766E',
        showCancelButton: true,
        confirmButtonColor: '#0F766E',
        cancelButtonColor:  '#6B6760',
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Kembalikan',
        cancelButtonText:  'Batal',
        reverseButtons: true,
      }).then(function (result) {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });

  // ── Auto-dismiss Flash Alerts ──────────────────────────────
  const alerts = document.querySelectorAll('.lt-alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      if (bsAlert) bsAlert.close();
    }, 5000); // 5 seconds
  });

  // ── Logout Confirmation (SweetAlert2) ─────────────────────
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function (e) {
      e.preventDefault();
      const url      = this.getAttribute('href');
      const username = this.dataset.username || 'Anda';

      Swal.fire({
        title: 'Keluar dari Sesi?',
        html:
          '<div style="color:#6B6655;font-size:.9rem;line-height:1.6">' +
            'Anda akan keluar sebagai <strong style="color:#18160F">' + username + '</strong>.<br>' +
            'Sesi Anda akan dihapus dan perlu login kembali.' +
          '</div>',
        icon: 'warning',
        iconColor: '#D97706',
        showCancelButton: true,
        confirmButtonColor: '#18160F',
        cancelButtonColor:  '#6B6760',
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Ya, Keluar',
        cancelButtonText:  'Batal',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
          popup:         'lt-swal-popup',
          title:         'lt-swal-title',
          confirmButton: 'lt-swal-btn-confirm',
          cancelButton:  'lt-swal-btn-cancel',
        },
      }).then(function (result) {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  }

  // ── Tooltip Initialization ─────────────────────────────────
  const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipEls.forEach(function (el) {
    new bootstrap.Tooltip(el);
  });

});
