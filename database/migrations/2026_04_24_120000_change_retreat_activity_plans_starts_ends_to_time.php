<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('retreat_activity_plans')) {
            return;
        }

        if (! Schema::hasColumn('retreat_activity_plans', 'starts_at')) {
            if (
                Schema::hasColumn('retreat_activity_plans', 'starts_at_tmp')
                && Schema::hasColumn('retreat_activity_plans', 'ends_at_tmp')
            ) {
                Schema::table('retreat_activity_plans', function (Blueprint $table) {
                    $table->renameColumn('starts_at_tmp', 'starts_at');
                    $table->renameColumn('ends_at_tmp', 'ends_at');
                });
                Schema::table('retreat_activity_plans', function (Blueprint $table) {
                    $table->time('starts_at')->nullable(false)->change();
                    $table->time('ends_at')->nullable(false)->change();
                });
                $this->ensureStartsIndex();
            }

            return;
        }

        $startsType = Schema::getColumnType('retreat_activity_plans', 'starts_at');
        if ($startsType === 'time') {
            $this->ensureStartsIndex();

            return;
        }

        if (Schema::hasColumn('retreat_activity_plans', 'starts_at_tmp')) {
            Schema::table('retreat_activity_plans', function (Blueprint $table) {
                $table->dropColumn(['starts_at_tmp', 'ends_at_tmp']);
            });
        }

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->time('starts_at_tmp')->nullable();
            $table->time('ends_at_tmp')->nullable();
        });

        foreach (DB::table('retreat_activity_plans')->orderBy('id')->cursor() as $row) {
            $starts = isset($row->starts_at) && $row->starts_at !== null && $row->starts_at !== ''
                ? Carbon::parse($row->starts_at)->format('H:i:s')
                : '00:00:00';
            $ends = isset($row->ends_at) && $row->ends_at !== null && $row->ends_at !== ''
                ? Carbon::parse($row->ends_at)->format('H:i:s')
                : '00:00:00';

            DB::table('retreat_activity_plans')->where('id', $row->id)->update([
                'starts_at_tmp' => $starts,
                'ends_at_tmp' => $ends,
            ]);
        }

        $this->dropIndexByNameIfExists('retreat_activity_plans_event_starts_idx');

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->renameColumn('starts_at_tmp', 'starts_at');
            $table->renameColumn('ends_at_tmp', 'ends_at');
        });

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->time('starts_at')->nullable(false)->change();
            $table->time('ends_at')->nullable(false)->change();
        });

        $this->ensureStartsIndex();
    }

    public function down(): void
    {
        if (! Schema::hasTable('retreat_activity_plans')) {
            return;
        }

        if (
            ! Schema::hasColumn('retreat_activity_plans', 'starts_at')
            || Schema::getColumnType('retreat_activity_plans', 'starts_at') !== 'time'
        ) {
            return;
        }

        $this->dropIndexByNameIfExists('retreat_activity_plans_event_starts_idx');
        $this->dropIndexByNameIfExists('retreat_activity_plans_session_starts_idx');
        $this->dropIndexByNameIfExists('retreat_activity_plans_starts_idx');

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->dateTime('starts_at_tmp')->nullable();
            $table->dateTime('ends_at_tmp')->nullable();
        });

        $today = now()->format('Y-m-d');

        foreach (DB::table('retreat_activity_plans')->orderBy('id')->cursor() as $row) {
            $starts = isset($row->starts_at) && $row->starts_at !== null && $row->starts_at !== ''
                ? $today.' '.Carbon::parse($row->starts_at)->format('H:i:s')
                : $today.' 00:00:00';
            $ends = isset($row->ends_at) && $row->ends_at !== null && $row->ends_at !== ''
                ? $today.' '.Carbon::parse($row->ends_at)->format('H:i:s')
                : $today.' 00:00:00';

            DB::table('retreat_activity_plans')->where('id', $row->id)->update([
                'starts_at_tmp' => $starts,
                'ends_at_tmp' => $ends,
            ]);
        }

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->renameColumn('starts_at_tmp', 'starts_at');
            $table->renameColumn('ends_at_tmp', 'ends_at');
        });

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable(false)->change();
            $table->dateTime('ends_at')->nullable(false)->change();
        });

        if (Schema::hasColumn('retreat_activity_plans', 'event_id')) {
            Schema::table('retreat_activity_plans', function (Blueprint $table) {
                $table->index(['event_id', 'starts_at'], 'retreat_activity_plans_event_starts_idx');
            });
        }
    }

    private function dropIndexByNameIfExists(string $indexName): void
    {
        foreach (Schema::getIndexes('retreat_activity_plans') as $index) {
            if (($index['name'] ?? '') === $indexName) {
                Schema::table('retreat_activity_plans', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });

                return;
            }
        }
    }

    private function ensureStartsIndex(): void
    {
        foreach (Schema::getIndexes('retreat_activity_plans') as $index) {
            if (in_array($index['name'] ?? '', [
                'retreat_activity_plans_event_starts_idx',
                'retreat_activity_plans_session_starts_idx',
                'retreat_activity_plans_starts_idx',
            ], true)) {
                return;
            }
        }

        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            if (Schema::hasColumn('retreat_activity_plans', 'event_id')) {
                $table->index(['event_id', 'starts_at'], 'retreat_activity_plans_event_starts_idx');
            } elseif (Schema::hasColumn('retreat_activity_plans', 'session_id')) {
                $table->index(['session_id', 'starts_at'], 'retreat_activity_plans_session_starts_idx');
            } else {
                $table->index('starts_at', 'retreat_activity_plans_starts_idx');
            }
        });
    }
};
