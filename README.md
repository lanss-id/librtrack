# LibTrack ERP - README
# Cara Menjalankan di Linux / Windows / Mac

## 🐳 Cara 1: Docker (Recommended — Linux, Windows, Mac)

> Satu perintah, jalan di semua OS.

### Prasyarat
- Install [Docker Desktop](https://www.docker.com/products/docker-desktop/)
  - Windows: Docker Desktop for Windows
  - Mac: Docker Desktop for Mac  
  - Linux: `sudo apt install docker.io docker-compose` atau ikuti [panduan resmi](https://docs.docker.com/engine/install/)

### Jalankan

```bash
# Clone / masuk ke folder proyek
cd joki_adink

# Jalankan semua service (pertama kali agak lama karena download image)
docker compose up -d

# Cek status container
docker compose ps
```

### Akses Aplikasi

| Service      | URL                            | Keterangan               |
|--------------|--------------------------------|--------------------------|
| LibTrack ERP | http://localhost:8080/libtrack | Aplikasi utama           |
| phpMyAdmin   | http://localhost:8081          | Manajemen database       |

**Login default:** `admin` / `admin123`

### Hentikan Aplikasi

```bash
docker compose down          # stop container (data tetap tersimpan)
docker compose down -v       # stop + hapus database (reset total)
```

---

## 🪟 Cara 2: XAMPP (Windows / Mac / Linux)

### Prasyarat
- Install [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL)

### Langkah-langkah

1. **Copy folder proyek ke htdocs**

   | OS      | Path htdocs                              |
   |---------|------------------------------------------|
   | Windows | `C:\xampp\htdocs\libtrack\`              |
   | Mac     | `/Applications/XAMPP/htdocs/libtrack/`   |
   | Linux   | `/opt/lampp/htdocs/libtrack/`            |

2. **Import database**
   - Buka XAMPP Control Panel → Start **Apache** dan **MySQL**
   - Buka browser → http://localhost/phpmyadmin
   - Buat database baru bernama `libtrack_db`
   - Klik tab **Import** → pilih file `database.sql` → klik Go

3. **Akses aplikasi**
   - Buka http://localhost/libtrack
   - Login: `admin` / `admin123`

> **Catatan config:** `config/database.php` sudah auto-detect. Untuk XAMPP,
> pastikan `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=` (kosong).

---

## 🐧 Cara 3: PHP Built-in Server (Linux/Mac — untuk testing cepat)

> Tidak perlu Apache/XAMPP, tapi MySQL tetap harus jalan.

```bash
# Install MySQL jika belum ada (Linux)
sudo apt install mysql-server

# Import database
mysql -u root -p < database.sql

# Jalankan PHP built-in server dari folder root proyek
php -S localhost:8080 -t .

# Akses: http://localhost:8080/libtrack
```

---

## 📁 Struktur Direktori

```
joki_adink/  ← root (htdocs/libtrack/ untuk XAMPP)
├── index.php          ← Dashboard
├── login.php
├── logout.php
├── database.sql       ← Schema + seed data
├── Dockerfile         ← Docker build
├── docker-compose.yml ← Docker orchestration
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│   └── helpers.php
├── assets/
│   ├── css/app.css
│   └── js/app.js
└── views/
    ├── books/         (index, create, edit)
    ├── members/       (index, create, edit, delete)
    └── transactions/  (index, borrow, history)
```

---

## ❓ Troubleshooting

### Docker: port 8080 atau 3306 sudah dipakai
Edit `docker-compose.yml`, ganti port kiri:
```yaml
ports:
  - "9090:80"    # ganti 8080 jadi 9090
```

### XAMPP: error "Database Connection Failed"
- Pastikan MySQL sudah Start di XAMPP Control Panel
- Pastikan nama database adalah `libtrack_db`
- Cek `config/database.php` — `DB_USER=root`, `DB_PASS=` (kosong)

### Windows XAMPP: path htdocs
Pastikan folder ditempatkan di `C:\xampp\htdocs\libtrack\` (bukan subfolder lain)
