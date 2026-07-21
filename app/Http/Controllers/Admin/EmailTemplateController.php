<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WHMCS-style email template management: system templates (used by
 * mailables) can be edited and toggled, custom ones fully managed.
 */
class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Configuration/Settings/EmailTemplates/Index', [
            'templates' => EmailTemplate::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'category', 'subject', 'enabled', 'is_system']),
            'categories' => EmailTemplate::CATEGORIES,
        ]);
    }

    public function store(SaveEmailTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $template = EmailTemplate::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'is_system' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email template created.')]);

        return redirect()->route('admin.settings.emailtemplates.edit', $template);
    }

    public function edit(EmailTemplate $emailtemplate): Response
    {
        return Inertia::render('Admin/Configuration/Settings/EmailTemplates/Edit', [
            'template' => $emailtemplate->only([
                'id', 'name', 'slug', 'category', 'subject', 'body', 'enabled', 'is_system',
            ]),
            'categories' => EmailTemplate::CATEGORIES,
            'mergeFields' => [
                ...EmailTemplateService::GLOBAL_MERGE_FIELDS,
                ...EmailTemplateService::MERGE_FIELDS[$emailtemplate->slug] ?? [],
            ],
        ]);
    }

    public function update(SaveEmailTemplateRequest $request, EmailTemplate $emailtemplate): RedirectResponse
    {
        $data = $request->validated();

        // System templates are referenced by slug from mailables; their
        // identity (name/category) stays fixed — only content is editable.
        if ($emailtemplate->is_system) {
            unset($data['name'], $data['category']);
        }

        $emailtemplate->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email template saved.')]);

        return redirect()->route('admin.settings.emailtemplates.edit', $emailtemplate);
    }

    public function destroy(EmailTemplate $emailtemplate): RedirectResponse
    {
        if ($emailtemplate->is_system) {
            return back()->withErrors([
                'template' => __('System templates cannot be deleted — disable them instead.'),
            ]);
        }

        $emailtemplate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email template deleted.')]);

        return redirect()->route('admin.settings.emailtemplates.index');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) !== '' ? Str::slug($name) : 'template';
        $slug = $base;
        $suffix = 2;

        while (EmailTemplate::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
