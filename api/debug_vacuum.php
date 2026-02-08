<?php
use Illuminate\Support\Facades\DB;
$data = DB::table('vacuum_logs')
    ->select('isotank_id', 'vacuum_value_mtorr', 'check_datetime')
    ->orderBy('isotank_id')
    ->orderBy('check_datetime', 'desc')
    ->limit(20)
    ->get();
dump($data->groupBy('isotank_id')->toArray());
