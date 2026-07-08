<?php

namespace App\Support;

/**
 * The four walkthrough personas for passwordless TeamHUB entry.
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
            ['email' => 'workspace-lead@teamhub.test', 'role' => 'workspace_lead'],
            ['email' => 'project-lead@teamhub.test', 'role' => 'project_leader'],
            ['email' => 'staff@teamhub.test', 'role' => 'staff'],
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
