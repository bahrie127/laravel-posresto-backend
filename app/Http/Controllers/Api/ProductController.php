<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    //index api
    public function index()
    {
        //get all products
        $products = Product::all();
        // $products = Product::paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $products
        ], 200);
    }
}
