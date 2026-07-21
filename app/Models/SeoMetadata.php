<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'meta_title',
    'meta_description',
    'keywords',
    'canonical_url',
    'robots',
    'open_graph_title',
    'open_graph_description',
    'open_graph_image',
    'twitter_card',
    'schema_json',
])]
class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
