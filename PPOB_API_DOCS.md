# 📱 PPOB API Documentation - Complete Guide

## 🚀 Base URL untuk Development
```
http://127.0.0.1:8000/api
```

## 🔐 Authentication
Semua endpoint (kecuali register, login, health) memerlukan Bearer Token:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📋 **1. Authentication Endpoints**

### Register User
```bash
POST /api/auth/register
```
**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com", 
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "081234567890"
}
```

### Login User
```bash
POST /api/auth/login
```
**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123",
    "device_name": "iPhone 15 Pro"
}
```

### Get Profile
```bash
GET /api/auth/profile
Authorization: Bearer TOKEN
```

### Logout
```bash
POST /api/auth/logout
Authorization: Bearer TOKEN
```

---

## 💰 **2. Wallet Management**

### Cek Saldo User
```bash
GET /api/wallet/balance
Authorization: Bearer TOKEN
```
**Response:**
```json
{
    "success": true,
    "message": "Balance retrieved successfully",
    "data": {
        "user_id": 1,
        "balance": 100000.00,
        "formatted_balance": "100.000"
    }
}
```

### Top Up Saldo
```bash
POST /api/wallet/topup
Authorization: Bearer TOKEN
```
**Request Body:**
```json
{
    "amount": 100000,
    "payment_method": "bank_transfer",
    "notes": "Top up via bank transfer"
}
```
**Response:**
```json
{
    "success": true,
    "message": "Top up successful",
    "data": {
        "transaction_id": "TRX20250811ABC123",
        "amount": 100000,
        "new_balance": 200000.00,
        "formatted_balance": "200.000"
    }
}
```

### History Saldo
```bash
GET /api/wallet/history?type=all&limit=20
Authorization: Bearer TOKEN
```
**Query Parameters:**
- `type`: `all`, `topup`, `purchase`
- `limit`: 1-100 (default: 20)
- `page`: pagination

---

## 🛒 **3. PPOB Products & Categories**

### Get Categories
```bash
GET /api/ppob/categories
Authorization: Bearer TOKEN
```
**Response:**
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": "pulsa",
                "name": "Pulsa & Paket Data", 
                "icon": "phone",
                "description": "Isi pulsa dan beli paket data untuk semua operator"
            },
            {
                "id": "pln",
                "name": "PLN (Listrik)",
                "icon": "zap", 
                "description": "Token listrik prabayar dan bayar tagihan pascabayar"
            }
        ]
    }
}
```

### Get Products by Category
```bash
GET /api/ppob/products?category=pulsa&provider=Telkomsel
Authorization: Bearer TOKEN
```
**Query Parameters:**
- `category`: `pulsa`, `pln`, `pdam`, `game`, `emoney`, `other`
- `provider`: (optional) filter by provider
- `search`: (optional) search products

**Response:**
```json
{
    "success": true,
    "data": {
        "category": "pulsa",
        "total_products": 15,
        "providers": [
            {
                "provider": "Telkomsel",
                "products": [
                    {
                        "id": 1,
                        "code": "TSEL5",
                        "name": "Telkomsel Pulsa 5.000",
                        "type": "pulsa",
                        "price": 5500,
                        "admin_fee": 500,
                        "selling_price": 6000,
                        "formatted_selling_price": "6.000",
                        "is_available": true
                    }
                ]
            }
        ]
    }
}
```

### Get Product Detail
```bash
GET /api/ppob/products/{productId}
Authorization: Bearer TOKEN
```

---

## 💳 **4. Purchase & Transactions**

### Purchase Product
```bash
POST /api/ppob/purchase
Authorization: Bearer TOKEN
```
**Request Body:**
```json
{
    "product_id": 1,
    "phone_number": "081234567890",
    "customer_id": "12345678901",
    "notes": "Pembelian pulsa untuk nomor pribadi"
}
```
**Response Success:**
```json
{
    "success": true,
    "message": "Transaction created successfully",
    "data": {
        "transaction": {
            "id": 123,
            "transaction_id": "TRX20250811XYZ789",
            "product_name": "Telkomsel Pulsa 5.000",
            "amount": 5500,
            "admin_fee": 500,
            "total_amount": 6000,
            "status": "pending",
            "phone_number": "081234567890"
        }
    }
}
```

**Response Insufficient Balance:**
```json
{
    "success": false,
    "message": "Insufficient balance",
    "data": {
        "required_amount": 6000,
        "current_balance": 5000,
        "shortage": 1000
    }
}
```

### Cek Status Transaksi
```bash
GET /api/ppob/transaction/{transactionId}
Authorization: Bearer TOKEN
```
**Response:**
```json
{
    "success": true,
    "data": {
        "transaction": {
            "id": 123,
            "transaction_id": "TRX20250811XYZ789",
            "product": {
                "name": "Telkomsel Pulsa 5.000",
                "type": "pulsa",
                "provider": "Telkomsel"
            },
            "status": "success",
            "total_amount": 6000,
            "phone_number": "081234567890",
            "provider_response": {
                "status": "success",
                "reference_id": "REF20250811123456"
            }
        }
    }
}
```

### History Transaksi
```bash
GET /api/ppob/transactions?status=success&type=pulsa&limit=10
Authorization: Bearer TOKEN
```
**Query Parameters:**
- `status`: `pending`, `processing`, `success`, `failed`, `cancelled`
- `type`: `pulsa`, `pln`, `pdam`, `game`, `emoney`, `other`
- `limit`: 1-100 (default: 20)
- `page`: pagination

---

## 🔄 **Flow Pembelian Lengkap**

### 1. **Setup User & Balance**
```bash
# Login
POST /api/auth/login

