<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageRequest;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Content/Pages/Index', [
            'pages' => Page::query()->with('seo')->orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Content/Pages/Create');
    }

    public function store(SavePageRequest $request): RedirectResponse
    {
        $this->pages->execute(new Page, $request->pageData(), $request->seoData());

        return redirect()->route('admin.pages.index');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('Admin/Content/Pages/Edit', [
            'managedPage' => $page->load('seo'),
        ]);
    }

    public function update(SavePageRequest $request, Page $page): RedirectResponse
    {
        $this->pages->execute($page, $request->pageData(), $request->seoData());

        return redirect()->route('admin.pages.index');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index');
    }
}
