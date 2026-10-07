@php
    $permissionLabels = collect(config('authorization.permissions', []))->map(fn (array $definition): string => $definition['label'] ?? '');
@endphp

<div class="bc-role-list">
    @foreach ($roles as $key => $definition)
        <label class="bc-role-option">
            <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $current, true))>
            <span>
                <strong>{{ $definition['label'] }}</strong>
                <small>{{ collect($definition['permissions'])->reject(fn (string $permission): bool => str_starts_with($permission, 'surface.'))->map(fn (string $permission): string => $permissionLabels[$permission] ?? $permission)->take(6)->implode(', ') ?: 'Nur eigenes Konto und Katalog' }}</small>
            </span>
        </label>
    @endforeach
</div>
