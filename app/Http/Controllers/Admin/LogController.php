<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogController extends Controller
{
    public function content(Request $request)
{
    try {
        $filename = $request->query('file');

        $path = $this->getSelectedLogPath($filename);

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Selected log file not found.',
                'file' => $filename,
                'content' => '',
            ], 404);
        }

        /*
         * Read the latest log content.
         *
         * Same limit as index() so we don't send
         * an unnecessarily huge response.
         */
        $lines = collect(file($path))
            ->filter()
            ->reverse()
            ->take(5000)
            ->reverse()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'file' => basename($path),
            'content' => implode('', $lines),
            'size' => round(File::size($path) / 1024, 2),
            'modified_at' => date(
                'Y-m-d H:i:s',
                File::lastModified($path)
            ),
        ]);

    } catch (Throwable $e) {

        Log::error('Laravel log content API failed', [
            'user_id' => auth()->id(),
            'file' => $request->query('file'),
            'message' => $e->getMessage(),
            'file_path' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to read log file.',
            'content' => '',
        ], 500);
    }
}
    /**
     * Get all available Laravel daily log files.
     *
     * Returns newest files first.
     */
    private function getLogFiles(): array
    {
        $logDirectory = storage_path('logs');

        if (!File::isDirectory($logDirectory)) {
            return [];
        }

        return collect(File::glob($logDirectory . '/laravel-*.log'))
            ->filter(function ($path) {
                return preg_match(
                    '/^laravel-\d{4}-\d{2}-\d{2}\.log$/',
                    basename($path)
                );
            })
            ->sortByDesc(function ($path) {
                return File::lastModified($path);
            })
            ->map(function ($path) {
                $filename = basename($path);

                preg_match(
                    '/laravel-(\d{4}-\d{2}-\d{2})\.log/',
                    $filename,
                    $matches
                );

                return [
                    'name' => $filename,
                    'date' => $matches[1] ?? null,
                    'size' => round(File::size($path) / 1024, 2),
                    'modified_at' => date(
                        'Y-m-d H:i:s',
                        File::lastModified($path)
                    ),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Validate selected log filename.
     *
     * Prevents path traversal such as:
     * ../../.env
     */
    private function getSelectedLogPath(?string $filename = null): ?string
    {
        $logDirectory = storage_path('logs');

        /*
        |--------------------------------------------------------------------------
        | Default = Today's log
        |--------------------------------------------------------------------------
        */
        if (!$filename) {
            $filename = 'laravel-' . now()->format('Y-m-d') . '.log';
        }

        /*
        |--------------------------------------------------------------------------
        | Only allow Laravel daily log filenames
        |--------------------------------------------------------------------------
        */
        if (!preg_match('/^laravel-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
            return null;
        }

        $path = $logDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Display Laravel logs.
     */
    public function index(Request $request)
    {
        try {

            $files = $this->getLogFiles();

            $selectedFile = $request->query('file');

            /*
            |--------------------------------------------------------------------------
            | Get selected log
            |--------------------------------------------------------------------------
            */
            $path = $this->getSelectedLogPath($selectedFile);

            /*
            |--------------------------------------------------------------------------
            | If selected file doesn't exist,
            | fallback to latest available log
            |--------------------------------------------------------------------------
            */
            if (!$path && !empty($files)) {

                $selectedFile = $files[0]['name'];
                $path = storage_path(
                    'logs/' . $selectedFile
                );
            }

            /*
            |--------------------------------------------------------------------------
            | No log files
            |--------------------------------------------------------------------------
            */
            if (!$path || !File::exists($path)) {

                return view('admin.logs.index', [
                    'content' => '',
                    'logLines' => [],
                    'files' => $files,
                    'selectedFile' => null,
                    'selectedFileInfo' => null,
                    'error' => 'No Laravel log files found.'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Read last 5000 lines only
            |--------------------------------------------------------------------------
            */
            $lines = collect(file($path))
                ->filter()
                ->reverse()
                ->take(5000)
                ->reverse()
                ->values()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Selected file information
            |--------------------------------------------------------------------------
            */
            $selectedFileInfo = collect($files)
                ->firstWhere('name', $selectedFile);

            /*
            |--------------------------------------------------------------------------
            | If selectedFileInfo missing
            |--------------------------------------------------------------------------
            */
            if (!$selectedFileInfo) {

                $selectedFileInfo = [
                    'name' => basename($path),
                    'date' => null,
                    'size' => round(File::size($path) / 1024, 2),
                    'modified_at' => date(
                        'Y-m-d H:i:s',
                        File::lastModified($path)
                    ),
                ];
            }

            return view('admin.logs.index', [
                'content' => implode('', $lines),
                'logLines' => $lines,
                'files' => $files,
                'selectedFile' => basename($path),
                'selectedFileInfo' => $selectedFileInfo,
                'error' => null
            ]);

        } catch (Throwable $e) {

            Log::error('Laravel log viewer failed', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return view('admin.logs.index', [
                'content' => '',
                'logLines' => [],
                'files' => [],
                'selectedFile' => null,
                'selectedFileInfo' => null,
                'error' => 'Unable to load log file.'
            ]);
        }
    }

    /**
     * Clear selected Laravel log file.
     */
    public function clear(Request $request)
    {
        try {

            $filename = $request->input('file');

            $path = $this->getSelectedLogPath($filename);

            if (!$path) {

                return redirect()
                    ->route('logs.laravel.index')
                    ->with('error', 'Selected log file not found.');
            }

            File::put($path, '');

            /*
            |--------------------------------------------------------------------------
            | Do NOT use Laravel Log here.
            |
            | If we log after clearing, the clear message itself
            | will be added to the same file.
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('logs.laravel.index', [
                    'file' => basename($path)
                ])
                ->with(
                    'success',
                    'Laravel log file cleared successfully.'
                );

        } catch (Throwable $e) {

            Log::error('Laravel log clear failed', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('logs.laravel.index')
                ->with(
                    'error',
                    'Unable to clear log file.'
                );
        }
    }

    /**
     * Download selected Laravel log file.
     */
    public function download(Request $request)
    {
        try {

            $filename = $request->query('file');

            $path = $this->getSelectedLogPath($filename);

            if (!$path) {

                return redirect()
                    ->route('logs.laravel.index')
                    ->with(
                        'error',
                        'Selected Laravel log file not found.'
                    );
            }

            return response()->download(
                $path,
                'laravel-log-' .
                    pathinfo($path, PATHINFO_FILENAME) .
                    '.log'
            );

        } catch (Throwable $e) {

            Log::error('Laravel log download failed', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('logs.laravel.index')
                ->with(
                    'error',
                    'Unable to download log file.'
                );
        }
    }

    /**
     * Get selected log stats.
     */
    public function stats(Request $request)
    {
        try {

            $filename = $request->query('file');

            $path = $this->getSelectedLogPath($filename);

            if (!$path) {

                return response()->json([
                    'size' => 0,
                    'lines' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                    'exceptions' => 0,
                ]);
            }

            $content = File::get($path);

            $lines = explode("\n", $content);

            $lowerContent = strtolower($content);

            $stats = [
                'file' => basename($path),

                'size' => round(
                    File::size($path) / 1024,
                    2
                ),

                'lines' => count($lines),

                'errors' => substr_count(
                    $lowerContent,
                    'error'
                ),

                'warnings' => substr_count(
                    $lowerContent,
                    'warning'
                ),

                'exceptions' => substr_count(
                    $lowerContent,
                    'exception'
                ),
            ];

            return response()->json($stats);

        } catch (Throwable $e) {

            return response()->json([
                'error' => 'Unable to get stats'
            ], 500);
        }
    }
}