<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')
                ->nullable()
                ->after('password')
                ->comment('사용자가 실제로 로컬 비밀번호를 설정한 시각');
        });

        $this->backfillPasswordSetAt();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }

    private function backfillPasswordSetAt(): void
    {
        $firstSocialCreatedAtByUser = DB::table('social_accounts')
            ->select('user_id', DB::raw('MIN(created_at) as first_social_created_at'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        DB::table('users')
            ->select('id', 'created_at')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($firstSocialCreatedAtByUser): void {
                foreach ($users as $user) {
                    $firstSocialCreatedAt = $firstSocialCreatedAtByUser->get($user->id)?->first_social_created_at;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'password_set_at' => $this->resolvePasswordSetAt(
                                $user->created_at,
                                $firstSocialCreatedAt,
                            ),
                        ]);
                }
            });
    }

    private function resolvePasswordSetAt(
        ?string $userCreatedAt,
        ?string $firstSocialCreatedAt,
    ): ?string {
        if ($userCreatedAt === null) {
            return null;
        }

        if ($firstSocialCreatedAt === null) {
            return $userCreatedAt;
        }

        return $firstSocialCreatedAt > $userCreatedAt
            ? $userCreatedAt
            : null;
    }
};
