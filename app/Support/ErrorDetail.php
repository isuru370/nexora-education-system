<?php

namespace App\Support;

use ErrorException;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

/**
 * What an error may tell the user.
 *
 * Messages we write ourselves ("Student not found.", "You do not have access
 * to this teacher.") are shown as before. Technical errors (database, PHP
 * errors) can contain table names, SQL or file paths, so the user gets a plain
 * message and the full error goes to the log. With APP_DEBUG=true (local) the
 * real message is shown for debugging.
 */
class ErrorDetail
{
    public const FALLBACK = 'Something went wrong. Please try again.';

    public static function safe(Throwable $e, string $fallback = self::FALLBACK): string
    {
        if (!self::isTechnical($e) || config('app.debug')) {
            return $e->getMessage();
        }

        report($e);

        return $fallback;
    }

    public static function isTechnical(Throwable $e): bool
    {
        return $e instanceof QueryException
            || $e instanceof PDOException
            || $e instanceof ErrorException   // PHP warnings / notices
            || $e instanceof \Error;          // TypeError, undefined method, ...
    }
}
