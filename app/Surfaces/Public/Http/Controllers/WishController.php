<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Services\BibliographicLookupService;
use App\Modules\Circulation\Actions\CreateBookWishAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Buchwunsch erfassen: ein öffentlicher Vorgang ohne Anmeldung. Wer angemeldet ist und ein Ausleihkonto hat, sieht den
 * Stand später in „Mein Konto“; alle anderen können freiwillig Name und E-Mail für eine Rückmeldung angeben.
 */
final class WishController
{
    public function create(Request $request): Response
    {
        return response()
            ->view('pages.surfaces.public.wish', [
                'patron' => $this->patron($request),
                'prefill' => ['title' => (string) $request->query('titel', ''), 'isbn' => (string) $request->query('isbn', '')],
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, CreateBookWishAction $create): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email:rfc', 'max:190'],
            'website' => ['nullable', 'string'],
        ], [
            'title.required' => 'Bitte gib den Titel des Buches an.',
            'contact_email.email' => 'Bitte gib eine gültige E-Mail-Adresse an oder lass das Feld leer.',
        ]);

        // Verstecktes Feld, das nur Programme ausfüllen: so tun, als wäre alles gut.
        if (($data['website'] ?? '') !== '') {
            return redirect()->route('public.wishes.create')->with('wish_success', 'Danke! Dein Buchwunsch ist angekommen.');
        }

        $patron = $this->patron($request);

        try {
            $create->execute(
                $patron,
                $data['title'],
                $data['author'] ?? null,
                $data['isbn'] ?? null,
                $data['note'] ?? null,
                $patron instanceof Patron ? null : ($data['contact_name'] ?? null),
                $patron instanceof Patron ? null : ($data['contact_email'] ?? null),
            );
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('public.wishes.create')->withInput()->withErrors(['title' => $exception->getMessage()]);
        }

        if ($patron instanceof Patron) {
            return redirect()->route('portal.wishes.index')->with('portal_success', 'Danke! Dein Wunsch „'.trim($data['title']).'“ ist angekommen. Du siehst hier, wie es damit weitergeht.');
        }

        return redirect()->route('public.wishes.create')->with('wish_success', 'Danke! Dein Buchwunsch „'.trim($data['title']).'“ ist angekommen.'.(($data['contact_email'] ?? '') !== '' ? ' Wir melden uns per E-Mail, sobald es etwas Neues gibt.' : ''));
    }

    /** ISBN nachschlagen, um Titel und Autor:in vorzuschlagen. Nichts wird gespeichert. */
    public function lookup(Request $request, BibliographicLookupService $lookup): JsonResponse
    {
        $isbn = strtoupper((string) preg_replace('/[^0-9Xx]/', '', (string) $request->query('isbn', '')));

        if (! in_array(strlen($isbn), [10, 13], true)) {
            return response()->json(['found' => false, 'message' => 'Die ISBN muss 10 oder 13 Stellen haben.']);
        }

        /** @var array{found: bool, title?: string, author?: string, message?: string} $result */
        $result = Cache::remember('wish-isbn:'.$isbn, now()->addDay(), static function () use ($lookup, $isbn): array {
            $found = $lookup->byIsbn($isbn);

            if (! $found->available) {
                return ['found' => false, 'message' => 'Die Suche ist gerade nicht erreichbar. Du kannst die Angaben auch selbst eintragen.'];
            }

            $record = $found->records[0] ?? null;

            if (! $record instanceof BibliographicRecord) {
                return ['found' => false, 'message' => 'Zu dieser ISBN wurde nichts gefunden. Du kannst die Angaben selbst eintragen.'];
            }

            $author = $record->contributors[0]['name'] ?? $record->responsibilityStatement ?? '';

            return ['found' => true, 'title' => $record->title.($record->subtitle ? ': '.$record->subtitle : ''), 'author' => $author, 'message' => 'Gefunden. Bitte prüfe die Angaben.'];
        });

        return response()->json($result);
    }

    private function patron(Request $request): ?Patron
    {
        $user = $request->user();

        if (! $user instanceof User || $user->patron_id === null) {
            return null;
        }

        $patron = Patron::query()->find($user->patron_id);

        return $patron instanceof Patron && $patron->isActive() ? $patron : null;
    }
}
