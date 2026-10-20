<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** GET /settings : semua pengaturan (JSON bebas, mis. darkMode, blockedTags, reader prefs) */
    public function show(Request $r)
    {
        $s = UserSetting::firstOrCreate(['user_id' => $r->user()->id], ['data' => []]);
        return ['data' => $s->data ?? []];
    }

    /** PUT /settings {data: {...}} : digabung dengan pengaturan yang sudah ada */
    public function update(Request $r)
    {
        $r->validate(['data' => 'required|array']);

        $s = UserSetting::firstOrCreate(['user_id' => $r->user()->id], ['data' => []]);
        $s->data = array_merge($s->data ?? [], $r->input('data'));
        $s->save();

        return ['data' => $s->data];
    }
}
