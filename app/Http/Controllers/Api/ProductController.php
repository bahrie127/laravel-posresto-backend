<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $products = Product::orderBy('id', 'desc')->get();

        $products->load('category');

        return response()->json([
            'success' => true,
            'message' => 'List Data Product',
            'data' => $products,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'category_id' => 'required',
            'is_best_seller' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg',
        ]);

        // App mengirim "is_best_seller"; kolomnya bernama is_favorite.
        $product = Product::create([
            'name' => $request->name,
            'price' => (int) $request->price,
            'stock' => (int) $request->stock,
            'category_id' => $request->category_id,
            'is_favorite' => $request->boolean('is_best_seller'),
            'image' => $this->storeImage($request),
            'printer_type' => $request->printer_type ?: 'kitchen',
        ]);

        if ($product) {
            return response()->json([
                'success' => true,
                'message' => 'Product Created',
                'data' => $product,
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product Failed to Save',
            ], 409);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'category_id' => 'required',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);
        $product = Product::findOrFail($request->id);
        $product->name = $request->name;
        $product->price = $request->price;
        $product->category_id = $request->category_id;
        $product->stock = $request->stock;
        // if printer_type is not null
        if ($request->printer_type) {
            $product->printer_type = $request->printer_type;
        }
        // Dulu menulis kolom is_best_seller yang tidak ada (SQL error saat dikirim).
        if ($request->has('is_best_seller')) {
            $product->is_favorite = $request->boolean('is_best_seller');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($product->image);
            $product->image = $this->storeImage($request);
        }
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product Updated',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Produk yang sudah tercatat di pesanan tidak bisa dihapus (riwayat & laporan butuh
        // datanya); dulu ini berakhir 500 karena foreign key.
        if ($product->orderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk sudah pernah terjual. Nonaktifkan saja agar riwayat tetap utuh.',
            ], 409);
        }

        $this->deleteImage($product->image);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product Deleted',
        ]);
    }

    /**
     * Simpan gambar di disk public; nilai kolom = path relatif yang langsung bisa dipakai app
     * sebagai `{API_HOST}/{image}`.
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return 'storage/'.$request->file('image')->store('products', 'public');
    }

    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
