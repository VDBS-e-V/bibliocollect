<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Patrons\Actions\BlockPatronAction;
use App\Modules\Patrons\Actions\UnblockPatronAction;
use App\Modules\Patrons\Exceptions\PatronBlockStateConflict;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PatronBlockController
{
    public function store(
        Request $request,
        string $patronId,
        FindPatronQuery $findPatron,
        BlockPatronAction $block,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        try {
            $block->execute($findPatron->byId($patronId), (string) $validated['reason'], $actor);
        } catch (PatronBlockStateConflict $exception) {
            return $this->withError($patronId, $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_success', 'Das Ausleihkonto wurde gesperrt.');
    }

    public function destroy(
        Request $request,
        string $patronId,
        FindPatronQuery $findPatron,
        UnblockPatronAction $unblock,
    ): RedirectResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        try {
            $unblock->execute($findPatron->byId($patronId), $actor);
        } catch (PatronBlockStateConflict $exception) {
            return $this->withError($patronId, $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_success', 'Die Ausleihsperre wurde aufgehoben.');
    }

    private function withError(string $patronId, string $message): RedirectResponse
    {
        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_error', $message);
    }
}
