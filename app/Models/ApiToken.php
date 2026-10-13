<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    /**
     * Permisos que puede llevar un token. Cada ruta de la API y el servidor MCP
     * exigen uno; '*' los cubre todos.
     *
     * @var array<string, string>
     */
    public const ABILITIES = [
        '*' => 'Full Access',
        'kids:read' => 'Read Kids',
        'kids:write' => 'Edit Kids',
        'attendance:read' => 'Read Attendance',
        'attendance:write' => 'Attendance MCP (check-in/out, notify tutors)',
        'export' => 'Export Data',
    ];

    protected $fillable = ['name', 'token', 'abilities', 'expires_at'];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public static function generate(string $name, ?array $abilities = ['*'], ?\DateTime $expiresAt = null): array
    {
        $plainToken = Str::random(48);

        $token = static::create([
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plain_token' => $plainToken];
    }

    public static function findByPlainToken(string $plainToken): ?static
    {
        return static::where('token', hash('sha256', $plainToken))->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array('*', $this->abilities ?? []) || in_array($ability, $this->abilities ?? []);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
