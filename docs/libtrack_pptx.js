'use strict';

const pptxgen = require('pptxgenjs');
const React = require('react');
const ReactDOMServer = require('react-dom/server');
const sharp = require('sharp');

// ── Design Tokens ─────────────────────────────────────────────
// Minimalist palette: dark charcoal + teal + amber accent
const C = {
  // Core
  black: '0F1117',   // near-black for dark slides
  slate: '1E2432',   // dark slate
  white: 'FFFFFF',
  offWh: 'F8F9FA',   // light bg
  muted: 'F1F3F5',   // card bg

  // Brand
  teal: '0D9488',   // primary
  tealL: 'CCFBF1',   // teal light
  amber: 'D97706',   // accent
  amberL: 'FEF3C7',

  // System
  blue: '2563EB',
  blueL: 'DBEAFE',
  rose: 'E11D48',
  roseL: 'FFE4E6',
  violet: '7C3AED',
  violetL: 'EDE9FE',
  green: '059669',
  greenL: 'D1FAE5',

  // Neutrals
  ink: '374151',   // body text
  mute: '6B7280',   // muted text
  border: 'E5E7EB',
  divider: 'F3F4F6',
};

const F_TITLE = 'Georgia';
const F_BODY = 'Calibri';
const F_MONO = 'Consolas';

// ── Icon helper ───────────────────────────────────────────────
const fa = require('react-icons/fa');
const md = require('react-icons/md');
const bi = require('react-icons/bi');

async function ico(Comp, color = '#FFFFFF', size = 512) {
  const svg = ReactDOMServer.renderToStaticMarkup(
    React.createElement(Comp, { color, size: String(size) })
  );
  const buf = await sharp(Buffer.from(svg)).png().toBuffer();
  return 'image/png;base64,' + buf.toString('base64');
}

const sh = () => ({
  type: 'outer', color: '000000', blur: 10, offset: 2, angle: 135, opacity: 0.07
});

// Reusable slide header (dark bar style)
function addHeader(s, pres, label, accent = C.teal) {
  s.addShape(pres.shapes.RECTANGLE, {
    x: 0, y: 0, w: 10, h: 0.68,
    fill: { color: C.black }, line: { color: C.black }
  });
  s.addShape(pres.shapes.RECTANGLE, {
    x: 0, y: 0.62, w: 10, h: 0.06,
    fill: { color: accent }, line: { color: accent }
  });
  s.addText(label, {
    x: 0.5, y: 0, w: 9, h: 0.65,
    fontSize: 15, fontFace: F_BODY, bold: true,
    color: C.white, valign: 'middle', margin: 0,
    charSpacing: 1,
  });
}

// Reusable section number pill
function addPill(s, pres, num, x, y, color) {
  s.addShape(pres.shapes.OVAL, {
    x, y, w: 0.38, h: 0.38,
    fill: { color }, line: { color }
  });
  s.addText(String(num), {
    x, y, w: 0.38, h: 0.38,
    fontSize: 10, fontFace: F_TITLE, bold: true,
    color: C.white, align: 'center', valign: 'middle', margin: 0,
  });
}

