<?php

namespace App\Actions\Dashboard;

use App\Models\User;
use App\Notifications\AdminMessageNotification;

class MessageWorkspaceLeaderAction
{
    public function execute(User $sender, User $leader, string $message): void
    {
        $leader->notify(new AdminMessageNotification($sender, $message));
    }
}
