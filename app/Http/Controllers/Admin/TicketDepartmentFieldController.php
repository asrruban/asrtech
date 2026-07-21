<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTicketDepartmentFieldRequest;
use App\Models\TicketDepartment;
use App\Models\TicketDepartmentField;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Custom fields on a support department (the Custom Fields tab of the
 * department editor).
 */
class TicketDepartmentFieldController extends Controller
{
    public function store(SaveTicketDepartmentFieldRequest $request, TicketDepartment $department): RedirectResponse
    {
        $department->fields()->create($this->prepare($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Custom field added.')]);

        return redirect()->route('admin.support.departments.edit', $department);
    }

    public function update(
        SaveTicketDepartmentFieldRequest $request,
        TicketDepartment $department,
        TicketDepartmentField $field,
    ): RedirectResponse {
        $field->update($this->prepare($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Custom field saved.')]);

        return redirect()->route('admin.support.departments.edit', $department);
    }

    public function destroy(TicketDepartment $department, TicketDepartmentField $field): RedirectResponse
    {
        $field->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Custom field deleted.')]);

        return redirect()->route('admin.support.departments.edit', $department);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        // Options only make sense for drop downs; clear them otherwise so
        // stale lists don't linger after a type change.
        if (($data['type'] ?? null) !== 'dropdown') {
            $data['select_options'] = null;
        }

        $data['sort_order'] ??= 0;

        return $data;
    }
}
