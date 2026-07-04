<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class DemoAccounts
{
    /**
     * @return Collection<int, array{email: string, name: string, role: string, label: string}>
     */
    public static function forSwitcher(): Collection
    {
        if (! config('demo.quick_login')) {
            return collect();
        }

        $names = User::query()
            ->whereIn('email', DemoRoles::emails())
            ->pluck('name', 'email');

        return collect(DemoRoles::accounts())
            ->map(fn (array $account): array => [
                'email' => $account['email'],
                'role' => $account['role'],
                'label' => __("auth.demo_roles.{$account['role']}"),
                'name' => $names->get($account['email'])
                    ?? __("auth.demo_roles.{$account['role']}"),
            ])
            ->values();
    }
}
