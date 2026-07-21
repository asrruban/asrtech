<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * WHMCS-style email template — system templates are referenced by slug
 * from mailables and can be edited but never deleted.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string $subject
 * @property string $body
 * @property bool $enabled
 * @property bool $is_system
 */
#[Fillable(['name', 'slug', 'category', 'subject', 'body', 'enabled', 'is_system'])]
class EmailTemplate extends Model
{
    /** @var array<string, string> Category slug => label, WHMCS "email type" style. */
    public const CATEGORIES = [
        'general' => 'General',
        'invoice' => 'Invoice / Billing',
        'support' => 'Support',
        'product' => 'Product',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
