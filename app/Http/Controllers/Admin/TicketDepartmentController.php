<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTicketDepartmentRequest;
use App\Models\Admin;
use App\Models\TicketDepartment;
use App\Models\TicketDepartmentField;
use App\Services\DepartmentMailProbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WHMCS-style support ticket departments: behaviour flags, assigned
 * admin users, per-department custom fields, and the POP/IMAP mail
 * importing configuration with a live connection test.
 */
class TicketDepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Support/Departments/Index', [
            'departments' => TicketDepartment::query()
                ->withCount(['admins', 'fields'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'description', 'email', 'hidden', 'sort_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Support/Departments/Create', [
            'admins' => $this->adminOptions(),
            'mailProviders' => TicketDepartment::MAIL_PROVIDERS,
        ]);
    }

    public function store(SaveTicketDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $adminIds = $data['assigned_admin_ids'] ?? [];
        unset($data['assigned_admin_ids']);

        // Blank secrets are simply "not set" on create.
        $data = array_filter($data, fn ($value) => $value !== null && $value !== '') + [
            'mail_port' => 0,
            'sort_order' => (int) TicketDepartment::query()->max('sort_order') + 1,
        ];

        $department = TicketDepartment::query()->create($data);
        $department->admins()->sync($adminIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Support department created.')]);

        return redirect()->route('admin.support.departments.edit', $department);
    }

    public function edit(TicketDepartment $department): Response
    {
        return Inertia::render('Admin/Support/Departments/Edit', [
            'department' => [
                ...$department->only([
                    'id', 'name', 'description', 'email', 'clients_only',
                    'pipe_replies_only', 'no_autoresponder', 'feedback_request',
                    'prevent_client_closure', 'hidden', 'mail_provider',
                    'mail_hostname', 'mail_port', 'mail_email', 'mail_client_id',
                ]),
                // Secrets are write-only: expose configured flags, never values.
                'mail_password_configured' => filled($department->mail_password),
                'mail_client_secret_configured' => filled($department->mail_client_secret),
                'assigned_admin_ids' => $department->admins()->pluck('admins.id'),
            ],
            'fields' => $department->fields()->get([
                'id', 'name', 'type', 'description', 'validation',
                'select_options', 'required', 'admin_only', 'sort_order',
            ]),
            'admins' => $this->adminOptions(),
            'mailProviders' => TicketDepartment::MAIL_PROVIDERS,
            'fieldTypes' => TicketDepartmentField::TYPES,
        ]);
    }

    public function update(SaveTicketDepartmentRequest $request, TicketDepartment $department): RedirectResponse
    {
        $data = $request->validated();
        $adminIds = $data['assigned_admin_ids'] ?? [];
        unset($data['assigned_admin_ids']);

        // Write-only secrets: keep the stored value when left blank.
        foreach (['mail_password', 'mail_client_secret'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }

        $data['mail_port'] ??= 0;

        $department->update($data);
        $department->admins()->sync($adminIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Support department saved.')]);

        return redirect()->route('admin.support.departments.edit', $department);
    }

    public function destroy(TicketDepartment $department): RedirectResponse
    {
        // WHMCS behaviour: departments with tickets cannot be removed —
        // the tickets would lose their thread of record.
        if ($department->tickets()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This department has tickets and cannot be deleted.')]);

            return redirect()->route('admin.support.departments.index');
        }

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Support department deleted.')]);

        return redirect()->route('admin.support.departments.index');
    }

    /** Move a department one position up or down in the display order. */
    public function move(Request $request, TicketDepartment $department): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $ids = TicketDepartment::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $index = (int) array_search($department->id, $ids, true);
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($ids)) {
            [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];

            // Re-sequence so future swaps stay stable even when rows share
            // the same sort_order (e.g. legacy zeroes).
            foreach ($ids as $position => $id) {
                TicketDepartment::query()->whereKey($id)
                    ->update(['sort_order' => $position + 1]);
            }
        }

        return redirect()->route('admin.support.departments.index');
    }

    /**
     * Live POP3/IMAP login test for the mail importing configuration.
     * Uses the submitted password, falling back to the stored one so
     * the test also works after a page reload leaves the field blank.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:ticket_departments,id'],
            'mail_hostname' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_email' => ['required', 'email', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
        ]);

        $password = filled($data['mail_password'] ?? null)
            ? $data['mail_password']
            : TicketDepartment::query()->whereKey($data['department_id'] ?? null)->first()?->mail_password;

        if (blank($password)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Enter the email password to test the connection.')]);

            return back();
        }

        $error = app(DepartmentMailProbe::class)->test(
            $data['mail_hostname'],
            (int) $data['mail_port'],
            $data['mail_email'],
            $password,
        );

        Inertia::flash('toast', $error === null
            ? ['type' => 'success', 'message' => __('Connection successful — mail importing is configured correctly.')]
            : ['type' => 'error', 'message' => __('Connection failed: :error', ['error' => $error])]);

        return back();
    }

    /** @return Collection<int, Admin> */
    private function adminOptions()
    {
        return Admin::query()->orderBy('name')->get(['id', 'name', 'email']);
    }
}
