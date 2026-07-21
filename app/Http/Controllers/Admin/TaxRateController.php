<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Commerce/TaxRates/Index', [
            'taxRates' => TaxRate::query()
                ->orderByDesc('active')
                ->orderByDesc('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TaxRate::query()->create($this->validated($request));

        return redirect()->route('admin.tax-rates.index');
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $taxRate->update($this->validated($request));

        return redirect()->route('admin.tax-rates.index');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('admin.tax-rates.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'priority' => ['required', 'integer', 'min:0', 'max:999999'],
            'active' => ['required', 'boolean'],
        ]);

        $data['country_code'] = filled($data['country_code'] ?? null) ? strtoupper(trim($data['country_code'])) : null;
        $data['state'] = filled($data['state'] ?? null) ? trim($data['state']) : null;

        if ($data['state'] !== null && $data['country_code'] === null) {
            throw ValidationException::withMessages([
                'country_code' => __('Choose a country before entering a state or region.'),
            ]);
        }

        return $data;
    }
}
