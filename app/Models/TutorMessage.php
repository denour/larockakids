<?php

namespace App\Models;

use App\Enums\TutorMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TutorMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label',
        'name',
        'message',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Obtiene un mensaje por su label
     */
    public static function findByLabel(string $label): ?self
    {
        return static::where('label', $label)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Reemplaza los tags del mensaje con sus valores correspondientes
     */
    public function replaceTags(array $values): string
    {
        $message = $this->message;
        $tags = TutorMessageType::getTags();

        foreach ($tags as $tag => $variable) {
            $value = $values[$variable] ?? '';
            $message = str_replace($tag, $value, $message);
        }

        return $message;
    }
} 