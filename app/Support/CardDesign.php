<?php

namespace App\Support;

use App\Models\CardLayout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * ID card designs of this institute, set on the Card Designer page (Super Admin).
 *
 *   permanent  student ID card, standard PVC card CR80 (85.6 x 54 mm): photo, ID, name, branch, address, QR ...
 *   temporary  pre-printed blank temporary card, business card size (88.9 x 50.8 mm): logo, name, QR, TMP number
 *
 * Every field has a place on the card in millimetres (x, y = top left corner,
 * w, h = size) and a look (font size in mm, colour, bold, ...). A field can be
 * switched off. Nothing saved = the default design (the layout used before).
 *
 * The same rendered card is used by the web previews, the PNG download
 * (html2canvas) and the PDF (dompdf), see resources/views/id-cards/card.blade.php.
 */
class CardDesign
{
    public const PERMANENT = 'permanent';
    public const TEMPORARY = 'temporary';

    public const TYPES = [self::PERMANENT, self::TEMPORARY];

    /** Card size in mm [width, height] */
    public const SIZES = [
        self::PERMANENT => [85.6, 54.0],
        self::TEMPORARY => [88.9, 50.8],
    ];

    public const LABELS = [
        self::PERMANENT => 'Student ID card (permanent)',
        self::TEMPORARY => 'Temporary card (pre-printed)',
    ];

    /** Background used until one is uploaded (public path) */
    public const DEFAULT_BACKGROUNDS = [
        self::PERMANENT => 'storage/id/idcard_bg.png',
        self::TEMPORARY => 'storage/id/temporary.png',
    ];

    public const LOGO = 'storage/logo/logo.png';
    public const DEFAULT_PHOTO = 'storage/logo/black_logo3.png';

    /** Where uploaded backgrounds go (public disk) */
    public const BACKGROUND_DIR = 'card-designs';

    public const CACHE_KEY = 'nexora.card_design.';

    /** Images already read as data URIs in this request (a PDF has many cards) */
    private static array $embedded = [];

    /** Values a saved field may change */
    public const EDITABLE = ['on', 'x', 'y', 'w', 'h', 'size', 'color', 'bold', 'align', 'upper', 'bg', 'frame', 'radius', 'padding', 'text'];

    public const ALIGNS = ['left', 'center', 'right'];

