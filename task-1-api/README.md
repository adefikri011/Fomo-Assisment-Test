```md
# 🛒 Task 1: Online Store API — Flash Sale

API Online Store dengan fitur **Flash Sale** yang dibangun menggunakan **Laravel 13 (PHP)**. API ini mampu menangani **race condition** saat flash sale berlangsung, di mana banyak customer mencoba membeli product yang sama secara bersamaan, serta menjamin stock inventory **tidak pernah bernilai negatif**.

---

## 🛠 Tech Stack

| Komponen | Teknologi |
|---|---|
| Bahasa | PHP 8+ |
| Framework | Laravel 13 |
| Database | MySQL |
| Testing | PHPUnit (Laravel Feature Test) |

---

## ⚙️ Cara Install & Menjalankan Project

### 1. Install Dependencies

```bash
composer install
```

Perintah ini menginstall seluruh package Laravel yang dibutuhkan oleh project.

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Salin file konfigurasi environment, lalu generate application key untuk enkripsi Laravel. Setelah itu buka file `.env` dan sesuaikan koneksi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_store
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan database `online_store` sudah dibuat terlebih dahulu di MySQL.

### 3. Migrasi Database & Seeder

```bash
php artisan migrate:fresh --seed
```

Perintah ini membuat seluruh tabel (`products`, `orders`, `order_items`) sekaligus mengisi data product flash sale sebagai data awal.

### 4. Jalankan Server

```bash
php artisan serve
```

API akan berjalan di `http://127.0.0.1:8000`

---

## 🗄️ Desain Database

```
products                 order_items                  orders
─────────                ───────────                  ────────
id            ←─────     product_id                   id    ─────→  order_id
name                     order_id                     customer_name
description              quantity                     customer_email
price                    price                        total_price
flash_sale_price         subtotal                     status
is_flash_sale            is_flash_sale_price
stock (unsigned)
```

**Relasi:**
- Satu `Order` memiliki banyak `OrderItem` (one-to-many) — order **wajib minimal 1 item**
- Satu `OrderItem` merujuk ke satu `Product`

**Poin penting desain:**
- Kolom `stock` menggunakan `unsignedInteger` sehingga **tidak bisa bernilai negatif di level database** (proteksi lapisan terakhir).
- Kolom `price` di `order_items` menyimpan harga **saat transaksi terjadi**, sehingga riwayat order tetap akurat walaupun harga product berubah di kemudian hari.
- Kolom `flash_sale_price` dan `is_flash_sale` di `products` menentukan harga mana yang dipakai saat order dibuat.

---

## 📡 Dokumentasi Endpoint API

Seluruh endpoint menggunakan **JSON** sebagai format request dan response, dengan **HTTP status code yang sesuai**.

### 1️⃣ Health Check

```
GET /api/health
```

Response `200 OK`:
```json
{
  "status": "OK",
  "message": "API is running"
}
```

---

### 2️⃣ List Semua Product

```
GET /api/products
```

Response `200 OK`:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Flash Sale Laptop",
      "description": "High performance laptop",
      "price": 15000000,
      "flash_sale_price": 10000000,
      "is_flash_sale": true,
      "stock": 10,
      "created_at": "2026-06-10T11:33:05.000000Z"
    }
  ]
}
```

Jika `is_flash_sale = true`, maka harga yang digunakan saat order adalah `flash_sale_price`, bukan harga normal.

---

### 3️⃣ Detail Product

```
GET /api/products/{id}
```

Response `200 OK` jika product ditemukan.

Response `404 Not Found` jika tidak ditemukan:
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 4️⃣ Create Order (Endpoint Utama)

```
POST /api/orders
Content-Type: application/json
```

Request Body:
```json
{
  "customer_name": "Fikri",
  "customer_email": "fikri@email.com",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

Field `items` berupa array sehingga satu order dapat berisi lebih dari satu product. Validasi `items|required|array|min:1` memastikan **order selalu memiliki minimal 1 order item** sesuai business requirement.

Response `201 Created` — order berhasil:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "customer_name": "Fikri",
    "customer_email": "fikri@email.com",
    "status": "pending",
    "total_price": 20000000,
    "created_at": "2026-06-10T11:14:45.000000Z",
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "price": 10000000,
        "subtotal": 20000000,
        "is_flash_sale_price": true
      }
    ]
  }
}
```

Response `400 Bad Request` — stock tidak mencukupi:
```json
{
  "success": false,
  "message": "Insufficient stock"
}
```

Response `422 Unprocessable Content` — validasi gagal (contoh: order tanpa item):
```json
{
  "message": "The items field is required.",
  "errors": {
    "items": ["The items field is required."]
  }
}
```

---

## 🔥 Penanganan Race Condition (Inti Solusi)

### Permasalahan

Saat flash sale, banyak request order masuk **bersamaan** untuk product yang sama. Tanpa penanganan khusus, akan terjadi race condition:

```
Stock = 5

