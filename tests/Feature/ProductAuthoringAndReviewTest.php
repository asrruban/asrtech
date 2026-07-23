<?php

namespace Tests\Feature;

use App\Enums\ProductReviewStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\ProductType;
use App\Models\User;
use App\Services\ProductNameBrandExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductAuthoringAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creation_can_include_an_initial_private_release_and_changelog(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin(), 'admin');
        $category = $this->category();

        $this->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'QuickBooks Payments Gateway module for WHMCS',
            'slug' => 'quickbooks-payments',
            'type' => 'whmcs_module',
            'status' => true,
            'featured' => false,
            'prices' => $this->prices(),
            'seo' => $this->seo(),
            'initial_release' => [
                'version' => '1.0.0',
                'title' => 'Initial release',
                'release_notes' => "Added secure checkout.\nAdded transaction syncing.",
                'released_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'download_limit' => 5,
                'status' => true,
                'file' => UploadedFile::fake()->create('quickbooks.zip', 64, 'application/zip'),
            ],
        ])->assertRedirect('/admin/products');

        $product = Product::query()->where('slug', 'quickbooks-payments')->firstOrFail();
        $release = ProductRelease::query()->whereBelongsTo($product)->sole();

        $this->assertSame('1.0.0', $product->version);
        $this->assertSame('quickbooks.zip', $release->original_filename);
        $this->assertSame(5, $release->download_limit);
        Storage::disk('local')->assertExists($release->file_path);
    }

    public function test_chatgpt_can_write_product_description_and_documentation(): void
    {
        $this->actingAs($this->admin(), 'admin');
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'gpt-5.6',
        ]);
        $content = [
            'short_description' => 'Accept QuickBooks payments from WHMCS.',
            'description' => 'Connect WHMCS billing workflows to QuickBooks payments.',
            'documentation_title' => 'QuickBooks Payments Documentation',
            'documentation_content' => "OVERVIEW\nConnect your accounts.\n\nINSTALLATION\nUpload the module package.",
            'documentation_meta_title' => 'QuickBooks Payments Documentation | ASRTech',
            'documentation_meta_description' => 'Install and configure the QuickBooks Payments Gateway module for WHMCS with practical setup and troubleshooting guidance.',
            'documentation_keywords' => 'QuickBooks payments, WHMCS gateway, module documentation',
        ];
        Http::fake([
            '*/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode($content, JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]),
        ]);

        $this->postJson('/admin/products/ai/content', [
            'name' => 'QuickBooks Payments Gateway module for WHMCS',
            'product_type' => 'WHMCS Modules',
            'category' => 'Payment Gateways',
        ])
            ->assertOk()
            ->assertJsonPath('content.documentation_title', 'QuickBooks Payments Documentation')
            ->assertJsonPath('content.short_description', $content['short_description']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['model'] === 'gpt-5.6'
            && $request['text']['format']['type'] === 'json_schema');
    }

    public function test_ai_icon_uses_only_the_extracted_product_brand_and_saves_the_image(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin(), 'admin');
        config([
            'asrtech.storage.driver' => 'local',
            'services.openai.api_key' => 'test-key',
            'services.openai.image_model' => 'gpt-image-2',
        ]);
        Http::fake([
            '*/images/generations' => Http::response([
                'data' => [[
                    'b64_json' => base64_encode('generated-png'),
                ]],
            ]),
        ]);

        $this->postJson('/admin/products/ai/icon', [
            'name' => 'QuickBooks Payments Gateway module for WHMCS',
        ])
            ->assertOk()
            ->assertJsonPath('brand', 'QuickBooks')
            ->assertJsonPath('url', fn (string $url): bool => str_starts_with($url, '/storage/products/'));

        Http::assertSent(function (Request $request): bool {
            $prompt = (string) $request['prompt'];

            return $request['model'] === 'gpt-image-2'
                && str_contains($prompt, '"QuickBooks"')
                && ! str_contains($prompt, 'Payments Gateway module for WHMCS');
        });

        $files = Storage::disk('uploads')->allFiles('products');
        $this->assertCount(1, $files);
        $this->assertSame('generated-png', Storage::disk('uploads')->get($files[0]));
    }

    public function test_brand_extractor_removes_generic_product_suffixes(): void
    {
        $extractor = app(ProductNameBrandExtractor::class);

        $this->assertSame(
            'QuickBooks',
            $extractor->extract('QuickBooks Payments Gateway module for WHMCS'),
        );
        $this->assertSame(
            'Google Workspace',
            $extractor->extract('Google Workspace Integration for WHMCS'),
        );
    }

    public function test_verified_purchaser_can_create_and_update_their_own_review(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->paidOrder($user, $product);
        $url = route('products.reviews.store', $product->storefrontRouteParameters());

        $this->actingAs($user)->post($url, [
            'rating' => 5,
            'title' => 'Easy to configure',
            'content' => 'The module worked well with our billing workflow.',
        ])->assertRedirect($product->storefrontUrl().'#reviews');

        $this->actingAs($user)->post($url, [
            'rating' => 4,
            'title' => 'Solid gateway',
            'content' => 'Setup was straightforward and the documentation helped.',
        ])->assertRedirect($product->storefrontUrl().'#reviews');

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'title' => 'Solid gateway',
            'status' => ProductReviewStatus::Pending->value,
        ]);
    }

    public function test_non_customer_cannot_publish_a_product_review(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)
            ->post(
                route('products.reviews.store', $product->storefrontRouteParameters()),
                [
                    'rating' => 5,
                    'content' => 'I did not purchase this product.',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_storefront_shows_release_changelog_and_customer_review_state(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Verified Buyer']);
        $product = $this->product();
        $this->paidOrder($user, $product);
        $product->customerReviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Recommended',
            'content' => 'Reliable in production.',
            'status' => ProductReviewStatus::Approved,
        ]);
        $product->releases()->create([
            'version' => '1.2.0',
            'title' => 'Gateway update',
            'release_notes' => "Added payment capture.\nImproved transaction logging.",
            'disk' => 'local',
            'file_path' => 'product-releases/test/package.zip',
            'original_filename' => 'package.zip',
            'mime_type' => 'application/zip',
            'file_size' => 100,
            'checksum_sha256' => hash('sha256', 'package'),
            'released_at' => now()->subMinute(),
            'status' => true,
        ]);

        $this->actingAs($user)
            ->get($product->storefrontUrl())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show')
                ->where('reviewState.can_review', true)
                ->where('reviewState.review.title', 'Recommended')
                ->where('product.reviews.0.name', 'Verified Buyer')
                ->where('product.reviews.0.verified_purchase', true)
                ->where('product.changelog.0.version', '1.2.0')
                ->where('product.changelog.0.notes.0', 'Added payment capture.'));
    }

    public function test_admin_can_moderate_but_not_author_customer_reviews(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Review Customer']);
        $product = $this->product();
        $this->paidOrder($user, $product);
        $reviewUrl = route('products.reviews.store', $product->storefrontRouteParameters());

        $this->actingAs($user)->post($reviewUrl, [
            'rating' => 5,
            'title' => 'Awaiting approval',
            'content' => 'This review was written by the customer.',
        ])->assertRedirect();

        $review = $product->customerReviews()->sole();
        $this->assertSame(ProductReviewStatus::Pending, $review->status);

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')
            ->get('/admin/product-reviews')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Catalog/ProductReviews/Index')
                ->where('counts.pending', 1)
                ->where('reviews.data.0.title', 'Awaiting approval'));

        $this->actingAs($admin, 'admin')
            ->patch("/admin/product-reviews/{$review->id}", [
                'status' => ProductReviewStatus::Approved->value,
                'moderation_note' => 'Verified and approved.',
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(ProductReviewStatus::Approved, $review->status);
        $this->assertSame($admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Product Author',
            'email' => 'author@example.com',
            'password' => 'a-secure-password',
        ]);
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
    }

    private function product(): Product
    {
        $type = ProductType::query()->where('key', 'whmcs_module')->firstOrFail();

        return Product::query()->create([
            'category_id' => $this->category()->id,
            'name' => 'QuickBooks Payments',
            'slug' => 'quickbooks-payments',
            'type' => $type->key,
            'price' => 99,
            'status' => true,
            'featured' => false,
        ]);
    }

    private function paidOrder(User $user, Product $product): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'currency' => 'USD',
            'amount' => 99,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function prices(): array
    {
        return [[
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => '99.00',
            'sale_price' => null,
            'setup_fee' => '0.00',
            'enabled' => true,
        ]];
    }

    /** @return array<string, mixed> */
    private function seo(): array
    {
        return [
            'meta_title' => 'QuickBooks Payments',
            'meta_description' => 'QuickBooks payment processing for WHMCS.',
            'keywords' => 'QuickBooks, WHMCS',
            'canonical_url' => null,
            'robots' => 'index,follow',
            'open_graph_title' => null,
            'open_graph_description' => null,
            'open_graph_image' => null,
            'twitter_card' => 'summary_large_image',
            'schema_json' => null,
        ];
    }
}