    /* ------------------------------------------------------------------ */
    /* Fields                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Default fields of a card type.
     *
     * kind:   text | qr | photo | logo
     * source: the data key shown (text), or null = the field's own text
     * lines:  1 = single line (vertically centred), 0 = wraps
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(string $type): array
    {
        self::assertType($type);

        $text = fn (array $values) => array_merge([
            'kind' => 'text', 'source' => null, 'on' => true,
            'x' => 0, 'y' => 0, 'w' => 30, 'h' => 5,
            'size' => 3, 'color' => '#222222', 'bold' => false, 'align' => 'left', 'upper' => false,
            'bg' => '', 'frame' => '', 'radius' => 0, 'padding' => 0, 'lines' => 1, 'text' => '',
        ], $values);

        $box = fn (string $kind, array $values) => array_merge([
            'kind' => $kind, 'source' => null, 'on' => true,
            'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20,
            'size' => 0, 'color' => '', 'bold' => false, 'align' => 'left', 'upper' => false,
            'bg' => '', 'frame' => '', 'radius' => 0, 'padding' => 0, 'lines' => 1, 'text' => '',
        ], $values);

        [$first, $rest] = self::splitName(InstituteInfo::name());

        if ($type === self::PERMANENT) {
            return [
                'photo' => $box('photo', ['label' => 'Student photo', 'x' => 4.5, 'y' => 13, 'w' => 19, 'h' => 24,
                    'bg' => '#c8d4e0', 'frame' => '#1d4ed8', 'radius' => 1]),
                'student_id' => $text(['label' => 'Student ID', 'source' => 'student_id',
                    'x' => 33, 'y' => 20.5, 'w' => 24.4, 'h' => 6.5, 'size' => 5, 'color' => '#143d8d', 'bold' => true]),
                'name' => $text(['label' => 'Student name', 'source' => 'name', 'lines' => 0,
                    'x' => 33, 'y' => 28, 'w' => 24.4, 'h' => 9.5, 'size' => 3.2, 'color' => '#222222', 'bold' => true, 'upper' => true]),
                'branch' => $text(['label' => 'Branch', 'source' => 'branch',
                    'x' => 33, 'y' => 38.2, 'w' => 42, 'h' => 4, 'size' => 2.8, 'color' => '#1d4ed8', 'bold' => true, 'upper' => true]),
                'address' => $text(['label' => 'Address', 'source' => 'address', 'lines' => 0,
                    'x' => 33, 'y' => 43, 'w' => 42, 'h' => 8, 'size' => 2.2, 'color' => '#374151']),
                'grade' => $text(['label' => 'Grade', 'source' => 'grade', 'on' => false,
                    'x' => 4.5, 'y' => 38.5, 'w' => 19, 'h' => 4, 'size' => 2.6, 'color' => '#143d8d', 'bold' => true, 'align' => 'center']),
                'mobile' => $text(['label' => 'Mobile', 'source' => 'mobile', 'on' => false,
                    'x' => 4.5, 'y' => 43, 'w' => 24, 'h' => 4, 'size' => 2.4, 'color' => '#374151']),
                'qr' => $box('qr', ['label' => 'QR code', 'x' => 57.6, 'y' => 15, 'w' => 24, 'h' => 24,
                    'bg' => '#ffffff', 'frame' => '#2450d4', 'radius' => 1.2, 'padding' => 0.8]),
                'logo' => $box('logo', ['label' => 'Logo', 'on' => false, 'x' => 4, 'y' => 2.5, 'w' => 9, 'h' => 9]),
                'title' => $text(['label' => 'Institute name', 'on' => false, 'text' => mb_strtoupper(InstituteInfo::name()),
                    'x' => 15, 'y' => 3, 'w' => 66, 'h' => 5.5, 'size' => 4, 'color' => '#0f2d7a', 'bold' => true]),
                'subtitle' => $text(['label' => 'Second line', 'on' => false, 'text' => 'Student ID Card',
                    'x' => 15, 'y' => 8.3, 'w' => 66, 'h' => 3.5, 'size' => 2.6, 'color' => '#1d4ed8', 'bold' => true]),
            ];
        }

        return [
            'logo' => $box('logo', ['label' => 'Logo', 'x' => 4.3, 'y' => 11.3, 'w' => 20, 'h' => 20]),
            'title' => $text(['label' => 'Institute name', 'text' => mb_strtoupper($first),
                'x' => 23.8, 'y' => 15.8, 'w' => 36, 'h' => 6.5, 'size' => 5, 'color' => '#08348f', 'bold' => true]),
            'subtitle' => $text(['label' => 'Second line', 'text' => mb_strtoupper($rest), 'on' => $rest !== '',
                'x' => 23.8, 'y' => 22.3, 'w' => 36, 'h' => 4, 'size' => 2.8, 'color' => '#38a51e', 'bold' => true]),
            'qr' => $box('qr', ['label' => 'QR code', 'x' => 58.4, 'y' => 11, 'w' => 24.5, 'h' => 24.5, 'padding' => 1.5]),
            'code' => $text(['label' => 'TMP number', 'source' => 'code',
                'x' => 54.9, 'y' => 36.9, 'w' => 31, 'h' => 7, 'size' => 4, 'color' => '#ffffff', 'bold' => true, 'align' => 'center']),
        ];
    }

    /**
     * Saved design merged over the defaults.
     *
     * @return array{type: string, width: float, height: float, background: ?string, fields: array<string, array<string, mixed>>, saved: bool}
     */
    public static function get(string $type): array
    {
        self::assertType($type);

        $row = null;
        try {
            $row = Cache::rememberForever(self::CACHE_KEY . $type, function () use ($type) {
                if (!Schema::hasTable('card_layouts')) {
                    return null;
                }
                $layout = CardLayout::query()->where('type', $type)->first();

                return $layout ? ['fields' => $layout->fields ?? [], 'background' => $layout->background] : null;
            });
        } catch (Throwable $e) {
            $row = null;
        }

        [$width, $height] = self::SIZES[$type];

        return [
            'type' => $type,
            'width' => $width,
            'height' => $height,
            'background' => $row['background'] ?? null,
            'fields' => self::merge($type, $row['fields'] ?? []),
            'saved' => $row !== null,
        ];
    }

