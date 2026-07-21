<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per application setting, WHMCS tblconfiguration style —
 * stored in the `configuration` table with values encrypted at rest.
 *
 * @property string $setting
 * @property string|null $value
 */
#[Fillable(['setting', 'value'])]
class Setting extends Model
{
    protected $table = 'configuration';

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }
}
