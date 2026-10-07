<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Auth\RoleRegistry;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Identity\Actions\CreateUserAccountAction;
use App\Modules\Identity\Actions\SetUserEnabledAction;
use App\Modules\Identity\Actions\UpdateUserRolesAction;
use App\Modules\Identity\Exceptions\UserAccountStateConflict;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Benutzerkonten in der Verwaltung: Mitarbeitende einladen, Rollen setzen, Konten deaktivieren. Alles ohne Konsole. */
final class UserAccountController
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function index(Request $request, RoleRegistry $roles): Response
    {
        $term = trim((string) $request->query('q', ''));
        $role = (string) $request->query('rolle', '');
        $state = (string) $request->query('status', '');

        $users = User::query()
            ->with('roleAssignments')
            ->when($term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static fn ($inner) => $inner->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($role !== '' && array_key_exists($role, $roles->all()), static fn ($query) => $query->whereHas('roleAssignments', static fn ($assignments) => $assignments->where('role_key', $role)))
            ->when($state === 'deaktiviert', static fn ($query) => $query->whereNotNull('disabled_at'))
            ->when($state === 'aktiv', static fn ($query) => $query->whereNull('disabled_at'))
            ->when($state === 'eingeladen', static fn ($query) => $query->whereNull('email_verified_at')->whereNull('disabled_at'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return response()
            ->view('pages.surfaces.administration.users.index', [
                'users' => $users,
                'roles' => $roles->all(),
                'term' => $term,
                'role' => $role,
                'state' => $state,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function create(RoleRegistry $roles): Response
    {
        return response()
            ->view('pages.surfaces.administration.users.create', ['roles' => $roles->all()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, CreateUserAccountAction $create, RoleRegistry $roles): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_keys($roles->all()))],
        ], [
            'name.required' => 'Bitte einen Namen angeben.',
            'email.required' => 'Bitte eine E-Mail-Adresse angeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'roles.required' => 'Bitte mindestens eine Rolle wählen.',
            'roles.min' => 'Bitte mindestens eine Rolle wählen.',
        ]);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $user = $create->execute($data['name'], $data['email'], array_values($data['roles']), $actor);
        } catch (UserAccountStateConflict $exception) {
            return back()->withInput()->withErrors(['email' => $exception->getMessage()]);
        }

        $this->audit->record('identity.user.created', 'Benutzerkonto angelegt und eingeladen.', null, ['user_id' => $user->getKey(), 'roles' => implode(',', $data['roles'])]);

        return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])
            ->with('user_success', 'Das Konto ist angelegt. '.$user->name.' hat eine E-Mail mit einem Link zum Festlegen des Passworts bekommen.');
    }

    public function edit(string $userId, RoleRegistry $roles): Response
    {
        $user = User::query()->with('roleAssignments')->findOrFail($userId);

        return response()
            ->view('pages.surfaces.administration.users.edit', ['account' => $user, 'roles' => $roles->all(), 'current' => $user->roleKeys()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function updateRoles(Request $request, string $userId, UpdateUserRolesAction $update, RoleRegistry $roles): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in(array_keys($roles->all()))],
        ]);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $update->execute($user, array_values($data['roles'] ?? []), $actor);
        } catch (UserAccountStateConflict $exception) {
            return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->withErrors(['roles' => $exception->getMessage()]);
        }

        $this->audit->record('identity.user.roles', 'Rollen eines Kontos geändert.', null, ['user_id' => $user->getKey(), 'roles' => implode(',', $data['roles'] ?? [])]);

        return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->with('user_success', 'Die Rollen sind gespeichert.');
    }

    public function disable(Request $request, string $userId, SetUserEnabledAction $action): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $action->disable($user, $data['reason'] ?? null, $actor);
        } catch (UserAccountStateConflict $exception) {
            return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->withErrors(['account' => $exception->getMessage()]);
        }

        $this->audit->record('identity.user.disabled', 'Benutzerkonto deaktiviert.', null, ['user_id' => $user->getKey()]);

        return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->with('user_success', 'Das Konto ist deaktiviert. Anmeldungen wurden beendet.');
    }

    public function enable(Request $request, string $userId, SetUserEnabledAction $action): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->enable($user, $actor);

        $this->audit->record('identity.user.enabled', 'Benutzerkonto aktiviert.', null, ['user_id' => $user->getKey()]);

        return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->with('user_success', 'Das Konto ist wieder aktiv.');
    }

    public function invite(Request $request, string $userId, SetUserEnabledAction $action): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        if (! $user->isEnabled()) {
            return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->withErrors(['account' => 'Ein deaktiviertes Konto bekommt keinen Link. Bitte zuerst aktivieren.']);
        }

        $sent = $action->invite($user, $actor);
        $this->audit->record('identity.user.invited', 'Einladung zum Setzen des Passworts verschickt.', null, ['user_id' => $user->getKey()]);

        return redirect()->route('administration.users.edit', ['userId' => $user->getKey()])->with($sent ? 'user_success' : 'user_error', $sent
            ? 'Der Link zum Festlegen eines neuen Passworts wurde an '.$user->email.' geschickt (60 Minuten gültig).'
            : 'Der Link konnte nicht verschickt werden. Bitte später erneut versuchen und den Mailversand prüfen.');
    }
}
