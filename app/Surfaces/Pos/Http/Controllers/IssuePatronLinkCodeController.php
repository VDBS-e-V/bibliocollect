<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Patrons\Actions\IssuePatronLinkCodeAction;
use App\Modules\Patrons\Exceptions\PatronLinkCodeCannotBeIssued;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class IssuePatronLinkCodeController
{
    public function __invoke(
        Request $request,
        string $patronId,
        FindPatronQuery $findPatron,
        IssuePatronLinkCodeAction $issue,
    ): Response|RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        try {
            $issued = $issue->execute($patron, $actor);
        } catch (PatronLinkCodeCannotBeIssued) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->with('workspace_error', 'Für dieses Ausleihkonto kann derzeit kein Onlinekonto-Code ausgegeben werden.');
        }

        return response()
            ->view('pages.surfaces.pos.patrons.link-code', [
                'patron' => $patron,
                'code' => $issued->code,
                'expiresAt' => $issued->expiresAt,
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
