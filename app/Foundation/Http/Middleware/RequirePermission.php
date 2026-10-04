<?php

declare(strict_types=1);

namespace App\Foundation\Http\Middleware;

use App\Foundation\Auth\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function __construct(private readonly PermissionRegistry $permissions) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->permissions->has($permission)) {
            abort(500, "Unknown permission [{$permission}].");
        }

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! Gate::forUser($user)->allows($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
