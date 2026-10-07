<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Str;

/** Erfasst einen Buchwunsch. Je Person gibt es höchstens eine Handvoll offener Wünsche, und denselben Titel nur einmal. */
final readonly class CreateBookWishAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(?Patron $patron, string $title, ?string $author, ?string $isbn, ?string $note): BookWish
    {
        $title = trim($title);
        $isbn = $isbn !== null ? preg_replace('/[^0-9Xx]/', '', $isbn) : null;
        $isbn = $isbn !== null && $isbn !== '' ? Str::upper($isbn) : null;

        if ($title === '') {
            throw new CirculationRuleViolation(['Bitte einen Titel angeben.']);
        }

        if ($isbn !== null && ! in_array(strlen($isbn), [10, 13], true)) {
            throw new CirculationRuleViolation(['Die ISBN muss 10 oder 13 Stellen haben. Du kannst sie auch weglassen.']);
        }

        if ($patron instanceof Patron) {
            $open = BookWish::query()->where('patron_id', $patron->getKey())->whereIn('status', WishStatus::openValues());

            if ((clone $open)->count() >= max(1, (int) config('circulation.max_open_wishes', 3))) {
                throw new CirculationRuleViolation(['Du hast schon '.(int) config('circulation.max_open_wishes', 3).' offene Wünsche. Bitte warte, bis darüber entschieden ist, oder ziehe einen zurück.']);
            }

            $duplicate = (clone $open)->where(static function ($query) use ($isbn, $title): void {
                $query->whereRaw('lower(title) = ?', [mb_strtolower($title)]);

                if ($isbn !== null) {
                    $query->orWhere('isbn', $isbn);
                }
            })->exists();

            if ($duplicate) {
                throw new CirculationRuleViolation(['Diesen Wunsch hast du schon abgegeben.']);
            }
        }

        $wish = BookWish::query()->create([
            'patron_id' => $patron?->getKey(),
            'title' => mb_substr($title, 0, 255),
            'author' => $author !== null && trim($author) !== '' ? mb_substr(trim($author), 0, 255) : null,
            'isbn' => $isbn,
            'note' => $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null,
            'status' => WishStatus::New,
        ]);

        $this->audit->record('wishes.created', 'Buchwunsch erfasst.', $wish, ['patron_id' => $patron?->getKey()]);

        return $wish;
    }
}
