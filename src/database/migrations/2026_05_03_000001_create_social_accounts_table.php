<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_user_id');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id'], 'social_accounts_provider_user_unique');
            $table->unique(['user_id', 'provider'], 'social_accounts_user_provider_unique');
        });

        $this->migrateLegacyUserSocialData();
        $this->dropLegacyUserSocialColumns();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider', 20)->default('email')->after('password');
            }

            if (! Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable()->after('provider');
            }

            if (! Schema::hasColumn('users', 'social_meta')) {
                $table->text('social_meta')->nullable()->after('social_id');
            }
        });

        DB::table('users')->update([
            'provider' => 'email',
            'social_id' => null,
            'social_meta' => null,
        ]);

        $accounts = DB::table('social_accounts')
            ->orderBy('id')
            ->get(['user_id', 'provider', 'provider_user_id', 'meta']);

        foreach ($accounts as $account) {
            DB::table('users')
                ->where('id', $account->user_id)
                ->update([
                    'provider' => $account->provider,
                    'social_id' => $account->provider_user_id,
                    'social_meta' => $this->normalizeMetaForLegacyColumn($account->meta),
                ]);
        }

        Schema::dropIfExists('social_accounts');
    }

    private function migrateLegacyUserSocialData(): void
    {
        if (! Schema::hasColumns('users', ['provider', 'social_id', 'social_meta'])) {
            return;
        }

        DB::table('users')
            ->whereNotNull('social_id')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                $payloads = [];

                foreach ($users as $user) {
                    $provider = trim((string) $user->provider);
                    $providerUserId = trim((string) $user->social_id);

                    if ($provider === '' || $provider === 'email' || $providerUserId === '') {
                        continue;
                    }

                    $payloads[] = [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'provider_user_id' => $providerUserId,
                        'meta' => $this->normalizeMetaForJsonColumn($user->social_meta),
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ];
                }

                if ($payloads !== []) {
                    DB::table('social_accounts')->upsert(
                        $payloads,
                        ['provider', 'provider_user_id'],
                        ['user_id', 'meta', 'updated_at'],
                    );
                }
            });
    }

    private function dropLegacyUserSocialColumns(): void
    {
        if (! Schema::hasColumn('users', 'social_id')) {
            return;
        }

        foreach (Schema::getIndexes('users') as $index) {
            if (! ($index['unique'] ?? false)) {
                continue;
            }

            if (($index['columns'] ?? []) !== ['social_id']) {
                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($index): void {
                $table->dropUnique($index['name']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'provider') ? 'provider' : null,
                Schema::hasColumn('users', 'social_id') ? 'social_id' : null,
                Schema::hasColumn('users', 'social_meta') ? 'social_meta' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function normalizeMetaForJsonColumn(mixed $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        if (is_string($meta)) {
            $trimmed = trim($meta);

            return $trimmed === '' ? null : $trimmed;
        }

        $encoded = json_encode($meta);

        return $encoded === false ? null : $encoded;
    }

    private function normalizeMetaForLegacyColumn(mixed $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        if (is_string($meta)) {
            $trimmed = trim($meta);

            return $trimmed === '' ? null : $trimmed;
        }

        $encoded = json_encode($meta);

        return $encoded === false ? null : $encoded;
    }
};
