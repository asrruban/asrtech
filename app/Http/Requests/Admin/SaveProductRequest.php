<?php

namespace App\Http\Requests\Admin;

use App\Enums\BillingCycle;
use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $categoryId = $this->integer('category_id');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('groups', 'id')->where(
                    fn (Builder $query) => $query->where('category_id', $categoryId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'type' => ['required', 'string', Rule::exists('product_types', 'key')],
            'badge' => ['nullable', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:100'],
            'release_date' => ['nullable', 'date'],
            'compatibility' => ['nullable', 'string', 'max:255'],
            'php_compatibility' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'featured_image' => ['nullable', 'string', 'max:2000', 'regex:/^(https?:\/\/|\/)/i'],
            'featured_image_upload' => ['nullable', 'image', 'max:5120'],
            'demo_url' => ['nullable', 'url', 'max:2000'],
            'documentation_url' => ['nullable', 'url', 'max:2000'],
            'documentation_title' => ['nullable', 'string', 'max:255'],
            'purchase_url' => ['nullable', 'url', 'max:2000'],
            'trial_url' => ['nullable', 'url', 'max:2000'],
            'documentation_content' => ['nullable', 'string', 'max:50000'],
            'documentation_meta_title' => ['nullable', 'string', 'max:255'],
            'documentation_meta_description' => ['nullable', 'string', 'max:500'],
            'documentation_keywords' => ['nullable', 'string', 'max:2000'],
            'documentation_robots' => ['sometimes', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'documentation_open_graph_image' => ['nullable', 'url', 'max:2000'],
            'status' => ['required', 'boolean'],
            'featured' => ['required', 'boolean'],
            'has_free_trial' => ['sometimes', 'boolean'],

            'gallery' => ['nullable', 'array', 'max:50'],
            'gallery.*.url' => ['required', 'string', 'max:2000', 'regex:/^(https?:\/\/|\/)/i'],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:255'],
            'feature_groups' => ['nullable', 'array', 'max:20'],
            'feature_groups.*.title' => ['required', 'string', 'max:255'],
            'feature_groups.*.description' => ['nullable', 'string', 'max:1000'],
            'feature_groups.*.features' => ['required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'array', 'max:30'],
            'requirements.*.label' => ['required', 'string', 'max:100'],
            'requirements.*.value' => ['required', 'string', 'max:500'],
            'changelog' => ['nullable', 'array', 'max:50'],
            'changelog.*.version' => ['required', 'string', 'max:100'],
            'changelog.*.released_at' => ['nullable', 'date'],
            'changelog.*.notes' => ['required', 'string', 'max:20000'],
            'addons' => ['nullable', 'array', 'max:30'],
            'addons.*.name' => ['required', 'string', 'max:255'],
            'addons.*.description' => ['nullable', 'string', 'max:2000'],
            'addons.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'addons.*.sale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'addons.*.currency' => ['required', 'string', 'size:3'],
            'addons.*.purchase_url' => ['nullable', 'url', 'max:2000'],

            'initial_release' => [$product === null ? 'nullable' : 'prohibited', 'array'],
            'initial_release.version' => [
                Rule::requiredIf(fn (): bool => $this->hasFile('initial_release.file')),
                'nullable',
                'string',
                'max:100',
            ],
            'initial_release.title' => ['nullable', 'string', 'max:255'],
            'initial_release.release_notes' => ['nullable', 'string', 'max:20000'],
            'initial_release.released_at' => [
                Rule::requiredIf(fn (): bool => $this->hasFile('initial_release.file')),
                'nullable',
                'date',
            ],
            'initial_release.available_until' => [
                'nullable',
                'date',
                'after:initial_release.released_at',
            ],
            'initial_release.download_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'initial_release.status' => ['sometimes', 'boolean'],
            'initial_release.file' => ['nullable', 'file', 'max:512000'],

            'prices' => ['required', 'array', 'min:1'],
            'prices.*.billing_cycle' => ['required', 'distinct', Rule::enum(BillingCycle::class)],
            'prices.*.name' => ['nullable', 'string', 'max:255'],
            'prices.*.description' => ['nullable', 'string', 'max:2000'],
            'prices.*.currency' => ['required', 'string', 'size:3'],
            'prices.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'prices.*.sale_price' => ['nullable', 'numeric', 'min:0', 'lte:prices.*.price'],
            'prices.*.setup_fee' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'prices.*.purchase_url' => ['nullable', 'url', 'max:2000'],
            'prices.*.features' => ['nullable', 'string', 'max:10000'],
            'prices.*.featured' => ['sometimes', 'boolean'],
            'prices.*.enabled' => ['required', 'boolean'],

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
    public function productData(): array
    {
        $data = Arr::only($this->validated(), [
            'category_id',
            'group_id',
            'name',
            'slug',
            'sku',
            'type',
            'badge',
            'version',
            'release_date',
            'compatibility',
            'php_compatibility',
            'short_description',
            'description',
            'featured_image',
            'demo_url',
            'documentation_url',
            'documentation_title',
            'purchase_url',
            'trial_url',
            'documentation_content',
            'documentation_meta_title',
            'documentation_meta_description',
            'documentation_keywords',
            'documentation_robots',
            'documentation_open_graph_image',
            'status',
            'featured',
            'has_free_trial',
        ]);

        $data['gallery'] = $this->rows('gallery');
        $data['documentation_robots'] ??= 'index,follow';
        $data['requirements'] = $this->rows('requirements');
        $data['addons'] = $this->rows('addons');
        $data['feature_groups'] = array_map(
            fn (array $group): array => [
                ...$group,
                'features' => $this->lines((string) $group['features']),
            ],
            $this->rows('feature_groups'),
        );
        $data['changelog'] = array_map(
            fn (array $release): array => [
                ...$release,
                'notes' => $this->lines((string) $release['notes']),
            ],
            $this->rows('changelog'),
        );

        return $data;
    }

    /** @return array<string, mixed>|null */
    public function initialReleaseMetadata(): ?array
    {
        if (! $this->hasFile('initial_release.file')) {
            return null;
        }

        $release = $this->validated('initial_release');

        if (! is_array($release)) {
            return null;
        }

        unset($release['file']);
        $release['status'] = (bool) ($release['status'] ?? false);

        return $release;
    }

    /** @return list<array<string, mixed>> */
    public function prices(): array
    {
        $prices = $this->validated('prices');

        if (! is_array($prices)) {
            return [];
        }

        return array_values(array_map(
            fn (array $price): array => [
                ...$price,
                'features' => $this->lines((string) ($price['features'] ?? '')),
                'featured' => (bool) ($price['featured'] ?? false),
            ],
            $prices,
        ));
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

    /** @return list<array<string, mixed>> */
    private function rows(string $key): array
    {
        $rows = $this->validated($key, []);

        return is_array($rows) ? array_values($rows) : [];
    }

    /** @return list<string> */
    private function lines(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: []),
        ));
    }
}
