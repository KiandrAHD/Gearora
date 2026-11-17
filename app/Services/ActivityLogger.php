<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail untuk aksi sensitif.
 *
 * Jangan pernah mencatat password, token sesi, secret, atau isi file privat.
 */
class ActivityLogger
{
    /**
     * Kunci yang tidak boleh masuk ke log dalam bentuk apa pun.
     *
     * @var array<int, string>
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'proof_file',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $actor = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => ($actor ?? auth()->user())?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $this->redact($properties),
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function redact(array $properties): array
    {
        foreach (self::REDACTED_KEYS as $key) {
            unset($properties[$key]);
        }

        return $properties;
    }

    private function ipAddress(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }

    private function userAgent(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return mb_substr((string) request()->userAgent(), 0, 500) ?: null;
    }
}
