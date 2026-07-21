<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ProductCardResource;
use App\Http\Resources\Client\ProductDetailResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Payments\Gateway;
use App\Payments\GatewayRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontProductController extends Controller
{
    public function __construct(private readonly GatewayRegistry $gateways) {}

    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString();
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Client/Products/Index', [
            'filters' => compact('type', 'search'),
            'productTypes' => ProductType::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['name', 'key', 'slug']),
            'categories' => Category::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'products' => Product::query()
                ->where('status', true)
                ->with(['category:id,name,slug', 'productType:id,name,key,slug', 'visiblePrices'])
                ->when($type, fn (Builder $query) => $query->where('type', $type))
                ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                }))
                ->orderByDesc('featured')
                ->latest()
                ->paginate(12)
                ->withQueryString()
                ->through(fn (Product $product) => ProductCardResource::make($product)->resolve()),
        ]);
    }

    public function show(ProductType $productType, Product $product): Response
    {
        abort_unless($product->status && $product->type === $productType->key, 404);

        $product->load(['category:id,name,slug', 'productType:id,name,key,slug', 'visiblePrices', 'seo']);

        $user = auth()->user();
        $gateways = collect($this->gateways->enabled())
            ->map(fn (Gateway $gateway): array => [
                'key' => $gateway->key(),
                'name' => $gateway->displayName(),
            ]);

        if ($product->has_free_trial && (! $user instanceof User || ! $user->hasUsedFreeTrial($product))) {
            $gateways->push([
                'key' => 'free_trial',
                'name' => '7 Days Free Trial',
            ]);
        }

        return Inertia::render('Client/Products/Show', [
            'product' => ProductDetailResource::make($product)->resolve(),
            'relatedProducts' => $this->relatedProducts($product),
            'paymentGateways' => $gateways->values()->all(),
        ]);
    }

    public function legacyShow(Product $product): RedirectResponse
    {
        abort_unless($product->status, 404);

        return redirect()->route('products.show', $product->storefrontRouteParameters(), 301);
    }

    public function legacyDocumentation(Product $product): RedirectResponse
    {
        abort_unless($product->status && filled($product->documentation_content), 404);

        return redirect()->route('products.documentation', $product->storefrontRouteParameters(), 301);
    }

    /** @return array<int, mixed> */
    private function relatedProducts(Product $product): array
    {
        return Product::query()
            ->where('status', true)
            ->whereKeyNot($product->getKey())
            ->where('category_id', $product->category_id)
            ->with(['category:id,name,slug', 'productType:id,name,key,slug', 'visiblePrices'])
            ->orderByDesc('featured')
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (Product $related): array => ProductCardResource::make($related)->resolve())
            ->values()
            ->all();
    }
}
