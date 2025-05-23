<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'direction' => ['sometimes', 'string', 'in:asc,desc'],
        ]);

        $products = Product::query()
            ->when(
                $request->query('direction'),
                static fn(Builder $query, string $direction) => $query->orderBy('price', $direction),
                static fn(Builder $query) => $query->latest()
            )
            ->get();

        return response()->json($products);
    }

    public function show(Product $product): \Illuminate\Http\JsonResponse
    {
        return response()->json($product);
    }
}
