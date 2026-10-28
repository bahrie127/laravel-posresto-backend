<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HotelGuest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TableManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** API yang dipakai app kasir resto/hotel. */
class PosApiTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-28 15:00:00');

        $this->cashier = User::factory()->create(['role' => 'staff']);
        $category = Category::create(['name' => 'Makanan']);
        $this->product = Product::create(['category_id' => $category->id, 'name' => 'Nasi Goreng', 'price' => 50000, 'stock' => 10]);
        Sanctum::actingAs($this->cashier);
    }

    public function test_simpan_order_tanpa_kamar_dan_wajib_ada_item(): void
    {
        $payload = $this->orderPayload();
        unset($payload['room_id']);

        $this->postJson('/api/save-order', $payload)->assertOk();
        $this->assertSame(0, Order::first()->room_id);
        $this->assertSame(2, OrderItem::first()->quantity);

        // Pembeli walk-in: nama pelanggan kosong tetap tersimpan.
        $this->postJson('/api/save-order', [...$payload, 'customer_name' => '', 'table_number' => 0, 'transaction_time' => now()->addMinute()->toIso8601String()])->assertOk();
        $this->assertSame(2, Order::count());

        $this->postJson('/api/save-order', [...$payload, 'order_items' => []])->assertUnprocessable();
        $this->postJson('/api/save-order', [...$payload, 'order_items' => [['id_product' => 999, 'quantity' => 1, 'price' => 1]]])
            ->assertUnprocessable();
        $this->assertSame(2, Order::count());
    }

    public function test_kiriman_ulang_order_yang_sama_tidak_menggandakan(): void
    {
        $payload = [...$this->orderPayload(), 'transaction_time' => '2026-10-28T15:38:12.123456'];

        $first = $this->postJson('/api/save-order', $payload)->assertOk()->json('data.id');
        $second = $this->postJson('/api/save-order', $payload)->assertOk()->json('data.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
    }

    public function test_ringkasan_hari_ini_tanpa_order_batal_dan_pajak_menambah_total(): void
    {
        $this->makeOrder(subtotal: 100000, discount: 10000, tax: 10000, paid: 150000, at: '2026-10-28 09:00:00');
        $this->makeOrder(subtotal: 50000, discount: 0, tax: 5000, paid: 55000, at: '2026-10-28 14:30:00');
        $this->makeOrder(subtotal: 80000, discount: 0, tax: 8000, paid: 88000, at: '2026-10-28 12:00:00', canceled: true);
        $this->makeOrder(subtotal: 70000, discount: 0, tax: 7000, paid: 77000, at: '2026-10-27 20:00:00');

        $this->getJson('/api/summary?start_date=2026-10-28&end_date=2026-10-28')
            ->assertOk()
            ->assertJsonPath('data.total_subtotal', 150000)
            ->assertJsonPath('data.total_discount', 10000)
            ->assertJsonPath('data.total_tax', 15000)
            // Pendapatan = yang ditagih (100rb-10rb+10rb + 50rb+5rb), bukan uang diterima (205rb).
            ->assertJsonPath('data.total_revenue', 155000)
            ->assertJsonPath('data.total', 155000);

        $this->getJson('/api/orders?start_date=2026-10-28&end_date=2026-10-28')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_riwayat_semua_order_terpanggil_beserta_item(): void
    {
        $this->makeOrder(subtotal: 100000, discount: 0, tax: 10000, paid: 110000, at: '2026-10-28 09:00:00');

        $this->getJson('/api/orders/all')->assertOk()->assertJsonCount(1, 'data.0.order_items');
    }

    public function test_produk_terjual_tidak_menghitung_order_batal(): void
    {
        $this->makeOrder(subtotal: 100000, discount: 0, tax: 10000, paid: 110000, at: '2026-10-28 09:00:00');
        $this->makeOrder(subtotal: 100000, discount: 0, tax: 10000, paid: 110000, at: '2026-10-28 10:00:00', canceled: true);

        $this->getJson('/api/order-sales?start_date=2026-10-28&end_date=2026-10-28')
            ->assertOk()
            ->assertJsonPath('data.0.total_quantity', '2');
    }

    public function test_batal_order_memakai_pin_dari_config(): void
    {
        config(['posresto.cancel_pin' => '4321']);
        $order = $this->makeOrder(subtotal: 100000, discount: 0, tax: 10000, paid: 110000, at: '2026-10-28 09:00:00');

        $this->postJson("/api/orders/{$order->id}/cancel", ['pin' => '8888'])->assertUnauthorized();
        $this->postJson("/api/orders/{$order->id}/cancel", ['pin' => '4321'])->assertOk();
        $this->assertTrue((bool) $order->fresh()->is_canceled);
    }

    public function test_tamu_menginap_hanya_yang_sedang_menginap_dan_wajib_login(): void
    {
        HotelGuest::create(['billing_number' => 'B-1', 'room_number' => '101', 'first_name' => 'Andi', 'last_name' => 'Saputra', 'check_in' => '2026-10-27', 'check_out' => '2026-10-30']);
        HotelGuest::create(['billing_number' => 'B-2', 'room_number' => '102', 'first_name' => 'Lama', 'last_name' => '', 'check_in' => '2026-10-20', 'check_out' => '2026-10-25']);

        $this->getJson('/api/reservation-guests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.billing_number', 'B-1')
            ->assertJsonPath('data.0.last_name', 'Saputra');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', '')->getJson('/api/reservation-guests')->assertUnauthorized();
    }

    public function test_produk_yang_pernah_terjual_tidak_bisa_dihapus(): void
    {
        $this->makeOrder(subtotal: 100000, discount: 0, tax: 10000, paid: 110000, at: '2026-10-28 09:00:00');

        $this->deleteJson("/api/products/{$this->product->id}")->assertStatus(409);
        $this->assertModelExists($this->product);
    }

    public function test_ubah_produk_dengan_best_seller_tidak_error(): void
    {
        $this->postJson('/api/products/edit', [
            'id' => $this->product->id, 'name' => 'Nasi Goreng Spesial', 'price' => 55000, 'stock' => 5,
            'category_id' => $this->product->category_id, 'is_best_seller' => 1,
        ])->assertOk();

        $this->assertTrue((bool) $this->product->fresh()->is_favorite);
    }

    public function test_status_meja_tidak_valid_dibalas_422_bukan_500(): void
    {
        $table = TableManagement::create(['table_name' => 'Meja 1', 'x_position' => 0, 'y_position' => 0]);

        $this->putJson("/api/tables/{$table->id}", ['status' => 'cleaning'])->assertUnprocessable();
        $this->patchJson("/api/tables/{$table->id}/status", ['status' => 'cleaning'])->assertUnprocessable();
        $this->patchJson("/api/tables/{$table->id}/status", ['status' => 'occupied', 'order_id' => 3])->assertOk();
        $this->assertSame('occupied', $table->fresh()->status);
        // Jam mulai wajib ada untuk meja terisi (dialog meja di app membacanya).
        $this->assertNotNull($table->fresh()->start_time);

        $this->patchJson("/api/tables/{$table->id}/status", ['status' => 'available'])->assertOk();
        $this->assertNull($table->fresh()->start_time);
    }

    /** @return array<string, mixed> */
    private function orderPayload(): array
    {
        return [
            'payment_amount' => 110000, 'sub_total' => 100000, 'tax' => 10000, 'discount' => 0, 'discount_amount' => 0,
            'service_charge' => 0, 'total' => 110000, 'payment_method' => 'Cash', 'total_item' => 2,
            'id_kasir' => $this->cashier->id, 'nama_kasir' => $this->cashier->name, 'customer_name' => 'Budi',
            'table_number' => 1, 'transaction_time' => now()->toIso8601String(), 'room_id' => 0,
            'order_items' => [['id_product' => $this->product->id, 'quantity' => 2, 'price' => 50000]],
        ];
    }

    private function makeOrder(int $subtotal, int $discount, int $tax, int $paid, string $at, bool $canceled = false): Order
    {
        $order = Order::create([
            ...$this->orderPayload(),
            'sub_total' => $subtotal, 'discount_amount' => $discount, 'tax' => $tax,
            'total' => $subtotal - $discount + $tax, 'payment_amount' => $paid,
            'is_canceled' => $canceled, 'status' => $canceled ? 'cancelled' : 'success',
        ]);
        $order->forceFill(['created_at' => $at])->save();
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'quantity' => 2, 'price' => 50000])
            ->forceFill(['created_at' => $at])->save();

        return $order;
    }
}
