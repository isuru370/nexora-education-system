<?php

namespace App\Http\Controllers\Web\StudentClass;

use App\Http\Controllers\Controller;
use App\Services\Web\StudentClass\ClassCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classes -> categories -> fee options from a CSV: download the template
 * or the current classes, upload, check the preview, then confirm.
 */
class ClassImportController extends Controller
{
    protected const DIR = 'class-imports';

    public function __construct(private ClassCsvImportService $import)
    {
    }

    public function index(): View
    {
        return view('student_classes.import', [
            'columns' => ClassCsvImportService::COLUMNS,
            'lists' => $this->import->referenceLists(),
        ]);
    }

    public function template(): Response
    {
        return $this->download($this->import->toCsv($this->import->templateRows()), 'classes-template.csv');
    }

    public function export(): Response
    {
        return $this->download($this->import->toCsv($this->import->exportRows()), 'classes-' . now()->format('Y-m-d') . '.csv');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ], [
            'file.mimes' => 'Upload a .csv file (in Excel: File > Save As > CSV UTF-8).',
        ]);

        $text = (string) file_get_contents($request->file('file')->getRealPath());
        $parsed = $this->import->parse($text);

        if ($parsed['error']) {
            return back()->withErrors(['file' => $parsed['error']]);
        }

        $result = $this->import->run($parsed['rows'], false);

        // Old uploads nobody confirmed
        foreach (Storage::disk('local')->files(self::DIR) as $old) {
            if (Storage::disk('local')->lastModified($old) < now()->subDay()->getTimestamp()) {
                Storage::disk('local')->delete($old);
            }
        }

        // Keep the file for "Confirm" (only this user can use the token)
        $token = Str::random(40);
        Storage::disk('local')->put($this->path($token), $text);

        return view('student_classes.import_preview', [
            'result' => $result,
            'token' => $result['errors'] === 0 ? $token : null,
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:40', 'alpha_num']]);
        $path = $this->path($data['token']);

        if (!Storage::disk('local')->exists($path)) {
            return redirect()->route('class-import.index')->withErrors(['file' => 'This upload has expired. Please upload the file again.']);
        }

        $parsed = $this->import->parse((string) Storage::disk('local')->get($path));
        $result = $parsed['error'] ? null : $this->import->run($parsed['rows'], true);
        Storage::disk('local')->delete($path);

        if (!$result || !$result['saved']) {
            return redirect()->route('class-import.index')->withErrors(['file' => 'The file has errors now (the data changed after the preview). Please upload it again.']);
        }

        $s = $result['summary'];

        return redirect()->route('student-classes.index')->with('success', sprintf(
            'Import done: %d new class(es), %d class(es) updated, %d new category link(s), %d new fee option(s), %d fee option(s) updated.',
            $s['new_classes'], $s['updated_classes'], $s['new_categories'], $s['new_options'], $s['updated_options']
        ));
    }

    /** Uploads are kept per user so nobody can confirm another user's file. */
    protected function path(string $token): string
    {
        return self::DIR . '/' . auth()->id() . '-' . $token . '.csv';
    }

    protected function download(string $csv, string $name): Response
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }
}