Request A baca stock = 5 → lolos pengecekan
Request B baca stock = 5 → lolos pengecekan (A belum selesai mengurangi!)
Request C baca stock = 5 → lolos pengecekan
... semua lolos → stock berakhir NEGATIF ❌
```

Semua request membaca nilai stock yang sama **sebelum** ada yang sempat menguranginya.

### Solusi: Database Transaction + Pessimistic Locking

Implementasi berada di `app/Services/OrderService.php`:

```php
return DB::transaction(function () use ($data) {

    // lockForUpdate() MENGUNCI row product (row-level lock).
    // Request lain yang ingin membaca row yang sama harus MENUNGGU
    // hingga transaksi ini selesai (commit / rollback).
    $product = Product::where('id', $item['product_id'])
        ->lockForUpdate()
        ->first();

    // Pengecekan stock dilakukan SETELAH lock didapatkan,
    // sehingga nilai stock yang dibaca dijamin selalu terbaru.
    if ($product->stock < $item['quantity']) {
        throw new \Exception('Insufficient stock');
    }

    $product->decrement('stock', $item['quantity']);
});
```

### Alur Setelah Solusi Diterapkan

```
Stock = 5, quantity per order = 2

Request A → lock product → stock 5 ≥ 2 → kurangi jadi 3 → commit (lock dilepas)
Request B → menunggu...  → lock product → stock 3 ≥ 2 → kurangi jadi 1 → commit
Request C → menunggu...  → lock product → stock 1 < 2 → DITOLAK (400) ✅
```

### Tiga Lapisan Proteksi

| Lapisan | Implementasi | Fungsi |
|---|---|---|
| 1. Transaction | `DB::transaction()` | Operasi atomic — jika gagal di tengah, semua perubahan di-rollback |
| 2. Row Locking | `lockForUpdate()` | Mencegah dua request membaca stock secara bersamaan |
| 3. Database Constraint | `unsignedInteger('stock')` | Kolom stock tidak dapat bernilai negatif di level MySQL |

---

## 🧪 Functional Test (Command Line)

Test dijalankan melalui command line sesuai technical requirement:

```bash
php artisan test
```

### Skenario Test

File: `tests/Feature/FlashSaleRaceConditionTest.php`

1. Membuat product flash sale dengan **stock hanya 5**
2. Mengirim **10 request order** ke `POST /api/orders` untuk product yang sama (simulasi burst order saat flash sale)
3. Verifikasi hasil:
   - Stock akhir **tidak pernah negatif** → `assertGreaterThanOrEqual(0, $product->stock)`
   - Jumlah order sukses **tidak melebihi stock awal** → `assertLessThanOrEqual(5, $successfulOrders)`

### Hasil Test

```
PASS  Tests\Feature\FlashSaleRaceConditionTest
✓ flash sale race condition does not create negative stock

Tests: 3 passed (6 assertions)
```

> **Catatan:** Test menggunakan trait `RefreshDatabase` sehingga database di-reset setiap test dijalankan. Untuk mengembalikan data development setelah test, jalankan kembali `php artisan migrate:fresh --seed`.

---

## 🏗 Arsitektur Project

Project menggunakan **Service Layer Pattern** untuk memisahkan business logic dari controller (separation of concerns):

```
Request → Route → Controller → Service → Model → Database
                       ↓
                   Resource (format JSON response)
```

| File | Tanggung Jawab |
|---|---|
| `routes/api.php` | Definisi seluruh endpoint API |
| `app/Http/Controllers/Api/ProductController.php` | Handle request product (list & detail) |
| `app/Http/Controllers/Api/OrderController.php` | Validasi input & HTTP response order |
| `app/Services/OrderService.php` | Business logic order: transaction, locking, kalkulasi harga |
| `app/Http/Resources/ProductResource.php` | Format & konsistensi JSON response product |
| `app/Http/Resources/OrderResource.php` | Format & konsistensi JSON response order |
| `app/Models/Product.php` | Model product + relasi + helper stock |
| `app/Models/Order.php` | Model order + relasi + konstanta status |
| `app/Models/OrderItem.php` | Model order item + relasi |
| `database/migrations/` | Struktur tabel database |
| `database/seeders/ProductSeeder.php` | Data awal product flash sale |
| `database/factories/ProductFactory.php` | Factory product untuk kebutuhan testing |
| `tests/Feature/FlashSaleRaceConditionTest.php` | Functional test race condition |

---

## ✅ Checklist Pemenuhan Requirement

### Business Requirements

| No | Requirement | Status | Implementasi |
|---|---|---|---|
| 1 | Order terdiri dari minimal satu Order Item | ✅ | Validasi `items: required\|array\|min:1` |
| 2 | Flash sale dengan harga diskon | ✅ | `flash_sale_price` digunakan saat `is_flash_sale = true` |
| 3 | Mencegah nilai Inventory negatif | ✅ | Transaction + row locking + unsigned column |

### Technical Requirements

| No | Requirement | Status | Implementasi |
|---|---|---|---|
| 1a | API menggunakan format JSON | ✅ | Seluruh request & response berformat JSON |
| 1b | Proper response code & error message | ✅ | 200, 201, 400, 404, 422 dengan pesan yang jelas |
| 2 | Menangani race condition saat flash sale | ✅ | `DB::transaction()` + `lockForUpdate()` |
| 3 | Functional test yang dijalankan via command line | ✅ | `php artisan test` — race condition test |
```