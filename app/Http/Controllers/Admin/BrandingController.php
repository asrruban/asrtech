<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly StorageService $storage,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
        ]);

        $uploaded = 0;

        foreach ([
            'logo' => 'branding_logo_url',
            'logo_light' => 'branding_logo_light_url',
            'logo_dark' => 'branding_logo_dark_url',
            'favicon' => 'branding_favicon_url',
        ] as $field => $setting) {
            if ($request->hasFile($field)) {
                $url = $this->storage->storeUpload($request->file($field), 'branding');
                $this->settings->put($setting, $url);
                $uploaded++;
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $uploaded > 0
                ? __(':count branding file(s) uploaded.', ['count' => $uploaded])
                : __('No files selected.'),
        ]);

        return redirect()->route('admin.settings.general.edit');
    }
}
