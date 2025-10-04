<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $rows = DB::table('templates')->select('id', 'front_image', 'back_image')->get();
        foreach ($rows as $r) {
            $front = $r->front_image ? str_replace(["\r", "\n", "\t"], '', trim($r->front_image)) : null;
            $back = $r->back_image ? str_replace(["\r", "\n", "\t"], '', trim($r->back_image)) : null;
            if ($front !== $r->front_image || $back !== $r->back_image) {
                DB::table('templates')->where('id', $r->id)->update(['front_image' => $front, 'back_image' => $back]);
            }
        }
    }

    public function down()
    {
        // no-op: cannot reliably restore original whitespace
    }
};
