<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * HasSlug (L5.5) — slug URL DRY untuk model ber-PK non-standar.
 *
 * Model boleh override slugSourceColumn() (default 'nama'). Slug auto-generate
 * saat save bila kosong / sumber berubah.
 * - Insert: base + token acak bila bentrok → saved() normalkan ke "-{PK}".
 * - Update: base + suffix PK bila bentrok.
 * Route-model-binding pakai `slug`.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model): void {
            if (blank($model->slug) || $model->isDirty($model->slugSourceColumn())) {
                $base = Str::slug((string) $model->slugSource()) ?: 'item';
                $pk = $model->getKey();
                $exists = static::where('slug', $base)
                    ->when($pk, fn ($q) => $q->where($model->getKeyName(), '!=', $pk))
                    ->exists();
                $model->slug = $exists
                    ? $base.'-'.($pk ?? Str::lower(Str::random(8)))
                    : $base;
            }
        });

        static::saved(function ($model): void {
            $base = Str::slug((string) $model->slugSource()) ?: 'item';
            $expected = $base.'-'.$model->getKey();
            // Bila slug bukan base (bentrok pakai token acak), normalkan ke base-{PK}.
            if ($model->slug !== $base && $model->slug !== $expected) {
                static::withoutEvents(fn () => $model->newQuery()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['slug' => $expected]));
                $model->slug = $expected;
            }
        });
    }

    protected function slugSourceColumn(): string { return 'nama'; }
    protected function slugSource(): string { return (string) $this->{$this->slugSourceColumn()}; }
    public function getRouteKeyName(): string { return 'slug'; }
}
