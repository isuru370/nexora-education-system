<?php

namespace App\Services\System;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Gives storage/ and bootstrap/cache/ back to the web server user.
 *
 * Commands run in the server terminal run as root, so the files and folders
 * they make (caches, logs, uploads/ from a photo import...) belong to root
 * and PHP (www-data) can no longer write there: photo uploads failed with
 * "Permission denied". nexora:deploy and the import commands call this.
 */
class FolderPermissionService
{
    public const FOLDERS = ['storage', 'bootstrap/cache'];

    /**
     * @param  array<string>|null  $folders  absolute paths (default: storage, bootstrap/cache)
     * @return array{status: string, message: string}
     */
    public function fix(?array $folders = null): array
    {
        $folders ??= array_map(fn ($f) => base_path($f), self::FOLDERS);

        $user = (string) config('setup.web_user', 'www-data');

        if (!function_exists('posix_geteuid') || !function_exists('posix_getpwnam')) {
            return $this->result(DeployCheckService::WARN, 'Skipped: PHP posix is not available');
        }

        if (posix_geteuid() !== 0) {
            return $this->result(DeployCheckService::OK, 'Skipped: not run as root (files already belong to ' . $this->currentUser() . ')');
        }

        $account = posix_getpwnam($user);

        if ($account === false) {
            return $this->result(DeployCheckService::WARN, "User '{$user}' not found - set WEB_USER in .env");
        }

        $uid = (int) $account['uid'];
        $gid = (int) $account['gid'];
        $changed = 0;
        $failed = 0;

        foreach ($folders as $root) {
            if (!is_dir($root)) {
                continue;
            }

            foreach ($this->walk($root) as $path => $isDir) {
                $wrongOwner = @fileowner($path) !== $uid || @filegroup($path) !== $gid;
                $mode = @fileperms($path) & 0777;
                $wantMode = $isDir ? ($mode | 0775) : ($mode | 0664);

                if (!$wrongOwner && $mode === $wantMode) {
                    continue;
                }

                // A link (e.g. public/storage) is not followed
                if (is_link($path)) {
                    continue;
                }

                $ok = @chown($path, $uid) && @chgrp($path, $gid) && @chmod($path, $wantMode);
                $ok ? $changed++ : $failed++;
            }
        }

        clearstatcache();

        $folders = implode(', ', array_map(fn ($f) => ltrim(str_replace(base_path(), '', $f), '/'), $folders));

        if ($failed > 0) {
            return $this->result(DeployCheckService::WARN, "{$failed} could not be changed - run: chown -R {$user}:{$user} {$folders}");
        }

        return $this->result(DeployCheckService::OK, $changed > 0
            ? "{$folders} -> {$user} ({$changed} fixed)"
            : "{$folders} -> {$user}");
    }

    /** The folder itself and everything in it: path => is folder. */
    protected function walk(string $root): \Generator
    {
        yield $root => true;

        try {
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($items as $item) {
                yield $item->getPathname() => $item->isDir() && !$item->isLink();
            }
        } catch (Throwable) {
            // unreadable folder: the counts show it
        }
    }

    protected function currentUser(): string
    {
        $info = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid()) : false;

        return $info['name'] ?? 'this user';
    }

    protected function result(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
