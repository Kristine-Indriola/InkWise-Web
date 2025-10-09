<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::rename('user_verifications', 'user_verifications_old');

            Schema::create('user_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('cascade');
                $table->string('email')->nullable()->index();
                $table->string('token')->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();
            });

            $oldRecords = DB::table('user_verifications_old')->get();

            foreach ($oldRecords as $record) {
                DB::table('user_verifications')->insert([
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'email' => DB::table('users')->where('user_id', $record->user_id)->value('email'),
                    'token' => $record->token,
                    'attempts' => 0,
                    'expires_at' => null,
                    'consumed_at' => null,
                    'verified_at' => $record->verified_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }

            Schema::drop('user_verifications_old');

            return;
        }

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE user_verifications MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->string('email')->nullable()->after('user_id');
            $table->unsignedSmallInteger('attempts')->default(0)->after('token');
            $table->timestamp('expires_at')->nullable()->after('attempts');
            $table->timestamp('consumed_at')->nullable()->after('verified_at');
            $table->index('email');
        });

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::rename('user_verifications', 'user_verifications_new');

            Schema::create('user_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
                $table->string('token')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });

            $newRecords = DB::table('user_verifications_new')->get();

            foreach ($newRecords as $record) {
                DB::table('user_verifications')->insert([
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'token' => $record->token,
                    'verified_at' => $record->verified_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }

            Schema::drop('user_verifications_new');

            return;
        }

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'attempts', 'expires_at', 'consumed_at']);
        });

        DB::statement('ALTER TABLE user_verifications MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
