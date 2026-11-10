<?php
namespace App\Services;

/**
 * Kisimbaji kidogo cha QR kisichohitaji maktaba yoyote ya nje.
 * Kinatumika pale vendor/ (bacon/bacon-qr-code) haijasakinishwa.
 *
 * Kinaunga mkono: byte mode, kiwango cha kurekebisha makosa M, version 1 hadi 10
 * (hadi herufi 213) - zaidi ya kutosha kwa viungo vya /verify/TOKEN.
 */
class SimpleQr
{
    /** version => [ec codewords kwa block, [[idadi ya blocks, data codewords kwa block], ...]] */
    private const EC = [
        1  => [10, [[1, 16]]],
        2  => [16, [[1, 28]]],
        3  => [26, [[1, 44]]],
        4  => [18, [[2, 32]]],
        5  => [24, [[2, 43]]],
        6  => [16, [[4, 27]]],
        7  => [18, [[4, 31]]],
        8  => [22, [[2, 38], [2, 39]]],
        9  => [22, [[3, 36], [2, 37]]],
        10 => [26, [[4, 43], [1, 44]]],
    ];

    private const ALIGN = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    private static ?array $exp = null;
    private static ?array $log = null;

    public static function svg(string $content, int $size = 320): string
    {
        $matrix = self::matrix($content);
        $n      = count($matrix);
        $quiet  = 4;
        $dim    = $n + 2 * $quiet;
        $path   = '';

        for ($r = 0; $r < $n; $r++) {
            $c = 0;
            while ($c < $n) {
                if ($matrix[$r][$c] === 1) {
                    $start = $c;
                    while ($c < $n && $matrix[$r][$c] === 1) {
                        $c++;
                    }
                    $len   = $c - $start;
                    $path .= 'M' . ($start + $quiet) . ' ' . ($r + $quiet) . 'h' . $len . 'v1h-' . $len . 'z';
                } else {
                    $c++;
                }
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $size . '" height="' . $size
            . '" viewBox="0 0 ' . $dim . ' ' . $dim . '" shape-rendering="crispEdges">'
            . '<rect width="' . $dim . '" height="' . $dim . '" fill="#ffffff"/>'
            . '<path fill="#000000" d="' . $path . '"/>'
            . '</svg>';
    }

    /** Inarudisha matrix ya 0/1 (safu kwa safu), bila quiet zone. */
    public static function matrix(string $text): array
    {
        self::initGf();

        [$version, $codewords] = self::encode($text);

        $best      = null;
        $bestScore = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::build($version, $codewords, $mask);
            $score     = self::penalty($candidate);

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $best      = $candidate;
            }
        }

        return $best;
    }

