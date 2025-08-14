# CARA SETUP GMAIL UNTUK KIRIM EMAIL OTP

## Langkah 1: Setup Gmail App Password

1. **Login ke Gmail:**
   - Buka https://myaccount.google.com/
   - Login dengan `instantcm.id@gmail.com`

2. **Aktifkan 2-Factor Authentication:**
   - Klik "Security" di sidebar kiri
   - Scroll ke "2-Step Verification"
   - Jika belum aktif, klik "Get started" dan ikuti petunjuk

3. **Buat App Password:**
   - Masih di halaman Security
   - Scroll ke "App passwords"
   - Klik "App passwords"
   - Pilih "Mail" dan "Windows Computer"
   - Atau pilih "Other" dan ketik "PPOB Laravel"
   - Klik "Generate"
   - **Copy password 16 karakter yang muncul** (contoh: `abcd efgh ijkl mnop`)

## Langkah 2: Update .env File

Ganti konfigurasi di file `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=instantcm.id@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="instantcm.id@gmail.com"
MAIL_FROM_NAME="PPOB System"
```

**PENTING:** 
- Ganti `abcdefghijklmnop` dengan App Password 16 karakter yang Anda dapat
- Jangan pakai password login Gmail biasa!
- Tulis App Password tanpa spasi

## Langkah 3: Clear Cache dan Test

```bash
php artisan config:clear
php artisan cache:clear
```

Lalu test API:
```bash
POST http://127.0.0.1:8000/api/auth/forgot-password
Body: {"email": "email_tujuan_anda@gmail.com"}
```

Email OTP akan dikirim ke email yang Anda masukkan di body request.

## Alternative: Mailtrap (Lebih Mudah untuk Testing)

1. **Daftar di Mailtrap:**
   - Buka https://mailtrap.io/
   - Klik "Sign Up" (gratis)
   - Verifikasi email

2. **Setup Inbox:**
   - Login ke dashboard
   - Klik "Add Inbox"
   - Nama: "PPOB Testing"
   - Copy SMTP credentials

3. **Update .env:**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_username_dari_mailtrap
   MAIL_PASSWORD=your_password_dari_mailtrap
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="noreply@ppob.com"
   MAIL_FROM_NAME="PPOB System"
   ```

4. **Keuntungan Mailtrap:**
   - Email tidak dikirim ke email asli
   - Bisa lihat email di dashboard Mailtrap
   - Tidak perlu setup 2FA
   - Aman untuk testing

## Pilih Salah Satu dan Test

Setelah setup salah satu opsi di atas:

1. Update .env sesuai pilihan
2. Run: `php artisan config:clear`
3. Test API dengan email tujuan apa saja
4. Cek email masuk (Gmail asli atau Mailtrap dashboard)
