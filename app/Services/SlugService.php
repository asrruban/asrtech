<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    public function generate(Model $model, string $name, string $fallback): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;
        $counter = 2;

        while ($model->newQuery()
            ->where('slug', $slug)
            ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
