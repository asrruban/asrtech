<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly SlugService $slugs) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Catalog/Categories/Index', [
            'categories' => Category::query()
                ->with('seo')
                ->withCount(['groups', 'products'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->payload();
            $data['slug'] = $this->slugs->generate(new Category, $data['name'], 'category');

            $category = Category::query()->create($data);
            $this->saveSeo($request, $category);
        });

        return redirect()->route('admin.categories.index');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category): void {
            $previousCanonical = route('categories.show', $category);
            $data = $request->payload();
            $data['slug'] = $this->slugs->generate($category, $data['name'], 'category');

            $category->update($data);
            $this->saveSeo($request, $category, $previousCanonical);
        });

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->groups()->exists() || $category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Move or delete this category’s groups and products first.',
            ]);
        }

        DB::transaction(function () use ($category): void {
            $category->seo()->delete();
            $category->delete();
        });

        return redirect()->route('admin.categories.index');
    }

    private function saveSeo(
        SaveCategoryRequest $request,
        Category $category,
        ?string $previousCanonical = null,
    ): void {
        if (! $request->hasSeoInput()) {
            return;
        }

        $seo = $request->seoData();
        $canonical = $seo['canonical_url'] ?? null;
        $seo['canonical_url'] = blank($canonical) || $canonical === $previousCanonical
            ? route('categories.show', $category)
            : $canonical;

        if (is_array($seo['schema_json']) && blank($seo['schema_json']['url'] ?? null)) {
            $seo['schema_json']['url'] = $seo['canonical_url'];
        }

        $category->seo()->updateOrCreate([], $seo);
    }
}
