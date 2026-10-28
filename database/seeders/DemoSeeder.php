<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discount;
use App\Models\DraftOrder;
use App\Models\HotelGuest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TableManagement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo resto hotel yang relatif terhadap hari ini: menu, tamu menginap, denah meja,
 * pesanan 30 hari terakhir (termasuk tagih ke kamar dan pembatalan), serta bill meja yang
 * masih terbuka. Rumus total mengikuti app kasir: (subtotal - diskon) + pajak 10% + layanan 5%,
 * keduanya dihitung dari subtotal setelah diskon.
 */
class DemoSeeder extends Seeder
{
    private const TAX_PERCENT = 10;

    private const SERVICE_PERCENT = 5;

    public function run(): void
    {
        mt_srand(20261028);

        $cashiers = [
            User::create(['name' => 'Rina Kasir', 'email' => 'kasir@posresto.test', 'password' => Hash::make('password'), 'role' => 'staff']),
            User::create(['name' => 'Dimas Kasir', 'email' => 'kasir2@posresto.test', 'password' => Hash::make('password'), 'role' => 'staff']),
        ];
        User::create(['name' => 'Manajer F&B', 'email' => 'admin@posresto.test', 'password' => Hash::make('password'), 'role' => 'admin']);

        // Urutan id dipakai tab app: 1 Makanan, 2 Minuman, 3 Snack.
        $categories = [];
        foreach (['Makanan', 'Minuman', 'Snack'] as $name) {
            $categories[$name] = Category::create(['name' => $name, 'description' => $name])->id;
        }

        $menu = [
            ['Makanan', 'Nasi Goreng Seafood', 58000, 'kitchen', true],
            ['Makanan', 'Mie Goreng Jawa', 48000, 'kitchen', false],
            ['Makanan', 'Ayam Bakar Madu', 62000, 'kitchen', true],
            ['Makanan', 'Ikan Bakar Rica', 85000, 'kitchen', false],
            ['Makanan', 'Sate Ayam Madura', 52000, 'kitchen', true],
            ['Makanan', 'Gado-Gado', 42000, 'kitchen', false],
            ['Makanan', 'Sop Buntut', 95000, 'kitchen', false],
            ['Makanan', 'Club Sandwich', 55000, 'kitchen', false],
            ['Makanan', 'Spaghetti Bolognese', 60000, 'kitchen', false],
            ['Minuman', 'Es Teh Manis', 18000, 'bar', true],
            ['Minuman', 'Jus Jeruk', 28000, 'bar', false],
            ['Minuman', 'Es Kelapa Muda', 32000, 'bar', true],
            ['Minuman', 'Kopi Tubruk', 22000, 'bar', false],
            ['Minuman', 'Cappuccino', 35000, 'bar', false],
            ['Minuman', 'Air Mineral', 12000, 'bar', false],
            ['Snack', 'Pisang Goreng Keju', 30000, 'kitchen', false],
            ['Snack', 'Kentang Goreng', 32000, 'kitchen', true],
            ['Snack', 'Roti Bakar Cokelat', 28000, 'kitchen', false],
        ];
        $products = [];
        foreach ($menu as [$category, $name, $price, $printer, $favorite]) {
            $products[] = Product::create([
                'category_id' => $categories[$category],
                'name' => $name,
                'description' => $name,
                'price' => $price,
                'stock' => 100,
                'status' => 1,
                'is_favorite' => $favorite,
                'printer_type' => $printer,
            ]);
        }

        $today = CarbonImmutable::today();

        Discount::create(['name' => 'Tamu Hotel', 'description' => 'Diskon tamu menginap', 'type' => 'percentage', 'value' => 10, 'status' => 'active', 'expired_date' => $today->addMonths(6)->toDateString()]);
        Discount::create(['name' => 'Happy Hour', 'description' => 'Pukul 15.00-17.00', 'type' => 'percentage', 'value' => 15, 'status' => 'active', 'expired_date' => $today->addMonths(3)->toDateString()]);
        Discount::create(['name' => 'Member', 'description' => 'Pemegang kartu member', 'type' => 'percentage', 'value' => 5, 'status' => 'active', 'expired_date' => $today->addYear()->toDateString()]);

        $guests = [];
        foreach ([
            ['101', 'Andi', 'Saputra', -2, 2], ['105', 'Maria', 'Gunawan', -1, 3], ['108', 'Kevin', 'Tan', 0, 2],
            ['203', 'Siti', 'Rahmawati', -3, 1], ['210', 'Hendra', 'Wijaya', -1, 4], ['305', 'Laura', 'Smith', 0, 5],
        ] as [$room, $first, $last, $in, $out]) {
            $guests[] = HotelGuest::create([
                'billing_number' => 'BILL-'.$room.'-'.$today->addDays($in)->format('md'),
                'room_number' => $room,
                'first_name' => $first,
                'last_name' => $last,
                'check_in' => $today->addDays($in),
                'check_out' => $today->addDays($out),
            ]);
        }

        // Denah 2 baris x 5 meja. Koordinat dp dari tepi layar; sidebar app menutup ±120 dp
        // pertama, jadi kolom pertama mulai di 170.
        $tables = [];
        for ($i = 0; $i < 10; $i++) {
            $tables[] = TableManagement::create([
                'table_name' => 'Meja '.($i + 1),
                'x_position' => 170 + ($i % 5) * 200,
                'y_position' => 90 + intdiv($i, 5) * 190,
                'status' => 'available',
            ]);
        }

        $this->seedOrders($products, $guests, $cashiers, $today);
        $this->seedOpenBills($products, $tables, $today);
    }

