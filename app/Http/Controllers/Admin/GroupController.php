<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGroupRequest;
use App\Models\Category;
use App\Models\Group;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
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
                ->with('category:id,name')
                ->withCount('products')
                ->latest()
                ->get(),
        ]);
    }

    public function store(SaveGroupRequest $request): RedirectResponse
    {
        $data = $request->payload();
        $data['slug'] = $this->slugs->generate(new Group, $data['name'], 'group');

        Group::query()->create($data);

        return redirect()->route('admin.groups.index');
    }

    public function update(SaveGroupRequest $request, Group $group): RedirectResponse
    {
        $data = $request->payload();
        $data['slug'] = $this->slugs->generate($group, $data['name'], 'group');

        $group->update($data);

        return redirect()->route('admin.groups.index');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect()->route('admin.groups.index');
    }
}
