<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SavePageRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:100000'],
            'template' => ['required', Rule::in(['default', 'wide', 'legal'])],
            'status' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],

            'seo' => ['required', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.keywords' => ['nullable', 'string', 'max:2000'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2000'],
            'seo.robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'seo.open_graph_title' => ['nullable', 'string', 'max:255'],
            'seo.open_graph_description' => ['nullable', 'string', 'max:500'],
            'seo.open_graph_image' => ['nullable', 'url', 'max:2000'],
            'seo.twitter_card' => ['required', Rule::in(['summary', 'summary_large_image'])],
            'seo.schema_json' => ['nullable', 'json'],
        ];
    }

    /** @return array<string, mixed> */
    public function pageData(): array
    {
        return Arr::only($this->validated(), [
            'title',
            'slug',
            'excerpt',
            'content',
            'template',
            'status',
            'sort_order',
        ]);
    }

    /** @return array<string, mixed> */
    public function seoData(): array
    {
        $seo = $this->validated('seo');
        $data = is_array($seo) ? $seo : [];
        $data['schema_json'] = filled($data['schema_json'] ?? null)
            ? json_decode((string) $data['schema_json'], true, flags: JSON_THROW_ON_ERROR)
            : null;

        return $data;
    }
}
