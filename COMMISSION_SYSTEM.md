# Sistem Komisi Dinamis PPOB Admin

## Fitur
Sistem komisi dinamis yang memungkinkan pengaturan komisi berbeda untuk setiap produk berdasarkan tipe pengguna:
- **Seller**: Komisi untuk penjual reguler
- **Reseller**: Komisi untuk reseller
- **B2B**: Komisi untuk klien bisnis

## Jenis Komisi
1. **Persentase (percent)**: Komisi berdasarkan persentase dari harga produk
2. **Nominal Tetap (fixed)**: Komisi dengan nominal tetap

## Penggunaan

### 1. Mengelola Komisi di Admin Dashboard
- Akses menu **Data Management > Product Commissions**
- Klik **Add Product Commission** untuk menambah komisi baru
- Pilih produk dan atur komisi untuk setiap tipe pengguna
- Atur komisi minimum dan maksimum (opsional)

### 2. Menghitung Komisi Secara Programatik

```php
// Mendapatkan atau membuat komisi untuk produk
$commission = $product->getOrCreateCommission();

// Menghitung komisi untuk seller dengan transaksi Rp 100,000
$sellerCommission = $product->calculateCommissionFor('seller', 100000);

// Menghitung komisi untuk reseller
$resellerCommission = $product->calculateCommissionFor('reseller', 100000);

// Menghitung komisi untuk B2B
$b2bCommission = $product->calculateCommissionFor('b2b', 100000);

// Mendapatkan informasi komisi
$commissionInfo = $commission->getCommissionInfo('seller');
echo $commissionInfo['display']; // "5% - Persentase"
```

### 3. Menggunakan Command Line
```bash
# Menghitung komisi untuk produk ID 1, tipe seller, dengan amount 100000
php artisan commission:calculate 1 seller 100000

# Contoh output:
# Product: Pulsa Telkomsel 10K
# User Type: seller
# Amount: Rp 100,000
# Commission: Rp 5,000
# Commission Rate: 5.0 (percent)
```

### 4. Contoh Penggunaan dalam Controller

```php
use App\Models\Product;

class TransactionController extends Controller
{
    public function processTransaction($productId, $userType, $amount)
    {
        $product = Product::findOrFail($productId);
        
        // Hitung komisi
        $commission = $product->calculateCommissionFor($userType, $amount);
        
        // Proses transaksi...
        $transaction = Transaction::create([
            'product_id' => $productId,
            'user_type' => $userType,
            'amount' => $amount,
            'commission' => $commission,
            // ... field lainnya
        ]);
        
        return $transaction;
    }
}
```

### 5. Validasi dan Aturan Bisnis
- Komisi tidak boleh negatif
- Komisi maksimum harus lebih besar dari komisi minimum
- Setiap produk hanya bisa memiliki satu pengaturan komisi
- Komisi dapat diaktifkan/dinonaktifkan dengan field `is_active`

### 6. Database Structure
Tabel `product_commissions`:
- `product_id`: ID produk (foreign key)
- `seller_commission`: Nilai komisi seller
- `seller_commission_type`: Tipe komisi seller (percent/fixed)
- `reseller_commission`: Nilai komisi reseller  
- `reseller_commission_type`: Tipe komisi reseller (percent/fixed)
- `b2b_commission`: Nilai komisi B2B
- `b2b_commission_type`: Tipe komisi B2B (percent/fixed)
- `min_commission`: Komisi minimum (opsional)
- `max_commission`: Komisi maksimum (opsional)
- `is_active`: Status aktif

## Testing
Untuk menguji sistem, jalankan seeder:
```bash
php artisan db:seed --class=ProductCommissionSeeder
```

Seeder akan membuat data sampel dengan berbagai konfigurasi komisi untuk testing.
