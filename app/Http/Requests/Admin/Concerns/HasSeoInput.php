<?php

namespace App\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait HasSeoInput
{
    /** @return array<string, mixed> */
    protected function seoRules(): array
    {
        return [
            'seo' => ['sometimes', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.keywords' => ['nullable', 'string', 'max:2000'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2000'],
            'seo.robots' => ['sometimes', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'seo.open_graph_title' => ['nullable', 'string', 'max:255'],
            'seo.open_graph_description' => ['nullable', 'string', 'max:500'],
            'seo.open_graph_image' => ['nullable', 'url', 'max:2000'],
            'seo.twitter_card' => ['sometimes', Rule::in(['summary', 'summary_large_image'])],
            'seo.schema_json' => ['nullable', 'json'],
        ];
    }

    public function hasSeoInput(): bool
    {
        return $this->has('seo');
    }

    /** @return array<string, mixed> */
    public function seoData(): array
    {
        $seo = $this->validated('seo');
        $data = is_array($seo) ? $seo : [];
        $data['robots'] ??= 'index,follow';
        $data['twitter_card'] ??= 'summary_large_image';
        $data['schema_json'] = filled($data['schema_json'] ?? null)
            ? json_decode((string) $data['schema_json'], true, flags: JSON_THROW_ON_ERROR)
            : null;

        return $data;
    }
}
