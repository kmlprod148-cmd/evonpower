<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter les migrations.
     */
    public function up(): void
    {
        // Ajouter client_id à la table users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table charging_stations
        if (Schema::hasTable('charging_stations')) {
            Schema::table('charging_stations', function (Blueprint $table) {
                if (!Schema::hasColumn('charging_stations', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table transactions
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table business_profiles
        if (Schema::hasTable('business_profiles')) {
            Schema::table('business_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('business_profiles', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table subscriptions
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table notifications
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table activity_log
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_log', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table audits
        if (Schema::hasTable('audits')) {
            Schema::table('audits', function (Blueprint $table) {
                if (!Schema::hasColumn('audits', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table permissions
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table roles
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table model_has_roles
        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                if (!Schema::hasColumn('model_has_roles', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('role_id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table model_has_permissions
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('model_has_permissions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('permission_id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table role_has_permissions
        if (Schema::hasTable('role_has_permissions')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('role_has_permissions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('permission_id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table personal_access_tokens
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('personal_access_tokens', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table password_reset_tokens
        if (Schema::hasTable('password_reset_tokens')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('password_reset_tokens', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('email')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table failed_jobs
        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('failed_jobs', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table jobs
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('jobs', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table sessions
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('sessions', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table cache
        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                if (!Schema::hasColumn('cache', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('key')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table cache_locks
        if (Schema::hasTable('cache_locks')) {
            Schema::table('cache_locks', function (Blueprint $table) {
                if (!Schema::hasColumn('cache_locks', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('key')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table migrations
        if (Schema::hasTable('migrations')) {
            Schema::table('migrations', function (Blueprint $table) {
                if (!Schema::hasColumn('migrations', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table password_resets
        if (Schema::hasTable('password_resets')) {
            Schema::table('password_resets', function (Blueprint $table) {
                if (!Schema::hasColumn('password_resets', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('email')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table personal_access_tokens
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('personal_access_tokens', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table teams
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                if (!Schema::hasColumn('teams', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table team_user
        if (Schema::hasTable('team_user')) {
            Schema::table('team_user', function (Blueprint $table) {
                if (!Schema::hasColumn('team_user', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table team_invitations
        if (Schema::hasTable('team_invitations')) {
            Schema::table('team_invitations', function (Blueprint $table) {
                if (!Schema::hasColumn('team_invitations', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table oauth_clients
        if (Schema::hasTable('oauth_clients')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                if (!Schema::hasColumn('oauth_clients', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table oauth_auth_codes
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                if (!Schema::hasColumn('oauth_auth_codes', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table oauth_access_tokens
        if (Schema::hasTable('oauth_access_tokens')) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('oauth_access_tokens', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table oauth_refresh_tokens
        if (Schema::hasTable('oauth_refresh_tokens')) {
            Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('oauth_refresh_tokens', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }

        // Ajouter client_id à la table oauth_personal_access_clients
        if (Schema::hasTable('oauth_personal_access_clients')) {
            Schema::table('oauth_personal_access_clients', function (Blueprint $table) {
                if (!Schema::hasColumn('oauth_personal_access_clients', 'client_id')) {
                    $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->onDelete('cascade');
                    $table->index('client_id');
                }
            });
        }
    }

    /**
     * Annuler les migrations.
     */
    public function down(): void
    {
        // Supprimer client_id de toutes les tables
        $tables = [
            'users', 'charging_stations', 'transactions', 'business_profiles',
            'subscriptions', 'payments', 'notifications', 'activity_log',
            'audits', 'permissions', 'roles', 'model_has_roles',
            'model_has_permissions', 'role_has_permissions', 'personal_access_tokens',
            'password_reset_tokens', 'failed_jobs', 'jobs', 'sessions',
            'cache', 'cache_locks', 'migrations', 'password_resets',
            'teams', 'team_user', 'team_invitations', 'oauth_clients',
            'oauth_auth_codes', 'oauth_access_tokens', 'oauth_refresh_tokens',
            'oauth_personal_access_clients'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'client_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['client_id']);
                    $table->dropColumn('client_id');
                });
            }
        }
    }
};