// ── MAIN ──────────────────────────────────────────────────────
(async () => {
  const pres = new pptxgen();
  pres.layout = 'LAYOUT_16x9';
  pres.title = 'LibTrack — Dokumentasi Teknis & Fungsional';
  pres.author = 'LibTrack';

  // ══════════════════════════════════════════════════════════
  // SLIDE 1 — COVER
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.black };

    // Left large color block
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0, y: 0, w: 4.8, h: 5.625,
      fill: { color: C.teal, transparency: 0 },
      line: { color: C.teal }
    });

    // Book icon on color block
    const bookIco = await ico(fa.FaBook, '#FFFFFF', 512);
    s.addImage({ data: bookIco, x: 2.05, y: 0.7, w: 0.7, h: 0.7 });

    // Title on left
    s.addText('LibTrack', {
      x: 0.45, y: 1.55, w: 4.1, h: 0.85,
      fontSize: 48, fontFace: F_TITLE, bold: true,
      color: C.white, margin: 0,
    });
    s.addText('ERP', {
      x: 0.45, y: 2.4, w: 4.1, h: 0.5,
      fontSize: 28, fontFace: F_BODY, bold: false,
      color: 'FFFFFF99', margin: 0, charSpacing: 6,
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.45, y: 3.05, w: 1.8, h: 0.04,
      fill: { color: 'FFFFFF55' }, line: { color: 'FFFFFF55' }
    });
    s.addText('Sistem Manajemen\nPerpustakaan Akademik', {
      x: 0.45, y: 3.18, w: 4.1, h: 0.8,
      fontSize: 13, fontFace: F_BODY,
      color: 'FFFFFFBB', margin: 0,
    });

    // Tech pills bottom-left
    const pills = ['PHP 8.2', 'MySQL 8', 'Bootstrap 5', 'Docker'];
    for (let i = 0; i < pills.length; i++) {
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.45 + i * 1.05, y: 5.1, w: 0.95, h: 0.3,
        fill: { color: 'FFFFFF15' }, line: { color: 'FFFFFF30' }
      });
      s.addText(pills[i], {
        x: 0.45 + i * 1.05, y: 5.1, w: 0.95, h: 0.3,
        fontSize: 8, fontFace: F_BODY,
        color: 'FFFFFFAA', align: 'center', valign: 'middle', margin: 0,
      });
    }

    // Right: description cards
    const cards = [
      { t: 'Manajemen Buku', d: 'CRUD koleksi, stok, kategori, ISBN, status real-time', icon: fa.FaBook, c: C.teal },
      { t: 'Manajemen Anggota', d: 'Registrasi, kode auto (MBR-NNN), status aktif/nonaktif', icon: fa.FaUsers, c: C.blue },
      { t: 'Transaksi', d: 'Peminjaman & pengembalian atomik, deteksi overdue', icon: fa.FaExchangeAlt, c: C.amber },
      { t: 'Dashboard', d: 'KPI real-time, grafik kategori, histori transaksi', icon: fa.FaChartBar, c: C.violet },
    ];

    for (let i = 0; i < cards.length; i++) {
      const card = cards[i];
      const y = 0.55 + i * 1.2;
      s.addShape(pres.shapes.RECTANGLE, {
        x: 5.1, y, w: 4.6, h: 1.05,
        fill: { color: 'FFFFFF08' }, line: { color: 'FFFFFF15' }
      });
      const icoData = await ico(card.icon, '#' + card.c, 256);
      s.addImage({ data: icoData, x: 5.25, y: y + 0.28, w: 0.42, h: 0.42 });
      s.addText(card.t, {
        x: 5.78, y: y + 0.12, w: 3.75, h: 0.34,
        fontSize: 13, fontFace: F_BODY, bold: true,
        color: C.white, margin: 0,
      });
      s.addText(card.d, {
        x: 5.78, y: y + 0.5, w: 3.75, h: 0.44,
        fontSize: 10, fontFace: F_BODY,
        color: 'FFFFFFAA', margin: 0,
      });
    }

    // Source link bottom right
    s.addText('github.com/lanss-id/librtrack', {
      x: 5.1, y: 5.22, w: 4.6, h: 0.28,
      fontSize: 8.5, fontFace: F_MONO,
      color: C.teal, align: 'right', margin: 0,
    });
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 2 — ARSITEKTUR SISTEM (3-TIER)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '01 / Arsitektur Sistem', C.teal);

    // Left: 3-Tier diagram
    const tiers = [
      { name: 'Presentation Layer', sub: 'Browser · Bootstrap 5 · DataTables · SweetAlert2', color: C.teal, icon: fa.FaDesktop },
      { name: 'Application Layer', sub: 'PHP 8.2 · PDO · Session · CSRF · Helpers', color: C.amber, icon: fa.FaCode },
      { name: 'Data Layer', sub: 'MySQL 8 · InnoDB · utf8mb4 · FK Constraints', color: C.blue, icon: fa.FaDatabase },
    ];

    for (let i = 0; i < tiers.length; i++) {
      const t = tiers[i];
      const y = 0.88 + i * 1.43;

      // White card
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.45, y, w: 4.35, h: 1.25,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      // Left accent
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.45, y, w: 0.06, h: 1.25,
        fill: { color: t.color }, line: { color: t.color }
      });
      // Icon
      const icoData = await ico(t.icon, '#' + t.color, 256);
      s.addImage({ data: icoData, x: 0.65, y: y + 0.38, w: 0.46, h: 0.46 });
      // Text
      s.addText(t.name, {
        x: 1.25, y: y + 0.18, w: 3.4, h: 0.38,
        fontSize: 13, fontFace: F_BODY, bold: true,
        color: C.ink, margin: 0,
      });
      s.addText(t.sub, {
        x: 1.25, y: y + 0.6, w: 3.4, h: 0.5,
        fontSize: 10, fontFace: F_BODY,
        color: C.mute, margin: 0,
      });

      // Arrow between layers
      if (i < 2) {
        s.addShape(pres.shapes.LINE, {
          x: 2.45, y: y + 1.25, w: 0, h: 0.18,
          line: { color: C.border, width: 1.5 }
        });
        s.addText('↕ HTTP / PDO', {
          x: 2.55, y: y + 1.25, w: 1.5, h: 0.18,
          fontSize: 7.5, fontFace: F_BODY, color: C.mute, margin: 0,
        });
      }
    }

    // Right: file structure tree
    s.addShape(pres.shapes.RECTANGLE, {
      x: 4.95, y: 0.88, w: 4.7, h: 4.5,
      fill: { color: C.black }, line: { color: C.black }, shadow: sh()
    });
    s.addText('Struktur Direktori', {
      x: 5.1, y: 0.97, w: 4.4, h: 0.3,
      fontSize: 10, fontFace: F_BODY, bold: true, color: C.teal, margin: 0,
    });

    const tree = [
      { t: 'librtrack/', d: '← root', color: 'FFFFFF', bold: true, indent: 0 },
      { t: '├── index.php', d: '← Dashboard', color: C.teal, bold: false, indent: 0.2 },
      { t: '├── login.php', d: '← Auth entry', color: C.teal, bold: false, indent: 0.2 },
      { t: '├── logout.php', d: '← Session destroy', color: C.teal, bold: false, indent: 0.2 },
      { t: '├── database.sql', d: '← Schema + seeds', color: C.amber, bold: false, indent: 0.2 },
      { t: '├── Dockerfile', d: '← PHP 8.2-apache', color: '6EE7B7', bold: false, indent: 0.2 },
      { t: '├── docker-compose.yml', d: '← 3 services', color: '6EE7B7', bold: false, indent: 0.2 },
      { t: '├── config/', d: '', color: 'FFFFFF', bold: false, indent: 0.2 },
      { t: '│   └── database.php', d: '← PDO + ENV config', color: C.mute, bold: false, indent: 0.4 },
      { t: '├── includes/', d: '', color: 'FFFFFF', bold: false, indent: 0.2 },
      { t: '│   ├── header.php', d: '← Nav + session', color: C.mute, bold: false, indent: 0.4 },
      { t: '│   ├── sidebar.php', d: '← Menu sidebar', color: C.mute, bold: false, indent: 0.4 },
      { t: '│   └── helpers.php', d: '← e(), requireAuth()', color: C.mute, bold: false, indent: 0.4 },
      { t: '└── views/', d: '', color: 'FFFFFF', bold: false, indent: 0.2 },
      { t: '    ├── books/', d: 'index|create|edit', color: C.teal, bold: false, indent: 0.5 },
      { t: '    ├── members/', d: 'index|create|edit|delete', color: C.blue, bold: false, indent: 0.5 },
      { t: '    └── transactions/', d: 'index|borrow|history', color: C.amber, bold: false, indent: 0.5 },
    ];

    for (let i = 0; i < tree.length; i++) {
      const item = tree[i];
      s.addText(item.t, {
        x: 5.1 + item.indent, y: 1.36 + i * 0.195, w: 2.8 - item.indent, h: 0.2,
        fontSize: 8, fontFace: F_MONO, bold: item.bold,
        color: item.color, margin: 0,
      });
      if (item.d) {
        s.addText(item.d, {
          x: 7.9, y: 1.36 + i * 0.195, w: 1.6, h: 0.2,
          fontSize: 7.5, fontFace: F_BODY,
          color: '6B7280', margin: 0,
        });
      }
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 3 — DATABASE SCHEMA
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '02 / Struktur Database — Schema 4 Tabel', C.blue);

    const tables = [
      {
        name: 'users', engine: 'InnoDB · utf8mb4', color: C.violet, x: 0.42, y: 0.85,
        fields: [
          { f: 'id', t: 'INT UNSIGNED PK AUTO_INCREMENT', k: 'pk' },
          { f: 'username', t: 'VARCHAR(100) UNIQUE NOT NULL', k: '' },
          { f: 'password', t: 'VARCHAR(255)  bcrypt $2y$10$…', k: '' },
          { f: 'full_name', t: 'VARCHAR(150)', k: '' },
          { f: 'role', t: "ENUM('admin','librarian')", k: '' },
          { f: 'created_at', t: 'TIMESTAMP DEFAULT NOW()', k: '' },
        ]
      },
      {
        name: 'books', engine: 'InnoDB · INDEX(status, category)', color: C.teal, x: 5.22, y: 0.85,
        fields: [
          { f: 'id', t: 'INT UNSIGNED PK AUTO_INCREMENT', k: 'pk' },
          { f: 'title', t: 'VARCHAR(255) NOT NULL', k: '' },
          { f: 'author', t: 'VARCHAR(150) NOT NULL', k: '' },
          { f: 'isbn', t: 'VARCHAR(30)', k: '' },
          { f: 'category', t: 'VARCHAR(100) INDEX', k: '' },
          { f: 'stock', t: 'INT UNSIGNED DEFAULT 1', k: '' },
          { f: 'status', t: "ENUM('Tersedia','Dipinjam') INDEX", k: '' },
        ]
      },
      {
        name: 'members', engine: 'InnoDB · INDEX(is_active)', color: C.amber, x: 0.42, y: 3.25,
        fields: [
          { f: 'id', t: 'INT UNSIGNED PK AUTO_INCREMENT', k: 'pk' },
          { f: 'name', t: 'VARCHAR(150) NOT NULL', k: '' },
          { f: 'email', t: 'VARCHAR(255) NOT NULL', k: '' },
          { f: 'phone', t: 'VARCHAR(20)', k: '' },
          { f: 'member_code', t: 'VARCHAR(20) UNIQUE  ← MBR-001', k: '' },
          { f: 'is_active', t: 'TINYINT(1) DEFAULT 1  INDEX', k: '' },
        ]
      },
      {
        name: 'transactions', engine: 'InnoDB · FK(member_id, book_id) · 4 INDEX', color: C.rose, x: 5.22, y: 3.25,
        fields: [
          { f: 'id', t: 'INT UNSIGNED PK AUTO_INCREMENT', k: 'pk' },
          { f: 'member_id', t: 'FK → members(id) RESTRICT', k: 'fk' },
          { f: 'book_id', t: 'FK → books(id) RESTRICT', k: 'fk' },
          { f: 'borrow_date', t: 'DATE NOT NULL  INDEX', k: '' },
          { f: 'due_date', t: 'DATE NOT NULL', k: '' },
          { f: 'return_date', t: 'DATE DEFAULT NULL', k: '' },
          { f: 'status', t: "ENUM('Dipinjam','Dikembalikan') INDEX", k: '' },
        ]
      },
    ];

    for (const t of tables) {
      const rowH = 0.26;
      const hdrH = 0.4;
      const totalH = hdrH + t.fields.length * rowH + 0.28;
      const W = 4.5;

      s.addShape(pres.shapes.RECTANGLE, {
        x: t.x, y: t.y, w: W, h: totalH,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      // Header
      s.addShape(pres.shapes.RECTANGLE, {
        x: t.x, y: t.y, w: W, h: hdrH,
        fill: { color: t.color }, line: { color: t.color }
      });
      s.addText(t.name, {
        x: t.x + 0.12, y: t.y + 0.02, w: W - 0.24, h: 0.26,
        fontSize: 13, fontFace: F_MONO, bold: true,
        color: C.white, margin: 0,
      });
      s.addText(t.engine, {
        x: t.x + 0.12, y: t.y + 0.24, w: W - 0.24, h: 0.18,
        fontSize: 7.5, fontFace: F_BODY, color: 'FFFFFF99', margin: 0,
      });

      for (let i = 0; i < t.fields.length; i++) {
        const rowY = t.y + hdrH + 0.04 + i * rowH;
        if (i % 2 === 0) {
          s.addShape(pres.shapes.RECTANGLE, {
            x: t.x, y: rowY, w: W, h: rowH,
            fill: { color: C.divider }, line: { color: C.divider }
          });
        }
        const isPk = t.fields[i].k === 'pk';
        const isFk = t.fields[i].k === 'fk';
        // Field name
        s.addText(t.fields[i].f, {
          x: t.x + 0.14, y: rowY + 0.02, w: 1.1, h: rowH - 0.04,
          fontSize: 8.5, fontFace: F_MONO, bold: isPk || isFk,
          color: isPk ? C.amber : isFk ? C.rose : C.ink,
          valign: 'middle', margin: 0,
        });
        s.addText(t.fields[i].t, {
          x: t.x + 1.3, y: rowY + 0.02, w: W - 1.44, h: rowH - 0.04,
          fontSize: 7.5, fontFace: F_BODY, color: C.mute,
          valign: 'middle', margin: 0,
        });
      }
    }

    // Legend
    const legend = [
      { color: C.amber, label: 'PK = Primary Key' },
      { color: C.rose, label: 'FK = Foreign Key (ON DELETE RESTRICT)' },
      { color: C.teal, label: 'INDEX = Indexed column for query performance' },
    ];
    for (let i = 0; i < legend.length; i++) {
      s.addShape(pres.shapes.OVAL, {
        x: 0.42 + i * 3.2, y: 5.28, w: 0.12, h: 0.12,
        fill: { color: legend[i].color }, line: { color: legend[i].color }
      });
      s.addText(legend[i].label, {
        x: 0.6 + i * 3.2, y: 5.22, w: 3.1, h: 0.28,
        fontSize: 8, fontFace: F_BODY, color: C.mute, margin: 0,
      });
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 4 — AUTHENTICATION FLOW
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '03 / Alur Autentikasi & Keamanan Session', C.violet);

    // Left: flowchart
    const steps = [
      { n: 1, t: 'User GET /login.php', d: 'Form login + CSRF token di-generate', c: C.violet },
      { n: 2, t: 'POST credentials', d: 'username + password + csrf_token', c: C.blue },
      { n: 3, t: 'Validasi CSRF', d: 'hash_equals(token, session_token) → timing-safe', c: C.amber },
      { n: 4, t: 'Query users WHERE username=?', d: 'PDO prepared statement — no string concat', c: C.teal },
      { n: 5, t: 'password_verify()', d: 'bcrypt $2y$10$ — cost 10, auto-salt', c: C.green },
      { n: 6, t: 'Session start + ID regenerate', d: 'session_regenerate_id(true) → prevent fixation', c: C.violet },
      { n: 7, t: 'Redirect Dashboard', d: 'header("Location: /libtrack/")', c: C.teal },
    ];

    for (let i = 0; i < steps.length; i++) {
      const st = steps[i];
      const y = 0.85 + i * 0.65;

      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.42, y, w: 4.4, h: 0.55,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      addPill(s, pres, st.n, 0.52, y + 0.085, st.c);
      s.addText(st.t, {
        x: 1.02, y: y + 0.04, w: 3.68, h: 0.26,
        fontSize: 10.5, fontFace: F_BODY, bold: true, color: C.ink, margin: 0,
      });
      s.addText(st.d, {
        x: 1.02, y: y + 0.3, w: 3.68, h: 0.22,
        fontSize: 8.5, fontFace: F_BODY, color: C.mute, margin: 0,
      });
      if (i < steps.length - 1) {
        s.addShape(pres.shapes.LINE, {
          x: 2.62, y: y + 0.55, w: 0, h: 0.1,
          line: { color: C.border, width: 1 }
        });
      }
    }

    // Fail path
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.42, y: 5.18, w: 4.4, h: 0.3,
      fill: { color: C.rose, transparency: 85 }, line: { color: C.rose, transparency: 60 }
    });
    s.addText('❌  Gagal: counter++ → lockout 5 menit setelah 5x salah', {
      x: 0.55, y: 5.18, w: 4.2, h: 0.3,
      fontSize: 9, fontFace: F_BODY, color: C.rose,
      valign: 'middle', margin: 0,
    });

    // Right: security summary cards
    const secCards = [
      {
        title: 'CSRF Protection',
        color: C.violet,
        icon: fa.FaShieldAlt,
        items: [
          'random_bytes(32) → bin2hex → 64-char token',
          'Disimpan di $_SESSION["csrf_token"]',
          'Validasi: hash_equals() tahan timing attack',
          'Setiap form POST/GET sensitif wajib token',
        ],
      },
      {
        title: 'Session Security',
        color: C.amber,
        icon: fa.FaLock,
        items: [
          'httponly flag → JS tidak bisa akses cookie',
          'SameSite=Lax → CSRF mitigasi tambahan',
          'session_regenerate_id(true) setelah login',
          'Login lockout: 5 percobaan → blokir 5 menit',
        ],
      },
      {
        title: 'Input Sanitization',
        color: C.rose,
        icon: fa.FaUserShield,
        items: [
          'cleanInput(): strip_tags() + trim()',
          'e(): htmlspecialchars(ENT_QUOTES, UTF-8)',
          'PDO ATTR_EMULATE_PREPARES = false',
          'Native prepared statements di semua query',
        ],
      },
    ];

    for (let i = 0; i < secCards.length; i++) {
      const card = secCards[i];
      const y = 0.85 + i * 1.56;
      s.addShape(pres.shapes.RECTANGLE, {
        x: 5.05, y, w: 4.6, h: 1.45,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x: 5.05, y, w: 4.6, h: 0.34,
        fill: { color: card.color }, line: { color: card.color }
      });
      const icoData = await ico(card.icon, '#FFFFFF', 256);
      s.addImage({ data: icoData, x: 5.12, y: y + 0.04, w: 0.26, h: 0.26 });
      s.addText(card.title, {
        x: 5.44, y, w: 4.1, h: 0.34,
        fontSize: 11, fontFace: F_BODY, bold: true,
        color: C.white, valign: 'middle', margin: 0,
      });
      for (let j = 0; j < card.items.length; j++) {
        s.addShape(pres.shapes.OVAL, {
          x: 5.14, y: y + 0.44 + j * 0.26 + 0.07, w: 0.08, h: 0.08,
          fill: { color: card.color }, line: { color: card.color }
        });
        s.addText(card.items[j], {
          x: 5.28, y: y + 0.44 + j * 0.26, w: 4.3, h: 0.26,
          fontSize: 8.8, fontFace: F_BODY, color: C.ink,
          valign: 'middle', margin: 0,
        });
      }
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 5 — DASHBOARD (index.php deep dive)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '04 / Dashboard — index.php', C.teal);

    // Top: 4 KPI blocks with SQL
    const kpis = [
      { label: 'Total Buku', sql: 'COUNT(*) FROM books', color: C.teal, icon: fa.FaBook },
      { label: 'Anggota Aktif', sql: "is_active = 1", color: C.blue, icon: fa.FaUsers },
      { label: 'Sedang Dipinjam', sql: "status = 'Dipinjam'", color: C.amber, icon: fa.FaExchangeAlt },
      { label: 'Terlambat', sql: "status='Dipinjam'\nAND due_date < CURDATE()", color: C.rose, icon: fa.FaExclamationTriangle },
    ];

    for (let i = 0; i < kpis.length; i++) {
      const k = kpis[i];
      const x = 0.42 + i * 2.4;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 0.82, w: 2.1, h: 1.3,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 0.82, w: 2.1, h: 0.06,
        fill: { color: k.color }, line: { color: k.color }
      });
      const icoData = await ico(k.icon, '#' + k.color, 256);
      s.addImage({ data: icoData, x: x + 0.8, y: 0.96, w: 0.45, h: 0.45 });
      s.addText(k.label, {
        x: x + 0.1, y: 1.5, w: 1.9, h: 0.3,
        fontSize: 10, fontFace: F_BODY, bold: true,
        color: C.ink, align: 'center', margin: 0,
      });
      s.addText(k.sql, {
        x: x + 0.08, y: 1.82, w: 1.94, h: 0.26,
        fontSize: 7, fontFace: F_MONO, color: C.mute,
        align: 'center', margin: 0,
      });
    }

    // Middle row: 3 widget explanations
    const widgets = [
      {
        title: 'Grafik Ketersediaan',
        color: C.teal,
        detail: [
          'Progress bar Tersedia vs Dipinjam',
          'COUNT(status=Tersedia) / total × 100',
          'Dua progress bar: teal + amber',
          'Update real-time setiap page load',
        ],
        sql: "SELECT status, COUNT(*) AS n\nFROM books\nGROUP BY status",
      },
      {
        title: 'Kategori Buku',
        color: C.blue,
        detail: [
          'Bar chart per kategori (max 8)',
          'Lebar bar proportional to count',
          'GROUP BY category ORDER BY COUNT DESC',
          'Warna berbeda per baris',
        ],
        sql: "SELECT category, COUNT(*) n\nFROM books\nGROUP BY category\nORDER BY n DESC LIMIT 8",
      },
      {
        title: 'Transaksi Terbaru',
        color: C.amber,
        detail: [
          '8 transaksi terbaru, ORDER BY created_at',
          'JOIN members + books per baris',
          'Badge overdue jika due_date < CURDATE()',
          'Link ke histori lengkap',
        ],
        sql: "SELECT t.*, m.name, b.title\nFROM transactions t\nJOIN members m ON t.member_id=m.id\nJOIN books b ON t.book_id=b.id\nORDER BY t.created_at DESC LIMIT 8",
      },
    ];

    for (let i = 0; i < widgets.length; i++) {
      const w = widgets[i];
      const x = 0.42 + i * 3.18;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 2.28, w: 3.0, h: 3.12,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 2.28, w: 3.0, h: 0.34,
        fill: { color: w.color }, line: { color: w.color }
      });
      s.addText(w.title, {
        x: x + 0.12, y: 2.28, w: 2.76, h: 0.34,
        fontSize: 10.5, fontFace: F_BODY, bold: true,
        color: C.white, valign: 'middle', margin: 0,
      });
      for (let j = 0; j < w.detail.length; j++) {
        s.addShape(pres.shapes.OVAL, {
          x: x + 0.12, y: 2.73 + j * 0.28 + 0.07, w: 0.08, h: 0.08,
          fill: { color: w.color }, line: { color: w.color }
        });
        s.addText(w.detail[j], {
          x: x + 0.26, y: 2.73 + j * 0.28, w: 2.65, h: 0.28,
          fontSize: 8.8, fontFace: F_BODY, color: C.ink,
          valign: 'middle', margin: 0,
        });
      }
      // SQL snippet
      s.addShape(pres.shapes.RECTANGLE, {
        x: x + 0.1, y: 3.94, w: 2.8, h: 1.35,
        fill: { color: C.black }, line: { color: C.black }
      });
      s.addText(w.sql, {
        x: x + 0.16, y: 3.98, w: 2.7, h: 1.28,
        fontSize: 8, fontFace: F_MONO, color: '5EEAD4',
        valign: 'top', margin: 0,
      });
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 6 — MODUL BUKU (CRUD)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '05 / Modul Manajemen Buku — views/books/', C.teal);

    // Left: 4-step CRUD flow
    const crud = [
      { step: '→ INDEX', file: 'books/index.php', q: "SELECT * FROM books ORDER BY created_at DESC", c: C.teal },
      { step: '+ CREATE', file: 'books/create.php', q: "INSERT INTO books (title,author,isbn,...) VALUES (?)", c: C.blue },
      { step: '✎ EDIT', file: 'books/edit.php', q: "UPDATE books SET title=?,author=?,... WHERE id=?", c: C.amber },
      { step: '✕ DELETE', file: 'books/delete.php (POST)', q: "DELETE FROM books WHERE id=? (cek status≠Dipinjam dulu)", c: C.rose },
    ];

    for (let i = 0; i < crud.length; i++) {
      const item = crud[i];
      const y = 0.85 + i * 1.12;
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.42, y, w: 4.4, h: 1.0,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.42, y, w: 0.06, h: 1.0,
        fill: { color: item.c }, line: { color: item.c }
      });
      s.addText(item.step, {
        x: 0.58, y: y + 0.06, w: 3.98, h: 0.3,
        fontSize: 12, fontFace: F_BODY, bold: true, color: C.ink, margin: 0,
      });
      s.addText(item.file, {
        x: 0.58, y: y + 0.36, w: 3.98, h: 0.2,
        fontSize: 8.5, fontFace: F_MONO, color: item.c, margin: 0,
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.58, y: y + 0.6, w: 4.18, h: 0.32,
        fill: { color: C.divider }, line: { color: C.divider }
      });
      s.addText(item.q, {
        x: 0.65, y: y + 0.62, w: 4.05, h: 0.28,
        fontSize: 7.8, fontFace: F_MONO, color: C.ink, margin: 0,
      });
    }

    // Right top: validation rules
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 0.85, w: 4.6, h: 2.35,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 0.85, w: 4.6, h: 0.34,
      fill: { color: C.teal }, line: { color: C.teal }
    });
    s.addText('Validasi & Constraint', {
      x: 5.18, y: 0.85, w: 4.34, h: 0.34,
      fontSize: 11, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });

    const validations = [
      ['title, author', 'Wajib diisi — not empty check'],
      ['isbn', 'Unique check sebelum INSERT/UPDATE'],
      ['year', 'Integer antara 1800 – tahun sekarang'],
      ['stock', 'Integer ≥ 1'],
      ['status', "ENUM('Tersedia','Dipinjam') — auto-controlled"],
      ['category', '<datalist> autocomplete dari DB GROUP BY'],
      ['delete constraint', 'Blokir jika status = Dipinjam'],
    ];
    for (let i = 0; i < validations.length; i++) {
      const y = 1.28 + i * 0.26;
      s.addText(validations[i][0], {
        x: 5.18, y, w: 1.2, h: 0.26,
        fontSize: 8.5, fontFace: F_MONO, bold: true, color: C.teal, margin: 0,
      });
      s.addText(validations[i][1], {
        x: 6.42, y, w: 3.15, h: 0.26,
        fontSize: 8.5, fontFace: F_BODY, color: C.ink, margin: 0,
      });
    }

    // Right bottom: status machine
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 3.33, w: 4.6, h: 2.1,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 3.33, w: 4.6, h: 0.34,
      fill: { color: C.ink }, line: { color: C.ink }
    });
    s.addText('State Machine — books.status', {
      x: 5.18, y: 3.33, w: 4.34, h: 0.34,
      fontSize: 11, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });
    // State boxes
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.3, y: 3.82, w: 1.7, h: 0.7,
      fill: { color: C.tealL }, line: { color: C.teal }
    });
    s.addText("'Tersedia'", {
      x: 5.3, y: 3.82, w: 1.7, h: 0.7,
      fontSize: 11, fontFace: F_MONO, bold: true,
      color: C.teal, align: 'center', valign: 'middle', margin: 0,
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 7.6, y: 3.82, w: 1.7, h: 0.7,
      fill: { color: C.amberL }, line: { color: C.amber }
    });
    s.addText("'Dipinjam'", {
      x: 7.6, y: 3.82, w: 1.7, h: 0.7,
      fontSize: 11, fontFace: F_MONO, bold: true,
      color: C.amber, align: 'center', valign: 'middle', margin: 0,
    });
    // Arrows
    s.addShape(pres.shapes.LINE, {
      x: 7.0, y: 4.06, w: 0.6, h: 0,
      line: { color: C.amber, width: 1.5 }
    });
    s.addText('borrow →', {
      x: 7.0, y: 3.88, w: 0.7, h: 0.18,
      fontSize: 7.5, fontFace: F_BODY, color: C.amber, align: 'center', margin: 0,
    });
    s.addShape(pres.shapes.LINE, {
      x: 7.0, y: 4.28, w: 0.6, h: 0,
      line: { color: C.teal, width: 1.5, dashType: 'dash' }
    });
    s.addText('← return', {
      x: 7.0, y: 4.3, w: 0.7, h: 0.18,
      fontSize: 7.5, fontFace: F_BODY, color: C.teal, align: 'center', margin: 0,
    });
    s.addText('books.status dikontrol eksklusif oleh transactions — bukan user input langsung', {
      x: 5.18, y: 4.68, w: 4.34, h: 0.6,
      fontSize: 9, fontFace: F_BODY, color: C.mute, margin: 0,
    });
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 7 — MODUL ANGGOTA
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '06 / Modul Manajemen Anggota — views/members/', C.amber);

    // Left: flow steps
    const steps = [
      { label: 'Registrasi Anggota', desc: 'POST form: nama, email, telepon, alamat', c: C.amber },
      { label: 'Auto-Generate Kode', desc: 'MAX(id)+1 → str_pad 3 digit → "MBR-NNN"', c: C.teal },
      { label: 'INSERT ke DB', desc: 'PDO prepared statement + member_code UNIQUE', c: C.blue },
      { label: 'Edit Data', desc: 'Update nama, email, telepon, status is_active', c: C.violet },
      { label: 'Nonaktifkan / Hapus', desc: 'Hapus diblokir jika ada transaksi aktif', c: C.rose },
    ];

    for (let i = 0; i < steps.length; i++) {
      const st = steps[i];
      const y = 0.85 + i * 0.88;
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.42, y, w: 4.4, h: 0.78,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      addPill(s, pres, i + 1, 0.52, y + 0.2, st.c);
      s.addText(st.label, {
        x: 1.02, y: y + 0.08, w: 3.68, h: 0.3,
        fontSize: 11.5, fontFace: F_BODY, bold: true, color: C.ink, margin: 0,
      });
      s.addText(st.desc, {
        x: 1.02, y: y + 0.42, w: 3.68, h: 0.28,
        fontSize: 9.5, fontFace: F_BODY, color: C.mute, margin: 0,
      });
    }

    // Right top: code snippet
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 0.85, w: 4.6, h: 2.0,
      fill: { color: C.black }, line: { color: C.black }, shadow: sh()
    });
    s.addText('Auto-Generate Kode Anggota (helpers.php)', {
      x: 5.18, y: 0.9, w: 4.34, h: 0.28,
      fontSize: 9.5, fontFace: F_BODY, bold: true, color: C.amber, margin: 0,
    });
    const codeLines = [
      { t: 'function generateMemberCode($pdo): string {', c: 'F1F5F9' },
      { t: '  $row = $pdo->query(', c: '94A3B8' },
      { t: '    "SELECT MAX(id) AS max_id FROM members"', c: '5EEAD4' },
      { t: '  )->fetch();', c: '94A3B8' },
      { t: '  $next = ($row["max_id"] ?? 0) + 1;', c: 'FCD34D' },
      { t: '  return "MBR-".str_pad($next,3,"0",STR_PAD_LEFT);', c: 'FCD34D' },
      { t: '}', c: 'F1F5F9' },
    ];
    for (let i = 0; i < codeLines.length; i++) {
      s.addText(codeLines[i].t, {
        x: 5.18, y: 1.24 + i * 0.2, w: 4.34, h: 0.2,
        fontSize: 8, fontFace: F_MONO, color: codeLines[i].c, margin: 0,
      });
    }

    // Right middle: validation table
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 3.0, w: 4.6, h: 2.42,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.05, y: 3.0, w: 4.6, h: 0.34,
      fill: { color: C.amber }, line: { color: C.amber }
    });
    s.addText('Aturan Validasi', {
      x: 5.18, y: 3.0, w: 4.34, h: 0.34,
      fontSize: 11, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });
    const vals = [
      ['name', 'Wajib diisi, string tidak kosong'],
      ['email', 'filter_var(FILTER_VALIDATE_EMAIL), unique'],
      ['member_code', 'Auto-generated, tidak bisa diubah user'],
      ['is_active', 'TINYINT 1/0 via checkbox, default 1'],
      ['hapus', 'Cek FK: ada baris transactions? → RESTRICT'],
      ['edit', 'Email unique kecuali ID yang sama (self-check)'],
    ];
    for (let i = 0; i < vals.length; i++) {
      if (i % 2 === 0) {
        s.addShape(pres.shapes.RECTANGLE, {
          x: 5.05, y: 3.42 + i * 0.32, w: 4.6, h: 0.32,
          fill: { color: C.divider }, line: { color: C.divider }
        });
      }
      s.addText(vals[i][0], {
        x: 5.18, y: 3.44 + i * 0.32, w: 1.0, h: 0.28,
        fontSize: 8.5, fontFace: F_MONO, bold: true, color: C.amber, margin: 0,
      });
      s.addText(vals[i][1], {
        x: 6.22, y: 3.44 + i * 0.32, w: 3.35, h: 0.28,
        fontSize: 8.5, fontFace: F_BODY, color: C.ink, margin: 0,
      });
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 8 — TRANSAKSI PEMINJAMAN
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '07 / Transaksi Peminjaman — views/transactions/borrow.php', C.rose);

    // Top: borrow flow (5 steps horizontal)
    const borrowFlow = [
      { n: '1', t: 'Pilih Anggota', s: 'Dropdown: is_active=1', c: C.blue },
      { n: '2', t: 'Pilih Buku', s: "status='Tersedia'", c: C.teal },
      { n: '3', t: 'Set Tanggal', s: 'borrow+due_date', c: C.amber },
      { n: '4', t: 'Validasi', s: 'Server-side check', c: C.violet },
      { n: '5', t: 'Atomic DB Txn', s: 'begin → insert → update → commit', c: C.rose },
    ];

    for (let i = 0; i < borrowFlow.length; i++) {
      const st = borrowFlow[i];
      const x = 0.42 + i * 1.88;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 0.82, w: 1.7, h: 1.0,
        fill: { color: st.c }, line: { color: st.c }, shadow: sh()
      });
      addPill(s, pres, st.n, x + 0.66, 0.88, 'FFFFFF30');
      s.addText(st.n, {
        x: x + 0.66, y: 0.88, w: 0.38, h: 0.38,
        fontSize: 10, fontFace: F_TITLE, bold: true,
        color: C.white, align: 'center', valign: 'middle', margin: 0,
      });
      s.addText(st.t, {
        x: x + 0.08, y: 1.32, w: 1.55, h: 0.3,
        fontSize: 10.5, fontFace: F_BODY, bold: true,
        color: C.white, align: 'center', margin: 0,
      });
      s.addText(st.s, {
        x: x + 0.06, y: 1.62, w: 1.58, h: 0.2,
        fontSize: 8, fontFace: F_BODY,
        color: 'FFFFFFBB', align: 'center', margin: 0,
      });
      if (i < 4) {
        s.addShape(pres.shapes.LINE, {
          x: x + 1.7, y: 1.32, w: 0.18, h: 0,
          line: { color: C.border, width: 1.5 }
        });
      }
    }

    // Middle: atomic transaction code
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.42, y: 1.98, w: 9.25, h: 1.62,
      fill: { color: C.black }, line: { color: C.black }, shadow: sh()
    });
    s.addText('Atomic DB Transaction — borrow.php', {
      x: 0.6, y: 2.03, w: 8.9, h: 0.26,
      fontSize: 10, fontFace: F_BODY, bold: true, color: C.rose, margin: 0,
    });
    const atomicLines = [
      { t: '$pdo->beginTransaction();', c: '94A3B8' },
      { t: '$stmt = $pdo->prepare("INSERT INTO transactions (member_id,book_id,borrow_date,due_date,status) VALUES (?,?,?,?,\'Dipinjam\')");', c: '5EEAD4' },
      { t: '$stmt->execute([$member_id, $book_id, $borrow_date, $due_date]);', c: '5EEAD4' },
      { t: '$upd = $pdo->prepare("UPDATE books SET status=\'Dipinjam\' WHERE id=?");', c: 'FCD34D' },
      { t: '$upd->execute([$book_id]);', c: 'FCD34D' },
      { t: '$pdo->commit();   // rollback() otomatis jika ada exception', c: '6EE7B7' },
    ];
    for (let i = 0; i < atomicLines.length; i++) {
      s.addText(atomicLines[i].t, {
        x: 0.62, y: 2.34 + i * 0.21, w: 9.0, h: 0.21,
        fontSize: 8.5, fontFace: F_MONO, color: atomicLines[i].c, margin: 0,
      });
    }

    // Bottom: 3 cards (return, overdue, history)
    const bottomCards = [
      {
        title: 'Pengembalian',
        color: C.teal,
        icon: fa.FaCheckCircle,
        items: [
          'SweetAlert2 konfirmasi dulu',
          'UPDATE transactions SET return_date=CURDATE(), status=Dikembalikan',
          "UPDATE books SET status='Tersedia'",
          'Wrapped dalam beginTransaction()/commit()',
        ],
      },
      {
        title: 'Deteksi Overdue',
        color: C.rose,
        icon: fa.FaClock,
        items: [
          "due_date < CURDATE() AND status='Dipinjam'",
          'Badge "Terlambat" + hitung +N hari',
          'Row highlight merah di tabel aktif',
          'Counter overdue tampil di KPI dashboard',
        ],
      },
      {
        title: 'Histori Transaksi',
        color: C.blue,
        icon: fa.FaHistory,
        items: [
          'JOIN 3 tabel: transactions + members + books',
          'Semua status: Dipinjam / Dikembalikan / Terlambat',
          'ORDER BY borrow_date DESC',
          'DataTables: search, sort, paginate, bahasa ID',
        ],
      },
    ];

    for (let i = 0; i < bottomCards.length; i++) {
      const card = bottomCards[i];
      const x = 0.42 + i * 3.18;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 3.72, w: 3.0, h: 1.72,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 3.72, w: 3.0, h: 0.34,
        fill: { color: card.color }, line: { color: card.color }
      });
      const icoData = await ico(card.icon, '#FFFFFF', 256);
      s.addImage({ data: icoData, x: x + 0.1, y: 3.76, w: 0.26, h: 0.26 });
      s.addText(card.title, {
        x: x + 0.42, y: 3.72, w: 2.5, h: 0.34,
        fontSize: 11, fontFace: F_BODY, bold: true,
        color: C.white, valign: 'middle', margin: 0,
      });
      for (let j = 0; j < card.items.length; j++) {
        s.addShape(pres.shapes.OVAL, {
          x: x + 0.12, y: 4.14 + j * 0.3 + 0.09, w: 0.08, h: 0.08,
          fill: { color: card.color }, line: { color: card.color }
        });
        s.addText(card.items[j], {
          x: x + 0.26, y: 4.14 + j * 0.3, w: 2.65, h: 0.3,
          fontSize: 8.5, fontFace: F_BODY, color: C.ink,
          valign: 'middle', margin: 0,
        });
      }
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 9 — DEPLOYMENT (Docker & XAMPP)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '08 / Deployment — Docker & XAMPP', C.green);

    // Left: Docker
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.42, y: 0.85, w: 4.4, h: 4.55,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.42, y: 0.85, w: 4.4, h: 0.38,
      fill: { color: C.teal }, line: { color: C.teal }
    });
    const dockerIco = await ico(fa.FaDocker, '#FFFFFF', 256);
    s.addImage({ data: dockerIco, x: 0.5, y: 0.9, w: 0.3, h: 0.3 });
    s.addText('Docker (Recommended)', {
      x: 0.86, y: 0.85, w: 3.82, h: 0.38,
      fontSize: 12, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });

    // Docker compose structure
    const services = [
      { name: 'app', img: 'php:8.2-apache', port: '8080:80', note: 'Volume mount /libtrack', c: C.teal },
      { name: 'db', img: 'mysql:8.0', port: '3306', note: 'libtrack_db + seed data', c: C.blue },
      { name: 'phpmyadmin', img: 'phpmyadmin/phpmyadmin', port: '8081:80', note: 'DB GUI admin', c: C.amber },
    ];

    for (let i = 0; i < services.length; i++) {
      const sv = services[i];
      const y = 1.36 + i * 0.88;
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.55, y, w: 4.14, h: 0.78,
        fill: { color: C.divider }, line: { color: C.border }
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x: 0.55, y, w: 0.06, h: 0.78,
        fill: { color: sv.c }, line: { color: sv.c }
      });
      s.addText(sv.name, {
        x: 0.68, y: y + 0.04, w: 1.5, h: 0.28,
        fontSize: 11, fontFace: F_MONO, bold: true, color: sv.c, margin: 0,
      });
      s.addText('image: ' + sv.img, {
        x: 0.68, y: y + 0.3, w: 3.9, h: 0.22,
        fontSize: 8, fontFace: F_MONO, color: C.ink, margin: 0,
      });
      s.addText('port: ' + sv.port + '  |  ' + sv.note, {
        x: 0.68, y: y + 0.52, w: 3.9, h: 0.2,
        fontSize: 8, fontFace: F_BODY, color: C.mute, margin: 0,
      });
    }

    // Docker commands
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.55, y: 4.04, w: 4.14, h: 1.2,
      fill: { color: C.black }, line: { color: C.black }
    });
    const cmds = [
      { t: '# Start semua service', c: '6B7280' },
      { t: 'docker compose up -d', c: '5EEAD4' },
      { t: '', c: '' },
      { t: '# Akses app: localhost:8080/libtrack', c: '6B7280' },
      { t: '# Login: admin / admin123', c: '6B7280' },
    ];
    for (let i = 0; i < cmds.length; i++) {
      if (!cmds[i].t) continue;
      s.addText(cmds[i].t, {
        x: 0.72, y: 4.1 + i * 0.22, w: 3.9, h: 0.22,
        fontSize: 8.5, fontFace: F_MONO, color: cmds[i].c, margin: 0,
      });
    }

    // Right: XAMPP + ENV
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.02, y: 0.85, w: 4.6, h: 2.35,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.02, y: 0.85, w: 4.6, h: 0.38,
      fill: { color: C.blue }, line: { color: C.blue }
    });
    s.addText('XAMPP (Fallback)', {
      x: 5.15, y: 0.85, w: 4.34, h: 0.38,
      fontSize: 12, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });
    const xamppSteps = [
      '① Copy folder ke htdocs/libtrack/',
      '② Buat DB libtrack_db di phpMyAdmin',
      '③ Import database.sql via Import tab',
      '④ Start Apache + MySQL di Control Panel',
      '⑤ Akses: localhost/libtrack → login admin/admin123',
    ];
    for (let i = 0; i < xamppSteps.length; i++) {
      s.addText(xamppSteps[i], {
        x: 5.15, y: 1.32 + i * 0.36, w: 4.34, h: 0.32,
        fontSize: 9.5, fontFace: F_BODY, color: C.ink, margin: 0,
      });
    }

    // ENV config
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.02, y: 3.33, w: 4.6, h: 2.07,
      fill: { color: C.white }, line: { color: C.border }, shadow: sh()
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.02, y: 3.33, w: 4.6, h: 0.34,
      fill: { color: C.violet }, line: { color: C.violet }
    });
    s.addText('config/database.php — ENV Config', {
      x: 5.15, y: 3.33, w: 4.34, h: 0.34,
      fontSize: 11, fontFace: F_BODY, bold: true,
      color: C.white, valign: 'middle', margin: 0,
    });
    const envCode = [
      { t: '$host = getenv("DB_HOST") ?: "db";', c: '5EEAD4' },
      { t: '$name = getenv("DB_NAME") ?: "libtrack_db";', c: '5EEAD4' },
      { t: '$user = getenv("DB_USER") ?: "root";', c: '5EEAD4' },
      { t: '$pass = getenv("DB_PASS") ?: "";', c: '5EEAD4' },
      { t: '// Docker: DB_HOST=db  |  XAMPP: DB_HOST=localhost', c: '6B7280' },
    ];
    s.addShape(pres.shapes.RECTANGLE, {
      x: 5.15, y: 3.76, w: 4.34, h: 1.5,
      fill: { color: C.black }, line: { color: C.black }
    });
    for (let i = 0; i < envCode.length; i++) {
      s.addText(envCode[i].t, {
        x: 5.24, y: 3.82 + i * 0.26, w: 4.15, h: 0.26,
        fontSize: 8, fontFace: F_MONO, color: envCode[i].c, margin: 0,
      });
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 10 — ALUR END-TO-END (FULL FLOW)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.black };

    s.addText('Alur Sistem End-to-End', {
      x: 0.5, y: 0.18, w: 9, h: 0.52,
      fontSize: 22, fontFace: F_TITLE, bold: true, color: C.white, margin: 0,
    });
    s.addText('Setiap permintaan melewati autentikasi session + CSRF validation + PDO prepared statements + output escaping', {
      x: 0.5, y: 0.72, w: 9, h: 0.24,
      fontSize: 9, fontFace: F_BODY, color: C.mute, margin: 0,
    });

    const phases = [
      { n: '1', title: 'Login', desc: 'bcrypt verify\nSession start\nID regenerate', c: C.violet, icon: fa.FaSignInAlt },
      { n: '2', title: 'Dashboard', desc: 'KPI queries\nRecent txn JOIN\nCategory chart', c: C.teal, icon: fa.FaChartBar },
      { n: '3', title: 'Kelola Buku', desc: 'CRUD + validasi\nISBN unique\nStatus ENUM', c: C.teal, icon: fa.FaBook },
      { n: '4', title: 'Kelola Anggota', desc: 'Registrasi\nAuto kode MBR\nActive toggle', c: C.amber, icon: fa.FaUsers },
      { n: '5', title: 'Pinjam Buku', desc: 'Validasi ganda\nAtomic INSERT\nStatus Dipinjam', c: C.rose, icon: fa.FaHandHoldingOpen || fa.FaExchangeAlt },
      { n: '6', title: 'Monitor Aktif', desc: 'List Dipinjam\nOverdue badge\nDue date check', c: C.rose, icon: fa.FaClock },
      { n: '7', title: 'Kembalikan', desc: 'Atomic UPDATE\nDikembalikan\nStok reset', c: C.green, icon: fa.FaCheckCircle },
      { n: '8', title: 'Histori', desc: 'Full log JOIN\nAll statuses\nDataTables', c: C.blue, icon: fa.FaHistory },
    ];

    // Row 1: phases 1-4
    // Row 2: phases 5-8
    for (let row = 0; row < 2; row++) {
      const rowPhases = phases.slice(row * 4, row * 4 + 4);
      const rowY = row === 0 ? 1.08 : 3.18;
      for (let i = 0; i < rowPhases.length; i++) {
        const ph = rowPhases[i];
        const x = 0.42 + i * 2.35;
        s.addShape(pres.shapes.RECTANGLE, {
          x, y: rowY, w: 2.08, h: 1.85,
          fill: { color: ph.c, transparency: 88 },
          line: { color: ph.c, transparency: 60 }
        });
        // Number + Icon
        const icoData = await ico(ph.icon, '#' + ph.c, 256);
        s.addImage({ data: icoData, x: x + 0.77, y: rowY + 0.18, w: 0.42, h: 0.42 });
        s.addText(ph.title, {
          x: x + 0.08, y: rowY + 0.7, w: 1.92, h: 0.34,
          fontSize: 12, fontFace: F_BODY, bold: true,
          color: C.white, align: 'center', margin: 0,
        });
        s.addText(ph.desc, {
          x: x + 0.08, y: rowY + 1.06, w: 1.92, h: 0.68,
          fontSize: 8.5, fontFace: F_BODY,
          color: 'FFFFFFAA', align: 'center', margin: 0,
        });
        // Arrow between
        if (i < 3) {
          s.addShape(pres.shapes.LINE, {
            x: x + 2.08, y: rowY + 0.9, w: 0.27, h: 0,
            line: { color: C.mute, width: 1 }
          });
        }
      }
    }

    // Connecting arrow row1 → row2
    s.addShape(pres.shapes.LINE, {
      x: 9.4, y: 2.93, w: 0, h: 0.25,
      line: { color: C.mute, width: 1, dashType: 'dash' }
    });
    s.addShape(pres.shapes.LINE, {
      x: 0.42, y: 3.18, w: 8.98, h: 0,
      line: { color: 'FFFFFF15', width: 1 }
    });

    // Bottom security bar
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0.42, y: 5.14, w: 9.25, h: 0.35,
      fill: { color: C.teal, transparency: 85 },
      line: { color: C.teal, transparency: 60 }
    });
    s.addText('🔒  Session Auth  ·  CSRF Token  ·  PDO Prepared Statements  ·  htmlspecialchars()  ·  bcrypt  ·  Login Lockout', {
      x: 0.55, y: 5.14, w: 9.08, h: 0.35,
      fontSize: 9, fontFace: F_BODY,
      color: C.teal, align: 'center', valign: 'middle', margin: 0,
    });
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 11 — STACK TEKNIS (TECH DEEP DIVE)
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.offWh };
    addHeader(s, pres, '09 / Stack Teknis — Frontend & Backend Libraries', C.ink);

    const stacks = [
      {
        cat: 'Frontend', color: C.blue,
        items: [
          { n: 'Bootstrap 5.3', d: 'Responsive grid, komponen UI, dark/light mode support' },
          { n: 'Bootstrap Icons', d: 'Icon set 2000+ SVG, in-line dengan class bi bi-*' },
          { n: 'DataTables 1.13', d: 'Server-side ready, search/sort/paginate, bahasa Indonesia' },
          { n: 'SweetAlert2', d: 'Modal dialog interaktif, konfirmasi hapus/return buku' },
          { n: 'Syne + Plus Jakarta Sans', d: 'Custom Google Fonts lewat CDN, tersimpan di CSS vars' },
        ]
      },
      {
        cat: 'Backend PHP', color: C.teal,
        items: [
          { n: 'PHP 8.2', d: 'Named args, enums, readonly props, fibers — latest stable' },
          { n: 'PDO + MySQLi', d: 'ATTR_EMULATE_PREPARES=false → native prepared statements' },
          { n: 'password_hash()', d: 'bcrypt cost=10, auto-salt, tahan rainbow table' },
          { n: 'random_bytes(32)', d: 'CSPRNG untuk CSRF token 64-char hex' },
          { n: 'session_regenerate_id()', d: 'Cegah session fixation setelah login berhasil' },
        ]
      },
      {
        cat: 'Database', color: C.amber,
        items: [
          { n: 'MySQL 8.0 InnoDB', d: 'ACID transactions, row-level locking, foreign keys' },
          { n: 'utf8mb4_unicode_ci', d: 'Full Unicode support termasuk emoji dan karakter khusus' },
          { n: 'INDEX(status)', d: 'Index pada kolom paling sering difilter → query cepat' },
          { n: 'ON DELETE RESTRICT', d: 'FK transactions → books/members tidak bisa hapus jika ada relasi' },
          { n: 'beginTransaction()', d: 'Borrow & return atomic: INSERT+UPDATE atau keduanya rollback' },
        ]
      },
      {
        cat: 'DevOps', color: C.green,
        items: [
          { n: 'Docker Compose', d: '3 services: app (PHP-apache), db (MySQL 8), phpmyadmin' },
          { n: 'php:8.2-apache', d: 'Official image, mod_rewrite enabled, healtcheck ready' },
          { n: 'Volume mount', d: './:/var/www/html/libtrack → live code reload' },
          { n: 'ENV variables', d: 'DB_HOST, DB_NAME, DB_USER, DB_PASS via docker-compose env' },
          { n: 'XAMPP fallback', d: 'Auto-detect: DB_HOST=localhost untuk local development' },
        ]
      },
    ];

    for (let col = 0; col < 4; col++) {
      const st = stacks[col];
      const x = 0.42 + col * 2.38;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 0.82, w: 2.18, h: 4.65,
        fill: { color: C.white }, line: { color: C.border }, shadow: sh()
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x, y: 0.82, w: 2.18, h: 0.38,
        fill: { color: st.color }, line: { color: st.color }
      });
      s.addText(st.cat, {
        x: x + 0.08, y: 0.82, w: 2.02, h: 0.38,
        fontSize: 11, fontFace: F_BODY, bold: true,
        color: C.white, valign: 'middle', margin: 0,
      });
      for (let j = 0; j < st.items.length; j++) {
        const item = st.items[j];
        const itemY = 1.28 + j * 0.84;
        s.addShape(pres.shapes.RECTANGLE, {
          x: x + 0.1, y: itemY, w: 1.98, h: 0.74,
          fill: { color: j % 2 === 0 ? C.offWh : C.white }, line: { color: C.divider }
        });
        s.addText(item.n, {
          x: x + 0.16, y: itemY + 0.04, w: 1.86, h: 0.28,
          fontSize: 9, fontFace: F_MONO, bold: true, color: st.color, margin: 0,
        });
        s.addText(item.d, {
          x: x + 0.16, y: itemY + 0.34, w: 1.86, h: 0.36,
          fontSize: 8, fontFace: F_BODY, color: C.mute, margin: 0,
        });
      }
    }
  }

  // ══════════════════════════════════════════════════════════
  // SLIDE 12 — CLOSING
  // ══════════════════════════════════════════════════════════
  {
    const s = pres.addSlide();
    s.background = { color: C.black };

    // Left: big teal accent
    s.addShape(pres.shapes.RECTANGLE, {
      x: 0, y: 0, w: 4.2, h: 5.625,
      fill: { color: C.teal, transparency: 0 }, line: { color: C.teal }
    });

    // Title on teal
    const bookIco2 = await ico(fa.FaBook, '#FFFFFF', 512);
    s.addImage({ data: bookIco2, x: 1.85, y: 0.55, w: 0.55, h: 0.55 });
    s.addText('LibTrack', {
      x: 0.38, y: 1.22, w: 3.64, h: 0.72,
      fontSize: 38, fontFace: F_TITLE, bold: true, color: C.white, margin: 0,
    });
    s.addText('Academic Library\nManagement System', {
      x: 0.38, y: 2.0, w: 3.64, h: 0.62,
      fontSize: 13, fontFace: F_BODY, color: 'FFFFFFBB', margin: 0,
    });

    // Summary list
    const summaryItems = [
      '4 modul terintegrasi (books, members, transactions, dashboard)',
      'Atomic DB transactions — consistency terjamin',
      'Keamanan: CSRF, bcrypt, PDO, session lockout',
      'Deployment: Docker 1-command atau XAMPP',
      'UI responsif — Bootstrap 5 + DataTables',
      'Deteksi overdue real-time — due_date < CURDATE()',
    ];
    for (let i = 0; i < summaryItems.length; i++) {
      s.addShape(pres.shapes.OVAL, {
        x: 0.4, y: 3.05 + i * 0.4 + 0.13, w: 0.1, h: 0.1,
        fill: { color: C.white }, line: { color: C.white }
      });
      s.addText(summaryItems[i], {
        x: 0.6, y: 3.05 + i * 0.4, w: 3.45, h: 0.38,
        fontSize: 10, fontFace: F_BODY, color: C.white,
        valign: 'middle', margin: 0,
      });
    }

    // Right: repo link + tech list
    s.addText('Repositori', {
      x: 4.5, y: 0.55, w: 5.1, h: 0.38,
      fontSize: 14, fontFace: F_TITLE, bold: true, color: C.white, margin: 0,
    });
    s.addShape(pres.shapes.RECTANGLE, {
      x: 4.5, y: 1.02, w: 5.1, h: 0.38,
      fill: { color: C.teal, transparency: 85 }, line: { color: C.teal, transparency: 60 }
    });
    s.addText('github.com/lanss-id/librtrack', {
      x: 4.6, y: 1.02, w: 4.9, h: 0.38,
      fontSize: 10.5, fontFace: F_MONO, color: C.teal,
      valign: 'middle', margin: 0,
    });

    s.addText('Stack', {
      x: 4.5, y: 1.58, w: 5.1, h: 0.34,
      fontSize: 14, fontFace: F_TITLE, bold: true, color: C.white, margin: 0,
    });

    const techs = [
      { l: 'PHP', v: '8.2', c: C.violet },
      { l: 'MySQL', v: '8.0', c: C.blue },
      { l: 'Apache', v: 'mod_rewrite', c: C.teal },
      { l: 'Bootstrap', v: '5.3', c: C.blue },
      { l: 'Docker', v: 'Compose v3', c: C.teal },
      { l: 'DataTables', v: '1.13', c: C.amber },
      { l: 'SweetAlert2', v: 'Latest', c: C.rose },
      { l: 'PDO', v: 'native prepared', c: C.green },
    ];

    for (let i = 0; i < techs.length; i++) {
      const t = techs[i];
      const x = 4.5 + (i % 2) * 2.6;
      const y = 2.04 + Math.floor(i / 2) * 0.72;
      s.addShape(pres.shapes.RECTANGLE, {
        x, y, w: 2.38, h: 0.6,
        fill: { color: 'FFFFFF08' }, line: { color: 'FFFFFF15' }
      });
      s.addShape(pres.shapes.RECTANGLE, {
        x, y, w: 0.06, h: 0.6,
        fill: { color: t.c }, line: { color: t.c }
      });
      s.addText(t.l, {
        x: x + 0.14, y: y + 0.04, w: 2.16, h: 0.3,
        fontSize: 11, fontFace: F_BODY, bold: true, color: C.white, margin: 0,
      });
      s.addText(t.v, {
        x: x + 0.14, y: y + 0.34, w: 2.16, h: 0.22,
        fontSize: 8.5, fontFace: F_BODY, color: C.mute, margin: 0,
      });
    }

    // Footer
    s.addShape(pres.shapes.RECTANGLE, {
      x: 4.5, y: 5.04, w: 5.1, h: 0.42,
      fill: { color: C.teal }, line: { color: C.teal }
    });
    s.addText('Terima Kasih — LibTrack Academic Library Management System', {
      x: 4.5, y: 5.04, w: 5.1, h: 0.42,
      fontSize: 9.5, fontFace: F_BODY, bold: true,
      color: C.white, align: 'center', valign: 'middle', margin: 0,
    });
  }

  // ── Write output ─────────────────────────────────────────
  const outPath = './LibTrack.pptx';
  await pres.writeFile({ fileName: outPath });
  console.log('Done:', outPath);
})();
