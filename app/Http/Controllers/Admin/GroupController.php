<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGroupRequest;
use App\Models\Category;
use App\Models\Group;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct(private readonly SlugService $slugs) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Catalog/Groups/Index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'groups' => Group::query()
                ->with(['category:id,name,slug', 'seo'])
                ->withCount('products')
                ->latest()
                ->get(),
        ]);
    }

    public function store(SaveGroupRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->payload();
            $data['slug'] = $this->slugs->generate(new Group, $data['name'], 'group');

            $group = Group::query()->create($data);
            $this->saveSeo($request, $group);
        });

        return redirect()->route('admin.subcategories.index');
    }

    public function update(SaveGroupRequest $request, Group $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group): void {
            $group->loadMissing('category:id,slug');
            $previousCanonical = route('subcategories.show', [
                'category' => $group->category,
                'group' => $group,
            ]);
            $data = $request->payload();
            $data['slug'] = $this->slugs->generate($group, $data['name'], 'group');

            $group->update($data);
            $group->unsetRelation('category');
            $this->saveSeo($request, $group, $previousCanonical);
        });

        return redirect()->route('admin.subcategories.index');
    }

    public function destroy(Group $group): RedirectResponse
    {
        DB::transaction(function () use ($group): void {
            $group->seo()->delete();
            $group->delete();
        });

        return redirect()->route('admin.subcategories.index');
    }

    private function saveSeo(
        SaveGroupRequest $request,
        Group $group,
        ?string $previousCanonical = null,
    ): void {
        if (! $request->hasSeoInput()) {
            return;
        }

        $group->loadMissing('category:id,slug');
        $seo = $request->seoData();
        $canonical = $seo['canonical_url'] ?? null;
        $seo['canonical_url'] = blank($canonical) || $canonical === $previousCanonical
            ? route('subcategories.show', [
                'category' => $group->category,
                'group' => $group,
            ])
            : $canonical;

        if (is_array($seo['schema_json']) && blank($seo['schema_json']['url'] ?? null)) {
            $seo['schema_json']['url'] = $seo['canonical_url'];
        }

        $group->seo()->updateOrCreate([], $seo);
    }
}
