<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends Controller
{
    //save order
    public function saveOrder(Request $request)
    {
        //validate request
        $request->validate([
            'payment_amount' => 'required',
            'sub_total' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'discount_amount' => 'required',
            'service_charge' => 'required',
            'total' => 'required',
            'payment_method' => 'required',
            'total_item' => 'required',
            'id_kasir' => 'required',
            'nama_kasir' => 'required',
            'transaction_time' => 'required',
            // 'order_items' => 'required'
        ]);

        //create order
        $order = Order::create([
            'payment_amount' => $request->payment_amount,
            'sub_total' => $request->sub_total,
            'tax' => $request->tax,
            'discount' => $request->discount,
            'discount_amount' => $request->discount_amount,
            'service_charge' => $request->service_charge,
            'total' => $request->total,
            'payment_method' => $request->payment_method,
            'total_item' => $request->total_item,
            'id_kasir' => $request->id_kasir,
            'nama_kasir' => $request->nama_kasir,
            'transaction_time' => $request->transaction_time
        ]);

        //create order items
        foreach ($request->order_items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id_product'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 200);
    }

    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        if ($start_date && $end_date) {
            $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();
        } else {
            $orders = Order::all();
        }
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    public function summary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $query = Order::query();
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalRevenue = $query->sum('payment_amount');
        $totalDiscount = $query->sum('discount_amount');
        $totalTax = $query->sum('tax');
        $totalServiceCharge = $query->sum('service_charge');
        $totalSubtotal = $query->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount - $totalTax + $totalServiceCharge;
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_subtotal' => $totalSubtotal,
                'total_service_charge' => $totalServiceCharge,
                'total' => $total,
            ]
        ], 200);
    }

    //weekly summary
