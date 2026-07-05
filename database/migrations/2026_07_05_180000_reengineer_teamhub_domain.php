<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->dropLegacyTables();
        $this->dropUniversityColumns();
        $this->renameCoreTables();
        $this->renameForeignKeyColumns();
        $this->migrateRoleValues();
        $this->migrateUserRoles();
        $this->simplifyMembershipRequestColumns();

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        throw new RuntimeException('TeamHUB domain re-engineering migration cannot be reversed.');
    }

    private function dropLegacyTables(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('attendance_checkins');
        Schema::dropIfExists('event_attendances');
        Schema::dropIfExists('events');
        Schema::dropIfExists('volunteer_hours');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_placeholders');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('universities');
    }

    private function dropUniversityColumns(): void
    {
        if (Schema::hasTable('clubs')) {
            Schema::table('clubs', function (Blueprint $table) {
                if (Schema::hasColumn('clubs', 'university_id')) {
                    $table->dropForeign(['university_id']);
                    $table->dropColumn('university_id');
                }
            });

            Schema::table('clubs', function (Blueprint $table) {
                foreach (['category', 'college'] as $indexedColumn) {
                    if (Schema::hasColumn('clubs', $indexedColumn)) {
                        try {
                            $table->dropIndex([$indexedColumn]);
                        } catch (Throwable) {
                            //
                        }
                    }
                }
            });

            Schema::table('clubs', function (Blueprint $table) {
                if (Schema::hasColumn('clubs', 'category')) {
                    $table->dropColumn('category');
                }
                if (Schema::hasColumn('clubs', 'college')) {
                    $table->dropColumn('college');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'university_id')) {
                    $table->dropForeign(['university_id']);
                    $table->dropColumn('university_id');
                }
            });

            if (Schema::hasColumn('users', 'qr_token')) {
                Schema::table('users', function (Blueprint $table) {
                    try {
                        $table->dropUnique(['qr_token']);
                    } catch (Throwable) {
                        //
                    }
                });

                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('qr_token');
                });
            }
        }
    }

    private function renameCoreTables(): void
    {
        $renames = [
            'clubs' => 'workspaces',
            'club_memberships' => 'workspace_memberships',
            'club_membership_roles' => 'workspace_membership_roles',
            'club_join_applications' => 'workspace_membership_requests',
            'committees' => 'projects',
            'committee_memberships' => 'project_memberships',
            'committee_membership_roles' => 'project_membership_roles',
            'club_resources' => 'project_files',
            'posts' => 'project_updates',
        ];

        foreach ($renames as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    private function renameForeignKeyColumns(): void
    {
        $this->renameColumnIfExists('workspace_memberships', 'club_id', 'workspace_id');
        $this->renameColumnIfExists('workspace_membership_requests', 'club_id', 'workspace_id');
        $this->renameColumnIfExists('projects', 'club_id', 'workspace_id');
        $this->renameColumnIfExists('project_memberships', 'committee_id', 'project_id');
        $this->renameColumnIfExists('project_files', 'club_id', 'workspace_id');
        $this->renameColumnIfExists('project_files', 'committee_id', 'project_id');
        $this->renameColumnIfExists('project_updates', 'club_id', 'workspace_id');
        $this->renameColumnIfExists('project_updates', 'committee_id', 'project_id');
        $this->renameColumnIfExists('tasks', 'committee_id', 'project_id');
        $this->renameColumnIfExists('workspace_membership_roles', 'club_membership_id', 'workspace_membership_id');
        $this->renameColumnIfExists('project_membership_roles', 'committee_membership_id', 'project_membership_id');

        $this->rebuildMembershipRoleUniques();
        $this->rebuildTasksProjectIndex();
        $this->rebuildProjectsWorkspaceUnique();
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
                $blueprint->renameColumn($from, $to);
            });
        }
    }

    private function rebuildMembershipRoleUniques(): void
    {
        foreach ([
            'workspace_membership_roles' => [
                'club_membership_roles_club_membership_id_role_unique',
                'workspace_membership_roles_club_membership_id_role_unique',
                'workspace_membership_roles_workspace_membership_id_role_unique',
                ['workspace_membership_id', 'role'],
            ],
            'project_membership_roles' => [
                'committee_membership_roles_committee_membership_id_role_unique',
                'project_membership_roles_committee_membership_id_role_unique',
                'project_membership_roles_project_membership_id_role_unique',
                ['project_membership_id', 'role'],
            ],
        ] as $table => [$legacyIndexA, $legacyIndexB, $targetIndex, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ([$legacyIndexA, $legacyIndexB, $targetIndex] as $indexName) {
                try {
                    DB::statement("DROP INDEX IF EXISTS {$indexName}");
                } catch (Throwable) {
                    //
                }
            }

            if (Schema::hasColumn($table, $columns[0])) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                    try {
                        $blueprint->unique($columns);
                    } catch (Throwable) {
                        //
                    }
                });
            }
        }
    }

    private function rebuildTasksProjectIndex(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        foreach ([
            'tasks_committee_id_status_index',
            'tasks_project_id_status_index',
        ] as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            } catch (Throwable) {
                //
            }
        }

        if (Schema::hasColumn('tasks', 'project_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                try {
                    $table->index(['project_id', 'status']);
                } catch (Throwable) {
                    //
                }
            });
        }
    }

    private function rebuildProjectsWorkspaceUnique(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        foreach ([
            'committees_club_id_name_unique',
            'projects_club_id_name_unique',
            'committees_workspace_id_name_unique',
            'projects_workspace_id_name_unique',
        ] as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            } catch (Throwable) {
                //
            }
        }

        if (Schema::hasColumn('projects', 'workspace_id')) {
            Schema::table('projects', function (Blueprint $table) {
                try {
                    $table->unique(['workspace_id', 'name']);
                } catch (Throwable) {
                    //
                }
            });
        }
    }

    private function migrateRoleValues(): void
    {
        if (Schema::hasTable('workspace_membership_roles')) {
            DB::table('workspace_membership_roles')
                ->where('role', 'club_lead')
                ->update(['role' => 'workspace_lead']);

            DB::table('workspace_membership_roles')
                ->whereIn('role', ['supervisor', 'club_supervisor', 'organizer'])
                ->update(['role' => 'workspace_lead']);
        }

        if (Schema::hasTable('project_membership_roles')) {
            DB::table('project_membership_roles')
                ->where('role', 'committee_lead')
                ->update(['role' => 'project_lead']);
        }
    }

    private function migrateUserRoles(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('role', 'student')
            ->update(['role' => 'member']);

        DB::table('users')
            ->where('role', 'university_staff')
            ->update(['role' => 'admin']);
    }

    private function simplifyMembershipRequestColumns(): void
    {
        if (! Schema::hasTable('workspace_membership_requests')) {
            return;
        }

        Schema::table('workspace_membership_requests', function (Blueprint $table) {
            foreach (['level', 'major', 'university_email'] as $column) {
                if (Schema::hasColumn('workspace_membership_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
