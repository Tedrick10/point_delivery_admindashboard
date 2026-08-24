<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $regional = [
        'Yangon Branch',
        'Naypyitaw Branch',
        'Taungyi Branch',
        'MDY Branch',
    ];

    public function up(): void
    {
        $now = now();

        // Rename legacy short names → "* Branch" (keep ids for existing refs/admins).
        foreach (['Yangon' => 'Yangon Branch', 'MDY' => 'MDY Branch'] as $from => $to) {
            $row = DB::table('branches')->whereNull('deleted_at')->where('name', $from)->first()
                ?? DB::table('branches')->where('name', $from)->whereNotNull('deleted_at')->first();
            if ($row) {
                DB::table('branches')->where('id', $row->id)->update([
                    'name' => $to,
                    'status' => 1,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($this->regional as $name) {
            $exists = DB::table('branches')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();
            if ($exists) {
                DB::table('branches')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->update([
                        'name' => $name,
                        'status' => 1,
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                continue;
            }

            DB::table('branches')->insert([
                'name' => $name,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep regional branches; only revert Yangon Branch label if unused name free.
        $hasYangon = DB::table('branches')->where('name', 'Yangon')->exists();
        if (! $hasYangon) {
            DB::table('branches')
                ->where('name', 'Yangon Branch')
                ->update(['name' => 'Yangon', 'updated_at' => now()]);
        }
    }
};
