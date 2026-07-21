<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
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
                ->withCount(['groups', 'products'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $data = $request->payload();
        $data['slug'] = $this->slugs->generate(new Category, $data['name'], 'category');

        Category::query()->create($data);

        return redirect()->route('admin.categories.index');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->payload();
        $data['slug'] = $this->slugs->generate($category, $data['name'], 'category');

        $category->update($data);

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->groups()->exists() || $category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Move or delete this category’s groups and products first.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index');
    }
}
