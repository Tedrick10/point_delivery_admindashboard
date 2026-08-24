<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('os_receive_settlements')) {
            return;
        }

        Schema::table('os_receive_settlements', function (Blueprint $table) {
            if (! Schema::hasColumn('os_receive_settlements', 'os_payslip_paths')) {
                $table->json('os_payslip_paths')->nullable()->after('os_payslip_path');
            }
        });

        // Backfill single path into JSON array for existing rows.
        DB::table('os_receive_settlements')
            ->whereNotNull('os_payslip_path')
            ->where('os_payslip_path', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $existing = $row->os_payslip_paths ?? null;
                    if ($existing !== null && $existing !== '' && $existing !== '[]' && $existing !== 'null') {
                        continue;
                    }
                    DB::table('os_receive_settlements')
                        ->where('id', $row->id)
                        ->update([
                            'os_payslip_paths' => json_encode([(string) $row->os_payslip_path]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('os_receive_settlements')) {
            return;
        }

        Schema::table('os_receive_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('os_receive_settlements', 'os_payslip_paths')) {
                $table->dropColumn('os_payslip_paths');
            }
        });
    }
};
