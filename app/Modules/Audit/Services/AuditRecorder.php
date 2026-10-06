<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Schreibt Auditereignisse. Aufrufer rufen den Recorder innerhalb ihrer Transaktion auf, damit Ereignis und
 * Fachänderung gemeinsam gelten oder gemeinsam zurückgerollt werden.
 */
final readonly class AuditRecorder
{
    public function __construct(private BusinessClock $clock) {}

    /**
     * @param  array<string, scalar|null>  $context  Nur Kennungen und Fachwerte, keine Namen oder Freitexte von Personen
     */
    public function record(
        string $action,
        string $summary,
        ?Model $subject = null,
        array $context = [],
        ?int $actorUserId = null,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'occurred_at' => $this->clock->now(),
            'actor_user_id' => $actorUserId ?? (is_int(Auth::id()) ? Auth::id() : null),
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'summary' => mb_substr($summary, 0, 500),
            'context' => $context === [] ? null : $context,
        ]);
    }
}
