<?php

namespace App\Http\Controllers\Web\Setting;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use ZipArchive;

class DatabaseBackupController extends Controller
{
    private const MAX_BACKUP_SIZE_MB = 200;

    public function index()
    {
        $logs = BackupLog::latest()->paginate(20);

        return view('setting.index', compact('logs'));
    }

    public function export(Request $request)
    {
        $log = BackupLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'export',
            'status'     => 'pending',
            'message'    => 'Database backup export started.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        $sqlFile = null;

        try {
            $connection = config('database.default');
            $db = config("database.connections.{$connection}");

            $host = $db['host'] ?? '127.0.0.1';
            $port = $db['port'] ?? 3306;
            $database = $db['database'];
            $username = $db['username'];
            $password = $db['password'] ?? '';

            $tempDir = storage_path('app/backup-temp');

            File::ensureDirectoryExists($tempDir);

            $appName = config('app.name');

            $fileName = str_replace(' ', '_', $appName)
                . '_backup_' . now()->format('Y_m_d_H_i_s');

            $sqlFile = $tempDir . DIRECTORY_SEPARATOR
                . $fileName . '.sql';

            $zipFile = $tempDir . DIRECTORY_SEPARATOR
                . $fileName . '.zip';

            $mysqldump = $this->getDatabaseClientPath(
                'MYSQLDUMP_PATH',
                'C:/wamp64/bin/mysql/mysql9.1.0/bin/mysqldump.exe',
                'mysqldump'
            );

            // Use argument-based execution instead of shell redirection.

            $process = new Process([
                $mysqldump,
                '--host=127.0.0.1',
                "--port={$port}",
                "--user={$username}",
                "--result-file={$sqlFile}",
                $database,
            ], null, [
                'MYSQL_PWD' => $password,
            ]);

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(
                    'mysqldump failed: '
                        . ($process->getErrorOutput()
                            ?: $process->getExitCodeText())
                );
            }

            if (!is_file($sqlFile) || filesize($sqlFile) === 0) {
                throw new \RuntimeException(
                    'Database dump was not created or is empty.'
                );
            }

            $zip = new ZipArchive();

            if ($zip->open(
                $zipFile,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            ) !== true) {
                throw new \RuntimeException(
                    'Unable to create ZIP backup file.'
                );
            }

            if (!$zip->addFile($sqlFile, basename($sqlFile))) {
                $zip->close();

                throw new \RuntimeException(
                    'Unable to add SQL file to ZIP backup.'
                );
            }

            $zip->close();

            File::delete($sqlFile);
            $sqlFile = null;

            $log->update([
                'status'       => 'success',
                'file_name'    => basename($zipFile),
                'message'      => 'Database backup exported successfully.',
                'completed_at' => now(),
            ]);

            return response()
                ->download($zipFile, basename($zipFile))
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Backup Export Failed', [
                'message' => $e->getMessage(),
            ]);

            if ($sqlFile && is_file($sqlFile)) {
                File::delete($sqlFile);
            }

            $log->update([
                'status'       => 'failed',
                'message'      => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return back()->with(
                'error',
                'Database export failed.'
            );
        }
    }

    public function import(Request $request)
    {
        $maxKilobytes = self::MAX_BACKUP_SIZE_MB * 1024;

        $request->validate([
            'backup_file' => [
                'required',
                'file',
                'mimes:zip',
                "max:{$maxKilobytes}",
            ],
        ]);

        $log = BackupLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'import',
            'status'     => 'pending',
            'message'    => 'Database backup import started.',
            'file_name'  => $request->file('backup_file')
                ->getClientOriginalName(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        $tempDir = storage_path(
            'app/backup-temp/import-'
                . now()->format('YmdHis')
                . '-'
                . uniqid()
        );

        File::ensureDirectoryExists($tempDir);

        try {
            $zipPath = $request->file('backup_file')->getRealPath();

            $zip = new ZipArchive();

            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException(
                    'Unable to open backup ZIP file.'
                );
            }

            // Validate ZIP entries before extraction.
            $basePath = realpath($tempDir);

            if ($basePath === false) {
                $zip->close();

                throw new \RuntimeException(
                    'Unable to resolve backup temporary directory.'
                );
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);

                if ($entryName === false || str_contains($entryName, "\0")) {
                    $zip->close();

                    throw new \RuntimeException(
                        'Invalid ZIP entry detected.'
                    );
                }

                $normalized = str_replace('\\', '/', $entryName);

                if (
                    str_starts_with($normalized, '/')
                    || preg_match('/^[A-Za-z]:/', $normalized)
                ) {
                    $zip->close();

                    throw new \RuntimeException(
                        'Invalid ZIP entry path detected.'
                    );
                }

                $segments = explode('/', $normalized);

                if (in_array('..', $segments, true)) {
                    $zip->close();

                    throw new \RuntimeException(
                        'Malicious ZIP entry detected.'
                    );
                }
            }

            if (!$zip->extractTo($tempDir)) {
                $zip->close();

                throw new \RuntimeException(
                    'Unable to extract backup ZIP file.'
                );
            }

            $zip->close();

            $sqlFile = $this->findFirstSqlFile($tempDir);

            if (!$sqlFile) {
                throw new \RuntimeException(
                    'SQL dump not found inside backup ZIP.'
                );
            }

            if (!$this->isValidSqlFile($sqlFile)) {
                throw new \RuntimeException(
                    'SQL file content validation failed.'
                );
            }

            $connection = config('database.default');
            $db = config("database.connections.{$connection}");

            $host     = $db['host'] ?? '127.0.0.1';
            $port     = $db['port'] ?? 3306;
            $database = $db['database'] ?? '';
            $username = $db['username'] ?? '';
            $password = $db['password'] ?? '';

            if ($database === '' || $username === '') {
                throw new \RuntimeException(
                    'Database configuration is incomplete.'
                );
            }

            $mysql = $this->getDatabaseClientPath(
                'MYSQL_CLIENT_PATH',
                'C:/wamp64/bin/mysql/mysql9.1.0/bin/mysql.exe',
                'mysql'
            );

            $inputStream = fopen($sqlFile, 'rb');

            if ($inputStream === false) {
                throw new \RuntimeException(
                    'Unable to read SQL backup file.'
                );
            }

            try {
                // Pass SQL through stdin instead of shell redirection.
                $process = new Process([
                    $mysql,
                    "--host={$host}",
                    "--port={$port}",
                    "--user={$username}",
                    $database,
                ], null, [
                    'MYSQL_PWD' => $password,
                ]);

                $process->setInput($inputStream);
                $process->setTimeout(300);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException(
                        'Database import failed: '
                            . ($process->getErrorOutput()
                                ?: $process->getExitCodeText())
                    );
                }
            } finally {
                if (is_resource($inputStream)) {
                    fclose($inputStream);
                }
            }

            $log->update([
                'status'       => 'success',
                'message'      => 'Database backup imported successfully.',
                'completed_at' => now(),
            ]);

            return back()->with(
                'success',
                'Database imported successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Backup Import Failed', [
                'message' => $e->getMessage(),
            ]);

            $log->update([
                'status'       => 'failed',
                'message'      => 'Import failed: ' . $e->getMessage(),
                'completed_at' => now(),
            ]);

            return back()->with(
                'error',
                'Database import failed.'
            );
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Resolve MySQL client executable for Windows or Linux.
     */
    private function getDatabaseClientPath(
        string $environmentKey,
        string $windowsDefault,
        string $linuxDefault
    ): string {
        $configuredPath = config(
            'database_backup.' . strtolower($environmentKey)
        );

        $path = is_string($configuredPath)
            && trim($configuredPath) !== ''
            ? trim($configuredPath)
            : (
                PHP_OS_FAMILY === 'Windows'
                ? $windowsDefault
                : $linuxDefault
            );

        // Validate explicitly specified or absolute paths.
        if (
            (str_contains($path, '/') || str_contains($path, '\\'))
            && !is_file($path)
        ) {
            throw new \RuntimeException(
                "Database client executable not found: {$path}"
            );
        }

        return $path;
    }

    private function findFirstSqlFile(string $directory): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (
                $file->isFile()
                && strtolower($file->getExtension()) === 'sql'
            ) {
                return $file->getRealPath() ?: null;
            }
        }

        return null;
    }

    private function isValidSqlFile(string $filePath): bool
    {
        if (!is_readable($filePath)) {
            return false;
        }

        $size = filesize($filePath);

        if ($size === false || $size === 0) {
            return false;
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $sample = fread($handle, 512);
        } finally {
            fclose($handle);
        }

        if ($sample === false || str_contains($sample, "\x00")) {
            return false;
        }

        return true;
    }
}