    private static function initGf(): void
    {
        if (self::$exp !== null) {
            return;
        }

        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x   = 1;

        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11d;
            }
        }

        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }

        self::$exp = $exp;
        self::$log = $log;
    }

    private static function gmul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    private static function rsGenerator(int $degree): array
    {
        $g = [1];

        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($g) + 1, 0);

            foreach ($g as $j => $coef) {
                $next[$j]     ^= $coef;
                $next[$j + 1] ^= self::gmul($coef, self::$exp[$i]);
            }

            $g = $next;
        }

        return $g;
    }

    private static function rsRemainder(array $data, int $degree): array
    {
        $g   = self::rsGenerator($degree);
        $res = array_fill(0, $degree, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $res[0];
            array_shift($res);
            $res[] = 0;

            if ($factor !== 0) {
                for ($i = 0; $i < $degree; $i++) {
                    $res[$i] ^= self::gmul($g[$i + 1], $factor);
                }
            }
        }

        return $res;
    }

    private static function dataCodewords(int $version): int
    {
        $total = 0;

        foreach (self::EC[$version][1] as [$count, $size]) {
            $total += $count * $size;
        }

        return $total;
    }

    private static function putBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /** @return array{0:int,1:array<int,int>} [version, codewords zilizounganishwa] */
    private static function encode(string $text): array
    {
        $data    = $text === '' ? [] : array_values(unpack('C*', $text));
        $length  = count($data);
        $version = 0;

        for ($v = 1; $v <= 10; $v++) {
            $countBits = $v <= 9 ? 8 : 16;

            if (4 + $countBits + 8 * $length <= 8 * self::dataCodewords($v)) {
                $version = $v;
                break;
            }
        }

        if ($version === 0) {
            throw new \RuntimeException('QR content is too long.');
        }

        $countBits = $version <= 9 ? 8 : 16;
        $capacity  = 8 * self::dataCodewords($version);
        $bits      = [];

        self::putBits($bits, 4, 4);
        self::putBits($bits, $length, $countBits);

        foreach ($data as $byte) {
            self::putBits($bits, $byte, 8);
        }

        self::putBits($bits, 0, min(4, $capacity - count($bits)));

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        $bitCount  = count($bits);

        for ($i = 0; $i < $bitCount; $i += 8) {
            $value = 0;
            for ($k = 0; $k < 8; $k++) {
                $value = ($value << 1) | $bits[$i + $k];
            }
            $codewords[] = $value;
        }

        $pads = [0xEC, 0x11];
        $need = self::dataCodewords($version);
        $k    = 0;

        while (count($codewords) < $need) {
            $codewords[] = $pads[$k % 2];
            $k++;
        }

        [$ecCount, $blockSpec] = self::EC[$version];

        $dataBlocks = [];
        $ecBlocks   = [];
        $offset     = 0;

        foreach ($blockSpec as [$count, $size]) {
            for ($b = 0; $b < $count; $b++) {
                $block        = array_slice($codewords, $offset, $size);
                $offset      += $size;
                $dataBlocks[] = $block;
                $ecBlocks[]   = self::rsRemainder($block, $ecCount);
            }
        }

        $maxData = 0;
        foreach ($dataBlocks as $block) {
            $maxData = max($maxData, count($block));
        }

        $final = [];

        for ($i = 0; $i < $maxData; $i++) {
            foreach ($dataBlocks as $block) {
                if ($i < count($block)) {
                    $final[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < $ecCount; $i++) {
            foreach ($ecBlocks as $block) {
                $final[] = $block[$i];
            }
        }

        return [$version, $final];
    }

    private static function bchFormat(int $format): int
    {
        $d = $format << 10;

        for ($i = 14; $i >= 10; $i--) {
            if (($d >> $i) & 1) {
                $d ^= 0x537 << ($i - 10);
            }
        }

        return (($format << 10) | $d) ^ 0x5412;
    }

    private static function bchVersion(int $version): int
    {
        $d = $version << 12;

        for ($i = 17; $i >= 12; $i--) {
            if (($d >> $i) & 1) {
                $d ^= 0x1F25 << ($i - 12);
            }
        }

        return ($version << 12) | $d;
    }

    private static function maskApplies(int $mask, int $r, int $c): bool
    {
        switch ($mask) {
            case 0:
                return ($r + $c) % 2 === 0;
            case 1:
                return $r % 2 === 0;
            case 2:
                return $c % 3 === 0;
            case 3:
                return ($r + $c) % 3 === 0;
            case 4:
                return (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0;
            case 5:
                return ($r * $c) % 2 + ($r * $c) % 3 === 0;
            case 6:
                return (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0;
            default:
                return (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0;
        }
    }

    private static function build(int $version, array $codewords, int $mask): array
    {
        $n = 17 + 4 * $version;
        $M = array_fill(0, $n, array_fill(0, $n, 0));
        $F = array_fill(0, $n, array_fill(0, $n, false));

        $setf = function (int $r, int $c, bool $on) use (&$M, &$F): void {
            $M[$r][$c] = $on ? 1 : 0;
            $F[$r][$c] = true;
        };

        $finder = function (int $r0, int $c0) use ($n, $setf): void {
            for ($dr = -1; $dr <= 7; $dr++) {
                for ($dc = -1; $dc <= 7; $dc++) {
                    $r = $r0 + $dr;
                    $c = $c0 + $dc;

                    if ($r < 0 || $r >= $n || $c < 0 || $c >= $n) {
                        continue;
                    }

                    $on = $dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6
                        && ($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6
                            || ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4));

                    $setf($r, $c, $on);
                }
            }
        };

        $finder(0, 0);
        $finder(0, $n - 7);
        $finder($n - 7, 0);

        for ($i = 8; $i < $n - 8; $i++) {
            $setf(6, $i, $i % 2 === 0);
            $setf($i, 6, $i % 2 === 0);
        }

        $positions = self::ALIGN[$version];
        $last      = $positions === [] ? -1 : $positions[count($positions) - 1];

        foreach ($positions as $r) {
            foreach ($positions as $c) {
                if (($r === 6 && $c === 6) || ($r === 6 && $c === $last) || ($r === $last && $c === 6)) {
                    continue;
                }

                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $setf($r + $dr, $c + $dc, max(abs($dr), abs($dc)) !== 1);
                    }
                }
            }
        }

        for ($i = 0; $i < 9; $i++) {
            if (!$F[8][$i]) {
                $setf(8, $i, false);
            }
            if (!$F[$i][8]) {
                $setf($i, 8, false);
            }
        }

        for ($i = 0; $i < 8; $i++) {
            $setf(8, $n - 1 - $i, false);
            $setf($n - 1 - $i, 8, false);
        }

        $setf($n - 8, 8, true);

        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $setf($i, $n - 11 + $j, false);
                    $setf($n - 11 + $j, $i, false);
                }
            }
        }

        $bits = [];
        foreach ($codewords as $cw) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($cw >> $i) & 1;
            }
        }

        $total = count($bits);
        $index = 0;
        $col   = $n - 1;
        $up    = true;

        while ($col > 0) {
            if ($col === 6) {
                $col--;
            }

            for ($k = 0; $k < $n; $k++) {
                $r = $up ? $n - 1 - $k : $k;

                foreach ([$col, $col - 1] as $cc) {
                    if (!$F[$r][$cc]) {
                        $bit = $index < $total ? $bits[$index] : 0;
                        $index++;
                        $M[$r][$cc] = $bit ^ (self::maskApplies($mask, $r, $cc) ? 1 : 0);
                    }
                }
            }

            $up   = !$up;
            $col -= 2;
        }

        $format = self::bchFormat($mask);

        for ($i = 0; $i < 15; $i++) {
            $bit = ($format >> $i) & 1;

            if ($i < 6) {
                $M[$i][8] = $bit;
            } elseif ($i < 8) {
                $M[$i + 1][8] = $bit;
            } else {
                $M[$n - 15 + $i][8] = $bit;
            }

            if ($i < 8) {
                $M[8][$n - $i - 1] = $bit;
            } elseif ($i === 8) {
                $M[8][7] = $bit;
            } else {
                $M[8][14 - $i] = $bit;
            }
        }

        $M[$n - 8][8] = 1;

        if ($version >= 7) {
            $info = self::bchVersion($version);

            for ($i = 0; $i < 18; $i++) {
                $bit = ($info >> $i) & 1;
                $M[intdiv($i, 3)][$n - 11 + $i % 3] = $bit;
                $M[$n - 11 + $i % 3][intdiv($i, 3)] = $bit;
            }
        }

        return $M;
    }

    private static function penalty(array $M): int
    {
        $n       = count($M);
        $penalty = 0;
        $columns = [];

        for ($c = 0; $c < $n; $c++) {
            $column = [];
            for ($r = 0; $r < $n; $r++) {
                $column[] = $M[$r][$c];
            }
            $columns[] = $column;
        }

        foreach ([$M, $columns] as $lines) {
            foreach ($lines as $row) {
                $run = 1;

                for ($i = 1; $i < $n; $i++) {
                    if ($row[$i] === $row[$i - 1]) {
                        $run++;
                    } else {
                        if ($run >= 5) {
                            $penalty += $run - 2;
                        }
                        $run = 1;
                    }
                }

                if ($run >= 5) {
                    $penalty += $run - 2;
                }
            }
        }

        for ($r = 0; $r < $n - 1; $r++) {
            for ($c = 0; $c < $n - 1; $c++) {
                $v = $M[$r][$c];

                if ($v === $M[$r][$c + 1] && $v === $M[$r + 1][$c] && $v === $M[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }

        $patternA = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
        $patternB = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];

        foreach ([$M, $columns] as $lines) {
            foreach ($lines as $row) {
                for ($i = 0; $i <= $n - 11; $i++) {
                    $segment = array_slice($row, $i, 11);

                    if ($segment === $patternA || $segment === $patternB) {
                        $penalty += 40;
                    }
                }
            }
        }

        $dark = 0;
        foreach ($M as $row) {
            $dark += array_sum($row);
        }

        $penalty += intdiv(abs(intdiv($dark * 100, $n * $n) - 50), 5) * 10;

        return $penalty;
    }
}
