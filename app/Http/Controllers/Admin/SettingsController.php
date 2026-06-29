<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group_name');
        return response()->json(['data' => $settings]);
    }

    /** Public subset — safe to expose without auth (used by landing page) */
    public function publicSettings()
    {
        return response()->json(['data' => [
            'commission_rate' => Setting::get('commission_rate', '12'),
        ]]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            '*' => ['string'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLog::record($request->user(), 'settings.updated', null, $data);

        return response()->json(['message' => 'Settings saved.', 'data' => Setting::all()->keyBy('key_name')]);
    }
}
