<?php

declare(strict_types=1);

namespace App\Foundation\Navigation;

use RuntimeException;

final class NavigationRegistry
{
    public function surfaceLabel(string $surface): string
    {
        $definition = $this->surfaceDefinition($surface);
        $label = $definition['label'] ?? null;

        if (! is_string($label) || $label === '') {
            throw new RuntimeException("Navigation surface [{$surface}] needs a label.");
        }

        return $label;
    }

    /** @return list<NavigationItem> */
    public function allForSurface(string $surface): array
    {
        $definition = $this->surfaceDefinition($surface);
        $configured = $definition['items'] ?? [];

        if (! is_array($configured)) {
            throw new RuntimeException("Navigation items for surface [{$surface}] must be an array.");
        }

        $items = [];

        foreach ($configured as $item) {
            if (! is_array($item)) {
                throw new RuntimeException("Navigation item for surface [{$surface}] must be an array.");
            }

            $label = $item['label'] ?? null;
            $route = $item['route'] ?? null;
            $activePattern = $item['active'] ?? $route;
            $permission = $item['permission'] ?? null;
            $previewRoute = $item['preview_route'] ?? null;
            $order = $item['order'] ?? 100;

            if (! is_string($label) || $label === '' || ! is_string($route) || $route === '') {
                throw new RuntimeException("Navigation item for surface [{$surface}] needs label and route.");
            }

            if (! is_string($activePattern) || $activePattern === '') {
                throw new RuntimeException("Navigation item [{$label}] needs a valid active pattern.");
            }

            if ($permission !== null && (! is_string($permission) || $permission === '')) {
                throw new RuntimeException("Navigation item [{$label}] has an invalid permission.");
            }

            if ($previewRoute !== null && (! is_string($previewRoute) || $previewRoute === '')) {
                throw new RuntimeException("Navigation item [{$label}] has an invalid preview route.");
            }

            if (! is_int($order)) {
                throw new RuntimeException("Navigation item [{$label}] order must be an integer.");
            }

            $items[] = new NavigationItem(
                surface: $surface,
                label: $label,
                route: $route,
                activePattern: $activePattern,
                permission: $permission,
                previewRoute: $previewRoute,
                order: $order,
            );
        }

        usort($items, static fn (NavigationItem $left, NavigationItem $right): int => $left->order <=> $right->order);

        return $items;
    }

    /**
     * @param  callable(string): bool  $allows
     * @return list<NavigationItem>
     */
    public function visibleForSurface(string $surface, callable $allows): array
    {
        return array_values(array_filter(
            $this->allForSurface($surface),
            static fn (NavigationItem $item): bool => $item->permission === null || $allows($item->permission),
        ));
    }

    /** @return array<string, mixed> */
    private function surfaceDefinition(string $surface): array
    {
        $configured = config("navigation.surfaces.{$surface}");

        if (! is_array($configured)) {
            throw new RuntimeException("Unknown navigation surface [{$surface}].");
        }

        return $configured;
    }
}
