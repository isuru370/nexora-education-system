<?php

namespace App\Http\Controllers\Web\Setting;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Settings > Notifications (admin only, whole system).
 */
class NotificationSettingController extends Controller
{
    public function index(): View
    {
        $settings = NotificationSetting::query()
            ->with('updatedBy:id,name')
            ->when(!feature('courses'), fn ($q) => $q->whereNotIn('key', NotificationSetting::COURSE_KEYS))
            ->orderBy('sort_order')
            ->get();

        return view('setting.notifications', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.is_enabled' => ['nullable', 'boolean'],
            'settings.*.channel' => ['nullable', 'in:push,sms'],
        ]);

        $changed = 0;

        DB::transaction(function () use ($validated, &$changed) {
            $rows = NotificationSetting::query()
                ->whereIn('key', array_keys($validated['settings']))
                ->lockForUpdate()
                ->get();

            foreach ($rows as $setting) {
                $input = $validated['settings'][$setting->key] ?? [];

                $setting->is_enabled = (bool) ($input['is_enabled'] ?? false);

                // Registration is always SMS
                if (!$setting->channel_locked && !empty($input['channel'])) {
                    $setting->channel = $input['channel'];
                }

                if ($setting->isDirty()) {
                    $setting->updated_by = auth()->id();
                    $setting->save();
                    $changed++;
                }
            }
        });

        NotificationSetting::clearCache();

        return redirect()
            ->route('admin.setting.notifications')
            ->with('success', $changed ? 'Notification settings saved.' : 'Nothing changed.');
    }
}
