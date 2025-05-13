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
        'message' => 'string',
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
        
        // Mapeo de tags a variables
        $tagMap = [
            '[tutor]' => '[tutor]',
            '[nino]' => '[nino]',
            '[fecha]' => '[fecha]',
            '[hora]' => '[hora]',
            '[comentario]' => '[comentario]',
        ];

        // Reemplazar cada tag con su valor correspondiente
        foreach ($tagMap as $tag => $variable) {
            $value = $values[$variable] ?? '';
            $message = str_replace($tag, $value, $message);
        }

        return $message;
    }

    /**
     * Mutador para el campo message
     */
    public function setMessageAttribute($value)
    {
        if ($value instanceof \Illuminate\Support\HtmlString) {
            $this->attributes['message'] = $value->toHtml();
        } else {
            $this->attributes['message'] = $value;
        }
    }

    /**
     * Accesor para el campo message
     */
    public function getMessageAttribute($value)
    {
        return new \Illuminate\Support\HtmlString($value);
    }
} 