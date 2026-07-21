<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'country_code', 'state', 'rate', 'priority', 'active'])]
class TaxRate extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'priority' => 'integer',
            'active' => 'boolean',
        ];
    }
}
