<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TicketDepartment;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicSupportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Client/Support/Landing', [
            'departments' => $this->departments()->take(4),
            'documentationProducts' => Product::query()
                ->where('status', true)
                ->whereNotNull('documentation_content')
                ->where('documentation_content', '!=', '')
                ->with('productType:id,key,slug')
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'slug', 'type', 'documentation_title'])
                ->map(fn (Product $product): array => [
                    ...$product->only(['name', 'slug', 'documentation_title']),
                    'documentation_path' => $product->documentationUrl(),
                ]),
            'seo' => [
                'meta_title' => 'Support Center | ASRTech',
                'meta_description' => 'Find product documentation, answers, and the right ASRTech support department for your request.',
                'canonical_url' => route('support.center'),
                'robots' => 'index,follow',
            ],
        ]);
    }

    public function ticket(): Response
    {
        return Inertia::render('Client/Support/Departments', [
            'departments' => $this->departments(),
            'seo' => [
                'meta_title' => 'Choose a Support Department | ASRTech',
                'meta_description' => 'Choose the ASRTech support department best suited to your question or project.',
                'canonical_url' => route('support.departments'),
                'robots' => 'index,follow',
            ],
        ]);
    }

    /** @return Collection<int, TicketDepartment> */
    private function departments(): Collection
    {
        return TicketDepartment::query()
            ->where('hidden', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'description', 'clients_only']);
    }
}