# Cek saldo
GET /api/wallet/balance

# Top up jika perlu
POST /api/wallet/topup
```

### 2. **Browse Products**
```bash
# Lihat kategori
GET /api/ppob/categories

# Lihat produk pulsa
GET /api/ppob/products?category=pulsa

# Detail produk tertentu
GET /api/ppob/products/1
```

### 3. **Purchase Process**
```bash
# Beli produk
POST /api/ppob/purchase

# Cek status transaksi
GET /api/ppob/transaction/TRX20250811XYZ789

# Cek saldo setelah pembelian
GET /api/wallet/balance
```

### 4. **History & Monitoring**
```bash
# History transaksi
GET /api/ppob/transactions

# History saldo
GET /api/wallet/history
```

---

## 📊 **Status Transaksi**

| Status | Deskripsi |
|--------|-----------|
| `pending` | Transaksi baru dibuat, menunggu proses |
| `processing` | Sedang diproses oleh provider |
| `success` | Transaksi berhasil |
| `failed` | Transaksi gagal |
| `cancelled` | Transaksi dibatalkan |

---

## 🎯 **Error Codes**

| Code | Message | Description |
|------|---------|-------------|
| 422 | Validation error | Input tidak valid |
| 401 | Unauthenticated | Token tidak valid/expired |
| 400 | Insufficient balance | Saldo tidak cukup |
| 404 | Not found | Data tidak ditemukan |
| 500 | Internal server error | Error server |

---

## 🚀 **Testing dengan PowerShell**

```powershell
# Jalankan complete test
.\complete-api-test.ps1
```

---

## 📱 **Integrasi Flutter**

### HTTP Client Setup (Dio)
```dart
import 'package:dio/dio.dart';

class ApiService {
  late Dio _dio;
  static const String baseUrl = 'http://127.0.0.1:8000/api';
  
  ApiService() {
    _dio = Dio();
    _dio.options.baseUrl = baseUrl;
    _dio.options.headers['Content-Type'] = 'application/json';
  }
  
  void setToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }
}
```

### Example: Purchase Product
```dart
Future<Map<String, dynamic>> purchaseProduct({
  required int productId,
  String? phoneNumber,
  String? customerId,
  String? notes,
}) async {
  try {
    final response = await _dio.post('/ppob/purchase', data: {
      'product_id': productId,
      'phone_number': phoneNumber,
      'customer_id': customerId,
      'notes': notes,
    });
    
    return response.data;
  } catch (e) {
    throw Exception('Purchase failed: $e');
  }
}
```

**🎉 API PPOB lengkap siap untuk Flutter integration!**
