<?php

namespace App\Http\Controllers\Web\AppSetup;

use App\Http\Controllers\Controller;
use App\Support\AppSetup;
use App\Support\InstituteInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppSetupController extends Controller
{
    /** Admin page: the QR to scan from the staff app. */
    public function index(): View
    {
        return view('app-setup.index', [
            'qrContent' => AppSetup::qrContent(),
            'baseUrl' => AppSetup::baseUrl(),
            'institute' => InstituteInfo::toArray(),
        ]);
    }

    public function regenerate(): RedirectResponse
    {
        AppSetup::regenerate();

        return redirect()
            ->route('app-setup.index')
            ->with('success', 'New QR code created. Old printed QR codes no longer work. Phones already set up keep working.');
    }

    /**
     * Opened when the QR is scanned with the phone camera instead of the app.
     * Says what to do; it does not check or show the key.
     */
    public function help(): View
    {
        return view('app-setup.help', [
            'institute' => InstituteInfo::toArray(),
        ]);
    }
}
