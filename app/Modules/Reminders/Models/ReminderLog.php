<?php

declare(strict_types=1);

namespace App\Modules\Reminders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Merkt sich, welche Erinnerung bereits verschickt wurde, damit jede nur einmal ankommt.
 *
 * @property string $id
 * @property int $user_id
 * @property string $kind
 * @property string $subject_id
 * @property string $stage
 */
final class ReminderLog extends Model
{
    use HasUlids;

    protected $table = 'reminder_logs';

    /** @var list<string> */
    protected $fillable = ['user_id', 'kind', 'subject_id', 'stage', 'sent_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
