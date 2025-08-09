# PPOB Admin Panel

Sistem admin panel modern untuk Payment Point Online Banking (PPOB) dibangun dengan Laravel 11 dan Backpack CRUD. Panel admin ini menyediakan interface yang user-friendly untuk mengelola pengguna, role, permissions, dan transaksi PPOB.

## Features

- 🔐 **Multi-Level Role Management** - Administrator, Agen PPOB, User Biasa
- 👥 **User Management** - CRUD lengkap untuk manajemen pengguna
- 🛡️ **Permission System** - Sistem permission yang fleksibel dan dapat dikustomisasi
- 📊 **Dashboard** - Dashboard dengan statistik dan visualisasi data
- 🎨 **Beautiful UI** - Interface modern dengan gradient dan komponen Bootstrap
- 🔒 **Secure Authentication** - Sistem autentikasi yang aman dengan middleware

## Tech Stack

- **Laravel 11** - PHP Framework
- **Backpack CRUD 6.x** - Admin Panel Package
- **PostgreSQL** - Database
- **Bootstrap 5** - CSS Framework
- **Chart.js** - Data Visualization
- **PHP 8.3+** - Programming Language

## Installation

### Prerequisites

- PHP 8.3 atau lebih tinggi
- Composer
- PostgreSQL/MySQL
- Node.js & NPM (opsional untuk asset compilation)

### Step 1: Clone Repository

```bash
git clone https://github.com/username/ppob-admin.git
cd ppob-admin
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Environment Configuration

Copy file environment dan sesuaikan konfigurasi database:

```bash
cp .env.example .env
```

Edit `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ppob_admin
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Run Migrations

```bash
php artisan migrate
```

### Step 6: Seed Database

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AdminUserSeeder
```

### Step 7: Start Development Server

```bash
php artisan serve
```

Panel admin akan tersedia di `http://localhost:8000/admin`

## Default Login Credentials

Setelah installation, gunakan akun berikut untuk login:

### Administrator
- **Email:** admin@ppob.com
- **Password:** admin123
- **Role:** Administrator (Full Access)

### Agent
- **Email:** agent@ppob.com  
- **Password:** agent123
- **Role:** Agen PPOB (Limited Access)

### User
- **Email:** user@ppob.com
- **Password:** user123
- **Role:** User Biasa (Basic Access)

## Usage

### Accessing Admin Panel

1. Buka browser dan pergi ke `http://localhost:8000/admin`
2. Login menggunakan salah satu akun di atas
3. Dashboard akan menampilkan overview sistem

### Managing Users

1. **View Users**: Navigasi ke sidebar menu "Users"
2. **Add User**: Klik tombol "Add User" dan isi form
3. **Edit User**: Klik icon edit pada list users
4. **Delete User**: Klik icon delete untuk menghapus user

### Managing Roles

1. **View Roles**: Navigasi ke sidebar menu "Roles"
2. **Add Role**: Klik "Add Role" dan pilih permissions
3. **Edit Role**: Modify existing roles dan permissions
4. **Assign Permissions**: Gunakan checkbox untuk assign/revoke permissions

### Role Permissions

Sistem menggunakan permission-based access control:

#### Administrator Permissions
- Full access ke semua fitur
- User management (create, read, update, delete)
- Role management
- System configuration

#### Agen PPOB Permissions
- View dan manage transactions
- Limited user access
- Product management

#### User Biasa Permissions
- View own profile
- Basic transaction viewing

## Development

### File Structure

```
app/
├── Http/Controllers/Admin/     # Backpack CRUD Controllers
├── Http/Requests/             # Form Request Validation
├── Http/Middleware/           # Custom Middleware
├── Models/                    # Eloquent Models
database/
├── migrations/               # Database Migrations
├── seeders/                 # Database Seeders
resources/
├── views/custom/            # Custom Blade Views
config/backpack/             # Backpack Configuration
```

### Adding New Role

1. Pergi ke `/admin/role`
2. Klik "Add Role"
3. Isi nama role dan deskripsi
4. Pilih permissions yang sesuai
5. Save - role akan otomatis tersedia di user form

### Customizing Dashboard

Edit file `resources/views/admin/dashboard.blade.php` untuk customize dashboard layout dan content.

### Adding New Permissions

Permissions dapat ditambahkan melalui:
1. Database seeder (`RolePermissionSeeder`)
2. Admin panel di `/admin/permission` (jika controller dibuat)

## Troubleshooting

### Common Issues

**Database Connection Error**
- Pastikan PostgreSQL running
- Check kredensial database di `.env`
- Verify database sudah dibuat

**Permission Denied**
- Clear cache: `php artisan config:clear`
- Check role assignments di database
- Verify middleware configuration

**Assets Not Loading**
- Run `php artisan storage:link`
- Check file permissions
- Clear view cache: `php artisan view:clear`

## Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

Untuk support dan pertanyaan:
- Create issue di GitHub repository
- Contact developer team

---

**Built with ❤️ using Laravel & Backpack CRUD**
