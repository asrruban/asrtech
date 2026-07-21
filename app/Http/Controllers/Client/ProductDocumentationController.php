<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductType;
use Inertia\Inertia;
use Inertia\Response;

class ProductDocumentationController extends Controller
{
    public function __invoke(ProductType $productType, Product $product): Response
    {
        abort_unless(
            $product->status
                && $product->type === $productType->key
                && filled($product->documentation_content),
            404,
        );

        $product->load(['category:id,name,slug', 'productType:id,name,key,slug']);
        $title = $product->documentation_title ?: "{$product->name} Documentation";
        $description = $product->documentation_meta_description ?: $product->short_description;

        return Inertia::render('Client/Products/Documentation', [
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
                'url' => $product->storefrontUrl(),
                'title' => $title,
                'content' => $product->documentation_content,
                'version' => $product->version,
                'release_date' => $product->release_date,
                'compatibility' => $product->compatibility,
                'documentation_url' => $product->documentation_url,
                'category' => $product->category->only(['name', 'slug']),
                'seo' => [
                    'meta_title' => $product->documentation_meta_title ?: $title,
                    'meta_description' => $description,
                    'keywords' => $product->documentation_keywords,
                    'canonical_url' => $product->documentationUrl(),
                    'robots' => $product->documentation_robots ?: 'index,follow',
                    'open_graph_title' => $product->documentation_meta_title ?: $title,
                    'open_graph_description' => $description,
                    'open_graph_image' => $product->documentation_open_graph_image ?: $product->featured_image,
                    'twitter_card' => 'summary_large_image',
                    'schema_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'TechArticle',
                        'headline' => $title,
                        'description' => $description,
                        'about' => $product->name,
                        'dateModified' => $product->updated_at?->toAtomString(),
                    ],
                ],
            ],
        ]);
    }
}
