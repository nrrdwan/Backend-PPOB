# API Documentation - Forgot Password & Reset Password

## Base URL
```
http://127.0.0.1:8000/api
```

## ✅ Status: TESTED & WORKING

Semua endpoint sudah ditest dan berfungsi dengan baik!

## Endpoints

### 1. Forgot Password (Send OTP) ✅
**Endpoint:** `POST /auth/forgot-password`

**Description:** Mengirim kode OTP 4 digit ke email pengguna untuk reset password. OTP berlaku selama 1 menit.

**Request Body:**
```json
{
    "email": "test@example.com"
}
```

**Response Success (200):**
```json
{
    "success": true,
    "message": "Kode OTP telah dikirim ke email Anda",
    "data": {
        "email": "test@example.com",
        "expires_in_seconds": 60,
        "otp_for_testing": "4676"
    }
}
```

**Response Error (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

### 2. Reset Password (Verify OTP & Change Password) ✅
**Endpoint:** `POST /auth/reset-password`

**Description:** Verifikasi kode OTP dan mengubah password pengguna.

**Request Body:**
```json
{
    "email": "test@example.com",
    "otp": "4676",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response Success (200):**
```json
{
    "success": true,
    "message": "Password berhasil direset. Silakan login dengan password baru Anda."
}
```

**Response Error - Invalid OTP (422):**
```json
{
    "success": false,
    "message": "Kode OTP tidak valid atau sudah kadaluarsa"
}
```

**Response Error - Validation (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "otp": [
            "The otp field must be 4 characters."
        ],
        "password": [
            "The password field confirmation does not match."
        ]
    }
}
```

## Testing Steps in Postman

### Step 1: Test Forgot Password ✅
1. Buat request baru di Postman
2. Set method ke `POST`
3. Set URL ke `http://127.0.0.1:8000/api/auth/forgot-password`
4. Di tab Headers, tambahkan:
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Di tab Body, pilih `raw` dan `JSON`, lalu masukkan:
   ```json
   {
       "email": "test@example.com"
   }
   ```
6. Klik Send
7. ✅ **Tested Result:** Response berhasil dengan OTP code

### Step 2: Test Reset Password ✅
1. Buat request baru di Postman
2. Set method ke `POST`
3. Set URL ke `http://127.0.0.1:8000/api/auth/reset-password`
4. Di tab Headers, tambahkan:
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Di tab Body, pilih `raw` dan `JSON`, lalu masukkan:
   ```json
   {
       "email": "test@example.com",
       "otp": "4676",
       "password": "newpassword123",
       "password_confirmation": "newpassword123"
   }
   ```
6. Klik Send
7. ✅ **Tested Result:** Password berhasil direset

### Step 3: Test Login dengan Password Baru ✅
1. Buat request baru di Postman
2. Set method ke `POST`
3. Set URL ke `http://127.0.0.1:8000/api/auth/login`
4. Di tab Headers, tambahkan:
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Di tab Body, pilih `raw` dan `JSON`, lalu masukkan:
   ```json
   {
       "email": "test@example.com",
       "password": "newpassword123"
   }
   ```
6. Klik Send
7. ✅ **Tested Result:** Login berhasil dengan password baru

## Test Cases

### 1. Email tidak valid ✅
**Request:**
```json
{
    "email": "invalid-email"
}
```
**Expected:** Error validation ✅

### 2. Email tidak terdaftar ✅
**Request:**
```json
{
    "email": "notfound@example.com"
}
```
**Expected:** Error "email tidak ditemukan" ✅

### 3. OTP sudah kadaluarsa
**Scenario:** Tunggu lebih dari 1 menit setelah request OTP, lalu coba reset password
**Expected:** Error "OTP kadaluarsa"

### 4. OTP salah ✅
**Request:**
```json
{
    "email": "test@example.com",
    "otp": "9999",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```
**Expected:** Error "OTP tidak valid" ✅ **Tested & Working**

### 5. Password confirmation tidak match
**Request:**
```json
{
    "email": "test@example.com",
    "otp": "4676",
    "password": "newpassword123",
    "password_confirmation": "differentpassword"
}
```
**Expected:** Error validation

## Features Implemented

✅ **4-digit OTP Generation:** Menggunakan `str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)`
✅ **1-minute Expiration:** OTP berlaku selama 60 detik
✅ **Email Delivery:** OTP dikirim via email (saat ini menggunakan log driver untuk testing)
✅ **Security:** Semua token existing dihapus setelah password reset
✅ **Validation:** Input validation untuk semua field
✅ **Error Handling:** Comprehensive error handling dengan logging
✅ **Database:** Table `password_otps` untuk menyimpan OTP
✅ **Cleanup:** OTP otomatis dihapus setelah digunakan atau expired

## Database Schema

### Table: password_otps
- `id` (bigint, primary key)
- `email` (string, indexed)
- `otp` (string, 4 characters)
- `expires_at` (timestamp)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Troubleshooting

### ❌ Problem: Error "email field required"
**Cause:** Biasanya karena:
1. Header `Content-Type: application/json` tidak ada
2. Body request tidak dalam format JSON yang valid
3. Field email tidak ada dalam request body

**Solution:** 
- Pastikan header `Content-Type: application/json` dan `Accept: application/json` ada
- Pastikan body dalam format JSON yang valid
- Contoh request yang benar sudah ada di dokumentasi di atas

### ❌ Problem: SMTP Authentication Error
**Cause:** Konfigurasi email SMTP tidak valid atau password salah

**Solution:** 
- Untuk testing, gunakan `MAIL_MAILER=log` di file `.env`
- Untuk production, pastikan kredensial email Gmail benar dan gunakan App Password

### ✅ Current Configuration
- `MAIL_MAILER=log` - Email akan ditulis ke log file, tidak dikirim sungguhan
- OTP tetap dikembalikan di response untuk testing (`otp_for_testing`)

## Security Notes

1. **OTP for Testing:** Field `otp_for_testing` dalam response hanya untuk development. Hapus di production!
2. **Rate Limiting:** Pertimbangkan menambahkan rate limiting untuk endpoint forgot password
3. **Email Verification:** Pastikan konfigurasi email sudah benar di file `.env`
4. **Logging:** Semua aktivitas login/logout/reset password dicatat di log file
