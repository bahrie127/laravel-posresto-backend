<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = \App\Models\Category::all();
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ], 200);
    }


}
