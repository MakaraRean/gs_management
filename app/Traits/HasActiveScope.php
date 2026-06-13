<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveScope
{
    public static function bootHasActiveScope(): void
    {
        static::addGlobalScope('active', fn (Builder $q) => $q->where($q->getModel()->getTable().'.is_active', true));
    }

    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    public function deactivate(): bool
    {
        return $this->forceFill(['is_active' => false])->save();
    }
}
