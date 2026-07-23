<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ProductCardResource;
use App\Http\Resources\Client\ProductDetailResource;
use App\Models\Category;
use App\Models\Group;
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
        return $this->catalog($request);
    }

    public function category(Request $request, Category $category): Response
    {
        abort_unless($category->status, 404);

        return $this->catalog($request, $category);
    }

    public function subcategory(Request $request, Category $category, Group $group): Response
    {
        abort_unless($category->status && $group->status, 404);

        return $this->catalog($request, $category, $group);
    }

    private function catalog(
        Request $request,
        ?Category $category = null,
        ?Group $group = null,
    ): Response {
        $type = $request->string('type')->toString();
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Client/Products/Index', [
            'filters' => compact('type', 'search'),
            'productTypes' => ProductType::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['name', 'key', 'slug']),
            'categories' => Category::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'url' => route('categories.show', $item),
                ]),
            'landing' => $category ? $this->landing($category, $group) : null,
            'products' => Product::query()
                ->where('status', true)
                ->with(['category:id,name,slug', 'productType:id,name,key,slug', 'visiblePrices'])
                ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
                ->when($group, fn (Builder $query) => $query->where('group_id', $group->id))
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

    /** @return array<string, mixed> */
    private function landing(Category $category, ?Group $group): array
    {
        $category->loadMissing('seo');
        $group?->loadMissing('seo');

        $subject = $group ?? $category;
        $canonicalUrl = $group
            ? route('subcategories.show', ['category' => $category, 'group' => $group])
            : route('categories.show', $category);
        $seo = $subject->seo?->toArray() ?? [];
        $seo['canonical_url'] = filled($seo['canonical_url'] ?? null)
            ? $seo['canonical_url']
            : $canonicalUrl;
        $seo['meta_title'] = filled($seo['meta_title'] ?? null)
            ? $seo['meta_title']
            : $subject->name.' | ASRTech';
        $seo['meta_description'] = filled($seo['meta_description'] ?? null)
            ? $seo['meta_description']
            : ($subject->description ?: "Browse {$subject->name} products and services from ASRTech.");

        return [
            'kind' => $group ? 'subcategory' : 'category',
            'name' => $subject->name,
            'description' => $subject->description,
            'url' => $canonicalUrl,
            'seo' => $seo,
            'parent' => $group ? [
                'name' => $category->name,
                'url' => route('categories.show', $category),
            ] : null,
            'subcategories' => $category->groups()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Group $subcategory): array => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'url' => route('subcategories.show', [
                        'category' => $category,
                        'group' => $subcategory,
                    ]),
                    'active' => $group?->is($subcategory) ?? false,
                ])
                ->values()
                ->all(),
        ];
    }

    public function show(ProductType $productType, Product $product): Response
    {
        abort_unless($product->status && $product->type === $productType->key, 404);

        $product->load([
            'category:id,name,slug',
            'productType:id,name,key,slug',
            'visiblePrices',
            'seo',
            'customerReviews' => fn ($query) => $query->approved()->latest(),
            'customerReviews.user:id,name',
            'releases' => fn ($query) => $query
                ->available()
                ->orderByDesc('released_at')
                ->orderByDesc('id'),
        ]);

        $user = auth()->user();
        $canReview = $user instanceof User
            && $user->hasVerifiedEmail()
            && $user->orders()
                ->where('product_id', $product->id)
                ->where('status', OrderStatus::Paid)
                ->exists();
        $customerReview = $user instanceof User
            ? $product->customerReviews()
                ->where('user_id', $user->id)
                ->first()
            : null;
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
            'reviewState' => [
                'can_review' => $canReview,
                'login_url' => route('login'),
                'review' => $customerReview?->only(['rating', 'title', 'content', 'status']),
            ],
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