//     {
//   "report_period": {
//     "start_date": "2025-06-23",
//     "end_date": "2025-06-29"
//   },
//   "summary": {
//     "total_orders": 128,
//     "total_revenue": 15750000,
//     "average_order_value": 123047,
//     "top_selling_product": {
//       "product_id": 12,
//       "name": "Kopi Susu Gula Aren",
//       "quantity_sold": 85,
//       "total_sales": 1275000
//     },
//     "most_active_day": {
//       "date": "2025-06-28",
//       "total_orders": 35,
//       "total_sales": 3950000
//     }
//   },
//   "daily_sales": [
//     {
//       "date": "2025-06-23",
//       "total_orders": 15,
//       "total_sales": 1750000
//     },
//     {
//       "date": "2025-06-24",
//       "total_orders": 20,
//       "total_sales": 2000000
//     },
//     {
//       "date": "2025-06-25",
//       "total_orders": 18,
//       "total_sales": 1900000
//     },
//     {
//       "date": "2025-06-26",
//       "total_orders": 12,
//       "total_sales": 1500000
//     },
//     {
//       "date": "2025-06-27",
//       "total_orders": 28,
//       "total_sales": 3100000
//     },
//     {
//       "date": "2025-06-28",
//       "total_orders": 35,
//       "total_sales": 3950000
//     },
//     {
//       "date": "2025-06-29",
//       "total_orders": 10,
//       "total_sales": 900000
//     }
//   ],
//   "top_5_products": [
//     {
//       "product_id": 12,
//       "name": "Kopi Susu Gula Aren",
//       "quantity_sold": 85,
//       "total_sales": 1275000
//     },
//     {
//       "product_id": 8,
//       "name": "Roti Bakar Coklat",
//       "quantity_sold": 70,
//       "total_sales": 1050000
//     },
//     {
//       "product_id": 3,
//       "name": "Es Teh Manis",
//       "quantity_sold": 65,
//       "total_sales": 650000
//     },
//     {
//       "product_id": 5,
//       "name": "Mie Goreng Spesial",
//       "quantity_sold": 50,
//       "total_sales": 1500000
//     },
//     {
//       "product_id": 9,
//       "name": "Kopi Hitam",
//       "quantity_sold": 45,
//       "total_sales": 675000
//     }
//   ]
// }

    public function weeklySummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Validate the date range
        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        // Fetch orders within the date range
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();

        // Calculate total orders and revenue
        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('payment_amount');

        // Calculate average order value
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Group orders by day
        $dailySales = [];
        foreach ($orders as $order) {
            $date = $order->created_at->format('Y-m-d');
            if (!isset($dailySales[$date])) {
                $dailySales[$date] = [
                    'date' => $date,
                    'total_orders' => 0,
                    'total_sales' => 0,
                ];
            }
            $dailySales[$date]['total_orders']++;
            $dailySales[$date]['total_sales'] += $order->payment_amount;
        }
        $dailySales = array_values($dailySales);

        // Find top selling product
        $topSellingProduct = null;
        if ($totalOrders > 0) {
            $topSellingProduct = OrderItem::selectRaw('product_id, SUM(quantity) as quantity_sold, SUM(price * quantity) as total_sales')
                ->whereIn('order_id', $orders->pluck('id'))
                ->groupBy('product_id')
                ->orderByDesc('quantity_sold')
                ->first();
            if ($topSellingProduct) {
                // Get product details
                $topSellingProductDetails = \App\Models\Product::find($topSellingProduct->product_id);
                if ($topSellingProductDetails) {
                    $topSellingProduct->name = $topSellingProductDetails->name;
                }
            }
        }

        // Find most active day
        $mostActiveDay = collect($dailySales)->sortByDesc('total_orders')->first();

        return response()->json([
            'report_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
                'top_selling_product' => $topSellingProduct ? [
                    'product_id' => $topSellingProduct->product_id,
                    'name' => $topSellingProduct->name,
                    'quantity_sold' => $topSellingProduct->quantity_sold,
                    'total_sales' => $topSellingProduct->total_sales,
                ] : null,
                'most_active_day' => $mostActiveDay ? [
                    'date' => $mostActiveDay['date'],
                    'total_orders' => $mostActiveDay['total_orders'],
                    'total_sales' => $mostActiveDay['total_sales'],
                ] : null,
            ],
            'daily_sales' => $dailySales,
            'top_5_products' => OrderItem::selectRaw('product_id, SUM(quantity) as quantity_sold, SUM(price * quantity) as total_sales')
                ->whereIn('order_id', $orders->pluck('id'))
                ->groupBy('product_id')
                ->orderByDesc('quantity_sold')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    $product = \App\Models\Product::find($item->product_id);
                    return [
                        'product_id' => $item->product_id,
                        'name' => $product ? $product->name : 'Unknown Product',
                        'quantity_sold' => $item->quantity_sold,
                        'total_sales' => $item->total_sales,
                    ];
                }),
        ], 200);
    }

    //dummy weekly summary
    public function dummyWeeklySummary()
    {
        return response()->json([
            'report_period' => [
                'start_date' => '2025-06-23',
                'end_date' => '2025-06-29',
            ],
            'summary' => [
                'total_orders' => 128,
                'total_revenue' => 15750000,
                'average_order_value' => 123047,
                'top_selling_product' => [
                    'product_id' => 12,
                    'name' => 'Kopi Susu Gula Aren',
                    'quantity_sold' => 85,
                    'total_sales' => 1275000,
                ],
                'most_active_day' => [
                    'date' => '2025-06-28',
                    'total_orders' => 35,
                    'total_sales' => 3950000,
                ],
            ],
            'daily_sales' => [
                [
                    'date' => '2025-06-23',
                    'total_orders' => 15,
                    'total_sales' => 1750000,
                ],
                [
                    'date' => '2025-06-24',
                    'total_orders' => 20,
                    'total_sales' => 2000000,
                ],
                [
                    'date' => '2025-06-25',
                    'total_orders' => 18,
                    'total_sales' => 1900000,
                ],
                [
                    'date' => '2025-06-26',
                    'total_orders' => 12,
                    'total_sales' => 1500000,
                ],
                [
                    'date' => '2025-06-27',
                    'total_orders' => 28,
                    'total_sales' => 3100000,
                ],
                [
                    'date' => '2025-06-28',
                    'total_orders' => 35,
                    'total_sales' => 3950000,
                ],
                [
                    'date' => '2025-06-29',
                    'total_orders' => 10,
                    'total_sales' => 900000,
                ],
            ],
            'top_5_products' => [
                [
                    'product_id' => 12,
                    'name' => 'Kopi Susu Gula Aren',
                    'quantity_sold' => 85,
                    'total_sales' => 1275000,
                ],
                [
                    'product_id' => 8,
                    'name' => 'Roti Bakar Coklat',
                    'quantity_sold' => 70,
                    'total_sales' => 1050000,
                ],
                [
                    'product_id' => 3,
                    'name' => 'Es Teh Manis',
                    'quantity_sold' => 65,
                    'total_sales' => 650000,
                ],
                [
                    'product_id' => 5,
                    'name' => 'Mie Goreng Spesial',
                    'quantity_sold' => 50,
                    'total_sales' => 1500000,
                ],
                [
                    'product_id' => 9,
                    'name' => 'Kopi Hitam',
                    'quantity_sold' => 45,
                    'total_sales' => 675000,
                ],
            ],
        ], 200);
    }


}