    /**
     * Defaults with the saved values on top; unknown fields and values are dropped
     * and numbers kept on the card.
     */
    public static function merge(string $type, array $saved): array
    {
        [$width, $height] = self::SIZES[$type];
        $fields = self::defaults($type);

        foreach ($fields as $key => $field) {
            $values = is_array($saved[$key] ?? null) ? $saved[$key] : [];

            foreach (self::EDITABLE as $name) {
                if (!array_key_exists($name, $values)) {
                    continue;
                }
                $value = $values[$name];

                switch ($name) {
                    case 'on':
                    case 'bold':
                    case 'upper':
                        $field[$name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'x':
                    case 'w':
                        $field[$name] = self::number($value, 0, $width);
                        break;
                    case 'y':
                    case 'h':
                        $field[$name] = self::number($value, 0, $height);
                        break;
                    case 'size':
                        $field[$name] = self::number($value, 0, 20);
                        break;
                    case 'radius':
                    case 'padding':
                        $field[$name] = self::number($value, 0, 10);
                        break;
                    case 'align':
                        $field[$name] = in_array($value, self::ALIGNS, true) ? $value : $field[$name];
                        break;
                    case 'color':
                    case 'bg':
                    case 'frame':
                        $field[$name] = self::color($value) ?? ($name === 'color' ? $field[$name] : '');
                        break;
                    case 'text':
                        if ($field['kind'] === 'text' && $field['source'] === null) {
                            $field[$name] = mb_substr(trim((string) $value), 0, 120);
                        }
                        break;
                }
            }

            $field['w'] = max(1, min($field['w'], $width));
            $field['h'] = max(1, min($field['h'], $height));
            $field['x'] = round(min($field['x'], $width - $field['w']), 2);
            $field['y'] = round(min($field['y'], $height - $field['h']), 2);
            $fields[$key] = $field;
        }

        return $fields;
    }

    /**
     * Save a design (only the editable values are kept).
     */
    public static function save(string $type, array $fields, ?string $background, ?int $userId = null): void
    {
        $clean = [];
        foreach (self::merge($type, $fields) as $key => $field) {
            $clean[$key] = array_intersect_key($field, array_flip(self::EDITABLE));
        }

        CardLayout::query()->updateOrCreate(
            ['type' => $type],
            ['fields' => $clean, 'background' => $background, 'updated_by' => $userId]
        );

        self::forget($type);
    }

    public static function reset(string $type): void
    {
        self::assertType($type);
        CardLayout::query()->where('type', $type)->delete();
        self::forget($type);
    }

    public static function forget(?string $type = null): void
    {
        foreach ($type ? [$type] : self::TYPES as $item) {
            Cache::forget(self::CACHE_KEY . $item);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Rendering                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Everything the card view needs for one card.
     *
     * $data keys: student_id, name, branch, address, grade, mobile, code (text),
     * qr (text in the QR) or qr_src (ready image), photo (image URL).
     * $embed: images as data URIs (PDF, no network).
     */
    public static function card(string $type, array $data, bool $embed = false, ?array $design = null): array
    {
        $design = $design ?? self::get($type);

        $items = [];
        foreach ($design['fields'] as $key => $field) {
            if (!$field['on']) {
                continue;
            }
            $items[$key] = $field + [
                'key' => $key,
                'style' => self::style($field),
                'inner' => self::innerStyle($field),
                'html' => self::content($field, $data, $embed),
            ];
        }

        return [
            'type' => $type,
            'width' => $design['width'],
            'height' => $design['height'],
            'background' => self::backgroundSrc($design, $embed),
            'fields' => $items,
        ];
    }

    /**
     * Inline CSS of a field box (absolute, mm). Kept in step with style() in
     * resources/views/card-designer/scripts.blade.php.
     */
    public static function style(array $f): string
    {
        $border = $f['frame'] !== '' ? 0.4 : 0;
        $css = [
            'position:absolute',
            'box-sizing:content-box',
            'overflow:hidden',
            'left:' . self::mm($f['x']),
            'top:' . self::mm($f['y']),
            'width:' . self::mm(max(0, $f['w'] - 2 * $border)),
            'height:' . self::mm(max(0, $f['h'] - 2 * $border)),
            'margin:0',
            'padding:0',
        ];

        if ($f['bg'] !== '') {
            $css[] = 'background:' . $f['bg'];
        }
        if ($border) {
            $css[] = 'border:' . self::mm($border) . ' solid ' . $f['frame'];
        }
        if ($f['radius'] > 0) {
            $css[] = 'border-radius:' . self::mm($f['radius']);
        }

        if ($f['kind'] === 'text') {
            $css[] = 'font-size:' . self::mm($f['size']);
            $css[] = 'color:' . $f['color'];
            $css[] = 'font-weight:' . ($f['bold'] ? '700' : '400');
            $css[] = 'text-align:' . $f['align'];
            if ($f['upper']) {
                $css[] = 'text-transform:uppercase';
            }
            if ($f['lines'] === 1) {
                $css[] = 'white-space:nowrap';
                $css[] = 'line-height:' . self::mm(max(0, $f['h'] - 2 * $border));
            } else {
                $css[] = 'line-height:1.2';
                $css[] = 'word-wrap:break-word';
            }
        }

        return implode(';', $css);
    }

    /** CSS of the image inside a QR / photo / logo box */
    public static function innerStyle(array $f): string
    {
        if ($f['kind'] === 'text') {
            return '';
        }
        $border = $f['frame'] !== '' ? 0.4 : 0;
        $pad = min($f['padding'], ($f['w'] - 2 * $border) / 2.5, ($f['h'] - 2 * $border) / 2.5);

        return implode(';', [
            'position:absolute',
            'display:block',
            'left:' . self::mm($pad),
            'top:' . self::mm($pad),
            'width:' . self::mm(max(0, $f['w'] - 2 * $border - 2 * $pad)),
            'height:' . self::mm(max(0, $f['h'] - 2 * $border - 2 * $pad)),
            'object-fit:' . ($f['kind'] === 'photo' ? 'cover' : 'contain'),
        ]);
    }

    /** Safe HTML inside a field box */
    public static function content(array $f, array $data, bool $embed): string
    {
        switch ($f['kind']) {
            case 'qr':
                $src = $data['qr_src'] ?? self::qr((string) ($data['qr'] ?? $data['code'] ?? $data['student_id'] ?? ''));

                return self::img($src, $f, 'QR');

            case 'photo':
                $src = $data['photo'] ?? null;
                $fallback = self::publicSrc(self::DEFAULT_PHOTO, $embed);
                $src = $src ?: $fallback;

                return '<img src="' . e($src) . '" style="' . self::innerStyle($f) . '" alt="Photo" crossorigin="anonymous"'
                    . ($fallback ? ' onerror="this.onerror=null;this.src=\'' . e($fallback) . '\'"' : '') . '>';

            case 'logo':
                $src = self::publicSrc(self::LOGO, $embed) ?? self::publicSrc(self::DEFAULT_PHOTO, $embed);

                return $src ? self::img($src, $f, 'Logo') : '';
        }

        $value = $f['source'] === null ? $f['text'] : (string) ($data[$f['source']] ?? '');

        if ($f['source'] === 'name') {
            [$first, $last] = self::nameLines($f['upper'] ? mb_strtoupper($value) : $value);

            return $last === '' ? e($first) : e($first) . '<br>' . e($last);
        }

        return e($value);
    }

    /** "N.M.D.P.S.SURANGIKA" -> ["N.M.D.P.S.", "SURANGIKA"]; "A B C SILVA" -> ["A B C", "SILVA"] */
    public static function nameLines(string $name): array
    {
        $name = trim($name);
        if (preg_match('/^(.+\.)\s*([^.]+)$/u', $name, $match)) {
            return [trim($match[1]), trim($match[2])];
        }
        $parts = preg_split('/\s+/u', $name) ?: [];
        if (count($parts) > 2) {
            $last = array_pop($parts);

            return [implode(' ', $parts), $last];
        }

        return [$name, ''];
    }

    /** QR code image (SVG data URI, made here: no outside QR website) */
    public static function qr(string $text): string
    {
        $svg = QrCode::format('svg')->size(300)->margin(0)->errorCorrection('M')->generate($text !== '' ? $text : '-');

        return 'data:image/svg+xml;base64,' . base64_encode((string) $svg);
    }

    /** Background image: uploaded one, else the default file */
    public static function backgroundSrc(array $design, bool $embed): ?string
    {
        if (!empty($design['background'])) {
            $src = self::publicSrc('storage/' . ltrim($design['background'], '/'), $embed);
            if ($src) {
                return $src;
            }
        }

        return self::publicSrc(self::DEFAULT_BACKGROUNDS[$design['type']], $embed);
    }

    /** URL of a file under public/ (or a data URI when embedding). Null if missing when embedding. */
    public static function publicSrc(string $path, bool $embed): ?string
    {
        if (!$embed) {
            return asset($path);
        }

        if (array_key_exists($path, self::$embedded)) {
            return self::$embedded[$path];
        }

        $file = public_path($path);
        if (!is_file($file) && str_starts_with($path, 'storage/')) {
            $file = storage_path('app/public/' . substr($path, 8));
        }
        if (!is_file($file)) {
            return self::$embedded[$path] = null;
        }

        $mime = mime_content_type($file) ?: 'image/png';

        return self::$embedded[$path] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file));
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    public static function assertType(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown card type [$type].");
        }
    }

    /** "Mihisara Education Centre" -> ["Mihisara", "Education Centre"] */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private static function img(string $src, array $f, string $alt): string
    {
        return '<img src="' . e($src) . '" style="' . self::innerStyle($f) . '" alt="' . $alt . '">';
    }

    private static function mm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . 'mm';
    }

    private static function number($value, float $min, float $max): float
    {
        $number = is_numeric($value) ? (float) $value : $min;

        return round(max($min, min($max, $number)), 2);
    }

    private static function color($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : null;
    }
}
