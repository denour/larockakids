<?php

namespace App\Mcp\Concerns;

use App\Models\Kid;
use Illuminate\Validation\ValidationException;

trait FindsKidByName
{
    /**
     * Resolve exactly one kid from a free-text name. Every whitespace-separated
     * term must match the first or last name, so "Giana", "Giana Perez" and
     * "Perez Giana" all resolve the same kid.
     *
     * ponytail: LIKE is accent-sensitive, so "Perez" won't match "Pérez". Swap
     * for an unaccented column or a collation match if the salón types accents.
     *
     * @throws ValidationException when no kid, or more than one, matches.
     */
    protected function resolveKid(string $name): Kid
    {
        $terms = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $matches = Kid::query()
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($sub) use ($term): void {
                        $sub->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
            })
            ->get();

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'kid_name' => "No se encontró ningún niño que coincida con \"{$name}\".",
            ]);
        }

        if ($matches->count() > 1) {
            $names = $matches->map->full_name->implode(', ');

            throw ValidationException::withMessages([
                'kid_name' => "Varios niños coinciden con \"{$name}\": {$names}. Especifica nombre y apellido.",
            ]);
        }

        return $matches->first();
    }
}
