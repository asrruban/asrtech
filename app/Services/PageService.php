<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\DB;

class PageService
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $seo
     */
    public function execute(Page $page, array $data, array $seo): Page
    {
        return DB::transaction(function () use ($page, $data, $seo): Page {
            $requestedSlug = trim((string) ($data['slug'] ?? ''));
            $data['slug'] = $this->slugs->generate(
                $page,
                $requestedSlug !== '' ? $requestedSlug : (string) $data['title'],
                'page',
            );

            $page->fill($data)->save();
            $page->seo()->updateOrCreate([], $seo);

            return $page->refresh()->load('seo');
        });
    }
}
