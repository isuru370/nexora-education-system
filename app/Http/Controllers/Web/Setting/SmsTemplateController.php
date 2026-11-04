<?php

namespace App\Http\Controllers\Web\Setting;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\Support\SmsTemplates;
use App\Support\SmsText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Settings > SMS Messages: the SMS texts parents get, and the cost of one SMS.
 */
class SmsTemplateController extends Controller
{
    public function index(): View
    {
        $edited = SmsTemplate::query()->with('updatedBy:id,name')->get()->keyBy('key');

        $templates = collect(SmsTemplates::definitions())->map(fn (array $definition, string $key) => $definition + [
            'key' => $key,
            'body' => SmsTemplates::body($key),
            'custom' => SmsTemplates::isCustom($key),
            'edited' => $edited->get($key),
        ]);

        return view('setting.sms-templates', [
            'templates' => $templates,
            'cost' => SmsTemplates::costPerSms(),
            'limits' => ['single' => SmsText::SINGLE, 'part' => SmsText::PART, 'max' => SmsTemplates::MAX_LENGTH],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cost' => ['required', 'numeric', 'min:0', 'max:100'],
            'templates' => ['required', 'array'],
            'templates.*' => ['nullable', 'string', 'max:' . SmsTemplates::MAX_LENGTH],
        ], [
            'templates.*.max' => 'An SMS text can have at most :max characters.',
        ]);

        $errors = [];
        foreach ($validated['templates'] as $key => $body) {
            if (!in_array($key, SmsTemplates::keys(), true)) {
                continue;
            }
            // empty = back to the default text
            if (trim((string) $body) === '') {
                continue;
            }
            foreach (SmsTemplates::problems($key, (string) $body) as $problem) {
                $errors["templates.{$key}"][] = SmsTemplates::definition($key)['label'] . ': ' . $problem;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['templates'] as $key => $body) {
                if (in_array($key, SmsTemplates::keys(), true)) {
                    SmsTemplates::save($key, $body, auth()->id());
                }
            }

            SmsTemplates::setCostPerSms((float) $validated['cost']);
        });

        return redirect()
            ->route('admin.setting.sms-templates')
            ->with('success', 'SMS messages saved.');
    }

    /**
     * Send the text (as it is in the box now, with the sample values) to one number.
     */
    public function test(Request $request, SmsService $sms): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', Rule::in(SmsTemplates::keys())],
            'body' => ['required', 'string', 'max:' . SmsTemplates::MAX_LENGTH],
            'mobile' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,15}$/'],
        ], [
            'mobile.required' => 'Type the mobile number to send the test SMS to.',
            'mobile.regex' => 'Type a valid mobile number (07XXXXXXXX).',
        ]);

        $label = SmsTemplates::definition($validated['key'])['label'];
        $problems = SmsTemplates::problems($validated['key'], $validated['body']);

        if ($problems) {
            return back()->withInput()->withErrors(['mobile' => "{$label}: " . implode(' ', $problems)]);
        }

        $message = SmsTemplates::preview($validated['key'], $validated['body']);

        try {
            $result = $sms->sendSms($validated['mobile'], $message);
        } catch (Throwable $e) {
            Log::warning('Test SMS failed', ['error' => $e->getMessage()]);
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors([
                'mobile' => 'Test SMS was not sent: ' . ($result['error'] ?? $result['provider_message'] ?? 'unknown error'),
            ]);
        }

        return back()->withInput()->with(
            'success',
            "Test \"{$label}\" SMS sent to {$validated['mobile']} (" . SmsText::parts($message) . ' SMS).'
        );
    }
}