    /**
     * @param  list<Product>  $products
     * @param  list<HotelGuest>  $guests
     * @param  list<User>  $cashiers
     */
    private function seedOrders(array $products, array $guests, array $cashiers, CarbonImmutable $today): void
    {
        $customers = ['Budi', 'Ayu', 'Rudi', 'Fajar', 'Nadia', 'Tommy', 'Lestari', 'Yoga', 'Mr. James', 'Bu Wati', 'Pak Herman', 'Dewi'];

        for ($day = 29; $day >= 0; $day--) {
            $date = $today->subDays($day);
            $count = ($date->isWeekend() ? 14 : 9) + mt_rand(0, 5);

            for ($n = 0; $n < $count; $n++) {
                $time = $date->setTime(mt_rand(7, 21), mt_rand(0, 59));
                if ($time->isFuture()) {
                    continue;
                }

                $lines = [];
                foreach ((array) array_rand($products, mt_rand(2, 4)) as $index) {
                    $lines[] = [$products[$index], mt_rand(1, 3)];
                }
                $subtotal = array_sum(array_map(fn ($line) => (int) $line[0]->price * $line[1], $lines));
                $discountPercent = mt_rand(1, 10) === 1 ? 10 : 0;
                $discountAmount = intdiv($subtotal * $discountPercent, 100);
                $afterDiscount = $subtotal - $discountAmount;
                $tax = intdiv($afterDiscount * self::TAX_PERCENT, 100);
                $service = intdiv($afterDiscount * self::SERVICE_PERCENT, 100);
                $total = $afterDiscount + $tax + $service;

                $roll = mt_rand(1, 100);
                $method = $roll <= 50 ? 'Cash' : ($roll <= 82 ? 'Qris' : 'Bill Hotel');
                $guest = $method === 'Bill Hotel' ? $guests[array_rand($guests)] : null;
                $cashier = $cashiers[$n % 2];
                $canceled = mt_rand(1, 40) === 1;

                $order = Order::create([
                    'payment_amount' => $method === 'Cash' ? (int) ceil($total / 50000) * 50000 : $total,
                    'sub_total' => $subtotal,
                    'tax' => $tax,
                    'discount' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'service_charge' => $service,
                    'total' => $total,
                    'payment_method' => $method,
                    'total_item' => array_sum(array_column($lines, 1)),
                    'id_kasir' => $cashier->id,
                    'nama_kasir' => $cashier->name,
                    'customer_name' => $guest ? $guest->first_name.' '.$guest->last_name : $customers[array_rand($customers)],
                    'table_number' => $guest ? 0 : mt_rand(1, 10),
                    'transaction_time' => $time->toIso8601String(),
                    'room_id' => $guest?->id ?? 0,
                    'note' => null,
                    'status' => $canceled ? 'cancelled' : 'success',
                    'is_canceled' => $canceled,
                    'canceled_by' => $canceled ? $cashier->id : null,
                    'canceled_at' => $canceled ? $time->addMinutes(10) : null,
                ]);
                $order->forceFill(['created_at' => $time, 'updated_at' => $time])->save();

                foreach ($lines as [$product, $quantity]) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => (int) $product->price,
                    ])->forceFill(['created_at' => $time, 'updated_at' => $time])->save();
                }
            }
        }
    }

    /**
     * Dua meja terisi dengan bill terbuka (draft order).
     *
     * @param  list<Product>  $products
     * @param  list<TableManagement>  $tables
     */
    private function seedOpenBills(array $products, array $tables, CarbonImmutable $today): void
    {
        $start = now()->subMinutes(35);
        foreach ([[2, 'Keluarga Pratama', [[0, 2], [9, 3], [16, 1]]], [6, 'Mr. James', [[3, 1], [13, 1]]]] as [$tableIndex, $name, $lines]) {
            $subtotal = array_sum(array_map(fn ($line) => (int) $products[$line[0]]->price * $line[1], $lines));
            $tax = intdiv($subtotal * self::TAX_PERCENT, 100);
            $service = intdiv($subtotal * self::SERVICE_PERCENT, 100);

            // Di draft order kolom service_charge berisi persen (lihat DraftOrderController).
            $draft = DraftOrder::create([
                'total_item' => array_sum(array_column($lines, 1)),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tax_percent' => self::TAX_PERCENT,
                'discount' => 0,
                'discount_amount' => 0,
                'service_charge' => self::SERVICE_PERCENT,
                'total' => $subtotal + $tax + $service,
                'transaction_time' => $start,
                'table_number' => $tables[$tableIndex]->id,
                'draft_name' => $name,
                'room_id' => null,
            ]);
            foreach ($lines as [$productIndex, $quantity]) {
                $draft->items()->create([
                    'product_id' => $products[$productIndex]->id,
                    'quantity' => $quantity,
                    'price' => (int) $products[$productIndex]->price,
                ]);
            }

            $tables[$tableIndex]->update([
                'status' => 'occupied',
                'order_id' => $draft->id,
                'payment_amount' => $draft->total,
                'start_time' => $start,
            ]);
            $start = $start->addMinutes(20);
        }
    }
}
