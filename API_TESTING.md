# PPOB API Testing Guide

## Base URL
```
http://localhost/ppob-admin/public/api
```

## 1. Health Check
```bash
GET /api/health
```

Response:
```json
{
    "success": true,
    "message": "PPOB API is running",
    "timestamp": "2025-08-11T12:00:00.000000Z",
    "version": "1.0.0"
}
```

## 2. Register User
```bash
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "full_name": "John Doe Full Name",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "081234567890"
}
```

Response Success (201):
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "full_name": "John Doe Full Name",
            "email": "john@example.com",
            "phone": "081234567890",
            "role": "User Biasa",
            "kyc_status": "unverified",
            "is_active": true,
            "created_at": "2025-08-11T12:00:00.000000Z"
        },
        "token": "1|abcdefghijklmnopqrstuvwxyz...",
        "token_type": "Bearer"
    }
}
```

## 3. Login User
```bash
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123",
    "device_name": "iPhone 15 Pro"
}
```

Response Success (200):
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "full_name": "John Doe Full Name",
            "email": "john@example.com",
            "phone": "081234567890",
            "role": "User Biasa",
            "kyc_status": "unverified",
            "is_active": true,
            "last_login_at": "2025-08-11T12:00:00.000000Z"
        },
        "token": "2|abcdefghijklmnopqrstuvwxyz...",
        "token_type": "Bearer"
    }
}
```

## 4. Get Profile (Protected)
```bash
GET /api/auth/profile
Authorization: Bearer YOUR_TOKEN_HERE
```

Response Success (200):
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "full_name": "John Doe Full Name",
            "email": "john@example.com",
            "phone": "081234567890",
            "role": "User Biasa",
            "kyc_status": "unverified",
            "is_active": true,
            "last_login_at": "2025-08-11T12:00:00.000000Z",
            "created_at": "2025-08-11T12:00:00.000000Z",
            "updated_at": "2025-08-11T12:00:00.000000Z"
        }
    }
}
```

## 5. Logout (Protected)
```bash
POST /api/auth/logout
Authorization: Bearer YOUR_TOKEN_HERE
```

Response Success (200):
```json
{
    "success": true,
    "message": "Logout successful"
}
```

## 6. Logout All Devices (Protected)
```bash
POST /api/auth/logout-all
Authorization: Bearer YOUR_TOKEN_HERE
```

Response Success (200):
```json
{
    "success": true,
    "message": "Logged out from all devices successfully"
}
```

## Error Responses

### Validation Error (422):
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### Unauthorized (401):
```json
{
    "success": false,
    "message": "Invalid email or password"
}
```

### Forbidden (403):
```json
{
    "success": false,
    "message": "Your account has been deactivated. Please contact admin."
}
```

### Unauthenticated (401):
```json
{
    "message": "Unauthenticated."
}
```

## Testing with cURL

### Register:
```bash
curl -X POST http://localhost/ppob-admin/public/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "081234567890"
  }'
```

### Login:
```bash
curl -X POST http://localhost/ppob-admin/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "device_name": "Test Device"
  }'
```

### Get Profile (replace TOKEN with actual token):
```bash
curl -X GET http://localhost/ppob-admin/public/api/auth/profile \
  -H "Authorization: Bearer TOKEN_HERE"
```

### Logout:
```bash
curl -X POST http://localhost/ppob-admin/public/api/auth/logout \
  -H "Authorization: Bearer TOKEN_HERE"
```
