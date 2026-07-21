<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ProductCardResource;
use App\Models\Page;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Client/Home', [
            'featuredProducts' => Product::query()
                ->where('status', true)
                ->where('featured', true)
                ->with(['category:id,name,slug', 'productType:id,name,key,slug', 'visiblePrices'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Product $product): array => ProductCardResource::make($product)->resolve()),
            'navigationPages' => Page::query()
                ->where('status', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get(['title', 'slug']),
        ]);
    }
}
