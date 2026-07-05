<?php

namespace App\Support;

/**
 * The three walkthrough personas for passwordless TeamHUB entry.
 */
class DemoRoles
{
    /**
     * @return list<array{email: string, role: string}>
     */
    public static function accounts(): array
    {
        return [
            ['email' => 'admin@teamhub.test', 'role' => 'admin'],
            ['email' => 'staff@teamhub.test', 'role' => 'staff'],
            ['email' => 'project-lead@teamhub.test', 'role' => 'project_leader'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return array_column(self::accounts(), 'email');
    }

    public static function isAllowed(string $email): bool
    {
        return in_array($email, self::emails(), true);
    }

    /**
     * @return array{email: string, role: string}|null
     */
    public static function find(string $email): ?array
    {
        foreach (self::accounts() as $account) {
            if ($account['email'] === $email) {
                return $account;
            }
        }

        return null;
    }
}
