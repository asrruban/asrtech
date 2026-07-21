<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSeoSettingsRequest;
use App\Services\SettingService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Global SEO: search engine verification codes, analytics/tracking IDs
 * (GA4, GTM, Meta Pixel), home page metadata, and Open Graph images.
 */
class SeoSettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly StorageService $storage,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Configuration/Settings/Seo', [
            'settings' => [
                'default_meta_title' => $this->settings->get('default_meta_title', (string) config('asrtech.seo.title')),
                'default_meta_description' => $this->settings->get('default_meta_description', (string) config('asrtech.seo.description')),
                'default_og_image' => $this->settings->get('default_og_image'),

                'home_meta_title' => $this->settings->get('home_meta_title'),
                'home_meta_description' => $this->settings->get('home_meta_description'),
                'home_meta_keywords' => $this->settings->get('home_meta_keywords'),
                'home_og_image' => $this->settings->get('home_og_image'),

                'google_site_verification' => $this->settings->get('google_site_verification'),
                'bing_site_verification' => $this->settings->get('bing_site_verification'),
                'yandex_site_verification' => $this->settings->get('yandex_site_verification'),
                'baidu_site_verification' => $this->settings->get('baidu_site_verification'),
                'pinterest_site_verification' => $this->settings->get('pinterest_site_verification'),

                'ga4_measurement_id' => $this->settings->get('ga4_measurement_id'),
                'gtm_container_id' => $this->settings->get('gtm_container_id'),
                'meta_pixel_id' => $this->settings->get('meta_pixel_id'),
            ],
        ]);
    }

    public function update(SaveSeoSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ([
            'default_meta_title',
            'default_meta_description',
            'home_meta_title',
            'home_meta_description',
            'home_meta_keywords',
            'google_site_verification',
            'bing_site_verification',
            'yandex_site_verification',
            'baidu_site_verification',
            'pinterest_site_verification',
            'ga4_measurement_id',
            'gtm_container_id',
            'meta_pixel_id',
        ] as $key) {
            $this->settings->put($key, isset($data[$key]) ? (string) $data[$key] : null);
        }

        foreach ([
            'og_image' => ['setting' => 'default_og_image', 'remove' => 'remove_og_image'],
            'home_og_image' => ['setting' => 'home_og_image', 'remove' => 'remove_home_og_image'],
        ] as $field => $target) {
            if ($request->hasFile($field)) {
                $this->settings->put(
                    $target['setting'],
                    $this->storage->storeUpload($request->file($field), 'branding'),
                );
            } elseif ($request->boolean($target['remove'])) {
                $this->settings->put($target['setting'], null);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SEO settings saved.')]);

        return redirect()->route('admin.settings.seo.edit');
    }
}
