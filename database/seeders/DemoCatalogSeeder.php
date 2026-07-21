<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Group;
use App\Models\Page;
use App\Models\Product;
use App\Services\SettingService;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $modules = Category::query()->updateOrCreate(
            ['slug' => 'whmcs-modules'],
            ['name' => 'WHMCS Modules', 'description' => 'Automation and integrations for WHMCS.', 'status' => true],
        );
        $templates = Category::query()->updateOrCreate(
            ['slug' => 'templates'],
            ['name' => 'Templates', 'description' => 'Professional WHMCS and website templates.', 'status' => true],
        );
        $development = Category::query()->updateOrCreate(
            ['slug' => 'web-development'],
            ['name' => 'Web Development', 'description' => 'Custom Laravel and Vue development services.', 'status' => true],
        );

        $automation = Group::query()->updateOrCreate(
            ['slug' => 'automation'],
            ['category_id' => $modules->id, 'name' => 'Automation', 'description' => 'Tools that remove repetitive work.', 'status' => true],
        );

        $this->product(
            $modules->id,
            $automation->id,
            'WHMCS Automation Toolkit',
            'whmcs-automation-toolkit',
            'whmcs_module',
            'Automate common WHMCS operations with a secure, maintainable module.',
            [
                ['billing_cycle' => 'one_time', 'currency' => 'USD', 'price' => 149, 'sale_price' => null, 'setup_fee' => 0, 'enabled' => true],
                ['billing_cycle' => 'yearly', 'currency' => 'USD', 'price' => 99, 'sale_price' => 79, 'setup_fee' => 0, 'enabled' => true],
            ],
        );
        $this->product(
            $templates->id,
            null,
            'Hosting Business Vue Template',
            'hosting-business-vue-template',
            'template',
            'A responsive, conversion-focused template for hosting businesses.',
            [['billing_cycle' => 'one_time', 'currency' => 'USD', 'price' => 69, 'sale_price' => null, 'setup_fee' => 0, 'enabled' => true]],
        );
        $this->product(
            $development->id,
            null,
            'Professional Web Development',
            'professional-web-development',
            'web_development',
            'Custom Laravel, Vue, API, and business platform development.',
            [['billing_cycle' => 'one_time', 'currency' => 'USD', 'price' => 500, 'sale_price' => null, 'setup_fee' => 0, 'enabled' => true]],
        );

        $richProduct = Product::query()->where('slug', 'whmcs-automation-toolkit')->firstOrFail();
        $richProduct->update([
            'badge' => 'WHMCS 9 Ready',
            'version' => '2.4.0',
            'release_date' => '2026-07-01',
            'compatibility' => 'WHMCS 9.x back to 8.10',
            'php_compatibility' => 'PHP 8.4 back to 8.2',
            'featured_image' => '/images/products/automation-dashboard.svg',
            'documentation_title' => 'WHMCS Automation Toolkit Documentation',
            'documentation_content' => "Getting started\n\n1. Download the module package from your client account.\n2. Upload the package to your WHMCS installation.\n3. Activate the addon and enter your ASRTech license key.\n4. Review automation permissions before enabling production jobs.\n\nUpdates\n\nBack up your installation before installing a major update. Complete release notes are available in the changelog tab.",
            'documentation_meta_title' => 'WHMCS Automation Toolkit Documentation | ASRTech',
            'documentation_meta_description' => 'Installation, activation, configuration, and update documentation for WHMCS Automation Toolkit.',
            'documentation_keywords' => 'WHMCS automation documentation, WHMCS module installation, ASRTech',
            'documentation_robots' => 'index,follow',
            'gallery' => [
                ['url' => '/images/products/automation-workflows.svg', 'alt_text' => 'Automation workflow management'],
                ['url' => '/images/products/automation-activity.svg', 'alt_text' => 'Automation activity and audit log'],
            ],
            'feature_groups' => [
                [
                    'title' => 'Administration',
                    'description' => 'A focused toolkit for configuring and supervising WHMCS automation.',
                    'features' => [
                        'Create and manage reusable automation workflows',
                        'Control execution permissions by administrator role',
                        'Review searchable activity and failure logs',
                        'Retry failed jobs safely from the admin area',
                    ],
                ],
                [
                    'title' => 'Automation engine',
                    'description' => 'Reliable processing designed for production hosting environments.',
                    'features' => [
                        'Queue-based background processing',
                        'Configurable schedules and event triggers',
                        'Rate limiting and duplicate-run protection',
                        'Webhook notifications for successful and failed runs',
                    ],
                ],
                [
                    'title' => 'Operations and integrations',
                    'description' => 'Connect routine WHMCS work with the services your team already uses.',
                    'features' => [
                        'WHMCS client, invoice, order, and service events',
                        'Extensible hooks for custom provider integrations',
                        'Structured API responses and diagnostic context',
                        'Multi-language interface support',
                    ],
                ],
            ],
            'requirements' => [
                ['label' => 'WHMCS', 'value' => '9.x back to 8.10'],
                ['label' => 'PHP', 'value' => '8.4 back to 8.2'],
                ['label' => 'Database', 'value' => 'MySQL 8.0+ or MariaDB 10.6+'],
                ['label' => 'Scheduler', 'value' => 'WHMCS cron and queue worker'],
            ],
            'changelog' => [
                [
                    'version' => '2.4.0',
                    'released_at' => '2026-07-01',
                    'notes' => [
                        'Added WHMCS 9 compatibility and updated admin styling',
                        'Added configurable webhook notifications',
                        'Improved failed-job diagnostics and retry protection',
                    ],
                ],
                [
                    'version' => '2.3.0',
                    'released_at' => '2026-03-18',
                    'notes' => [
                        'Added reusable workflow templates',
                        'Improved large activity-log performance',
                    ],
                ],
            ],
            'addons' => [
                [
                    'name' => 'Professional installation',
                    'description' => 'ASRTech installs, activates, and verifies the module in your WHMCS environment.',
                    'price' => 79,
                    'sale_price' => 49,
                    'currency' => 'USD',
                    'purchase_url' => null,
                ],
                [
                    'name' => 'Custom workflow setup',
                    'description' => 'We design and configure one automation workflow for your business process.',
                    'price' => 199,
                    'sale_price' => null,
                    'currency' => 'USD',
                    'purchase_url' => null,
                ],
            ],
            'reviews' => [
                [
                    'name' => 'Hosting Operations Team',
                    'title' => 'Clear automation with useful logs',
                    'rating' => 5,
                    'content' => 'The workflow controls are straightforward and the audit history makes production troubleshooting much easier.',
                    'reviewed_at' => '2026-06-12',
                ],
            ],
        ]);

        $richProduct->prices()->where('billing_cycle', 'one_time')->firstOrFail()->update([
            'name' => 'Owned License',
            'description' => 'One-time license for a production WHMCS installation.',
            'features' => ['Lifetime license validity', 'One production installation', '12 months of updates', 'Standard technical support'],
            'featured' => true,
        ]);
        $richProduct->prices()->where('billing_cycle', 'yearly')->firstOrFail()->update([
            'name' => 'Annual License',
            'description' => 'Annual access with continuous updates and support.',
            'features' => ['One production installation', 'Updates while active', 'Priority technical support', 'Development license on request'],
            'featured' => false,
        ]);

        $page = Page::query()->updateOrCreate(
            ['slug' => 'about-asrtech'],
            [
                'title' => 'About ASRTech',
                'excerpt' => 'Professional WHMCS modules, templates, and web development.',
                'content' => "ASRTech builds reliable digital products for hosting companies and modern businesses.\n\nOur work focuses on secure architecture, maintainable code, performance, and dependable support.",
                'template' => 'default',
                'status' => true,
                'sort_order' => 10,
            ],
        );
        $page->seo()->updateOrCreate([], [
            'meta_title' => 'About ASRTech',
            'meta_description' => 'Learn about ASRTech and our professional WHMCS and web development services.',
            'robots' => 'index,follow',
            'twitter_card' => 'summary_large_image',
        ]);

        $settings = app(SettingService::class);
        foreach ([
            'app_name' => 'ASRTech',
            'company_name' => 'ASRTech',
            'tagline' => 'WHMCS modules, templates, and professional web development.',
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'default_meta_title' => 'ASRTech — WHMCS Modules and Web Development',
            'default_meta_description' => 'WHMCS modules, templates, licenses, and professional Laravel and Vue web development services.',
        ] as $key => $value) {
            $settings->put($key, $value);
        }
    }

    /** @param list<array<string, mixed>> $prices */
    private function product(
        int $categoryId,
        ?int $groupId,
        string $name,
        string $slug,
        string $type,
        string $description,
        array $prices,
    ): void {
        if ($prices === []) {
            throw new InvalidArgumentException('Demo products require at least one price.');
        }

        $amounts = array_map(
            fn (array $price): float => (float) $price['price'],
            $prices,
        );

        $product = Product::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'name' => $name,
                'type' => $type,
                'short_description' => $description,
                'description' => $description,
                'price' => min($amounts),
                'status' => true,
                'featured' => true,
            ],
        );
        $product->prices()->delete();
        $product->prices()->createMany($prices);
        $product->seo()->updateOrCreate([], [
            'meta_title' => $name.' | ASRTech',
            'meta_description' => $description,
            'keywords' => 'ASRTech, WHMCS, Laravel, Vue, '.$type,
            'robots' => 'index,follow',
            'twitter_card' => 'summary_large_image',
            'schema_json' => [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $name,
                'description' => $description,
            ],
        ]);
    }
}
