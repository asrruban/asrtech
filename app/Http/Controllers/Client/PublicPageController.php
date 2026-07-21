<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __invoke(Page $page): Response
    {
        abort_unless($page->status, 404);

        return $this->render($page);
    }

    public function legal(string $legalPage): Response
    {
        $page = Page::query()
            ->where('slug', $legalPage)
            ->where('status', true)
            ->firstOrFail();

        return $this->render($page);
    }

    private function render(Page $page): Response
    {
        return Inertia::render('Client/Pages/Show', [
            'managedPage' => $page->load('seo'),
        ]);
    }
}
