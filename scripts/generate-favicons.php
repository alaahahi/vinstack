<?php

/**
 * Generate favicon PNGs, favicon.ico, and OG share image from public/images/logo-day.jpg.
 * Logos are letterboxed (full width visible), not center-cropped.
 *
 * Run: php scripts/generate-favicons.php
 */

$root = dirname(__DIR__);
$source = $root . '/public/images/logo-day.jpg';

if (! is_readable($source)) {
    fwrite(STDERR, "Source logo not found: {$source}\n");
    exit(1);
}

$info = @getimagesize($source);
if ($info === false) {
    fwrite(STDERR, "Failed to read image info\n");
    exit(1);
}

echo 'Source logo: ' . ($info[0] ?? '?') . '×' . ($info[1] ?? '?') . ' (' . ($info['mime'] ?? 'unknown') . ')' . PHP_EOL;

$src = loadImage($source, $info[2] ?? 0);
if ($src === false) {
    fwrite(STDERR, "Failed to load image (mime: " . ($info['mime'] ?? 'unknown') . ")\n");
    exit(1);
}

/** @var array{0:int,1:int,2:int} $bgRgb */
$bgRgb = [255, 255, 255];
$faviconMarginRatio = 0.035;

function letterboxToCanvas($src, int $canvasW, int $canvasH, float $marginRatio, array $bgRgb): \GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);

    $dst = imagecreatetruecolor($canvasW, $canvasH);
    $bg = imagecolorallocate($dst, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
    imagefilledrectangle($dst, 0, 0, $canvasW, $canvasH, $bg);

    $padX = (int) round($canvasW * $marginRatio);
    $padY = (int) round($canvasH * $marginRatio);
    $usableW = max(1, $canvasW - (2 * $padX));
    $usableH = max(1, $canvasH - (2 * $padY));
    $scale = min($usableW / $sw, $usableH / $sh);
    $destW = max(1, (int) round($sw * $scale));
    $destH = max(1, (int) round($sh * $scale));
    $dx = (int) round(($canvasW - $destW) / 2);
    $dy = (int) round(($canvasH - $destH) / 2);

    imagecopyresampled($dst, $src, $dx, $dy, 0, 0, $destW, $destH, $sw, $sh);

    return $dst;
}

$sizes = [
    16 => $root . '/public/favicon-16x16.png',
    32 => $root . '/public/favicon-32x32.png',
    180 => $root . '/public/apple-touch-icon.png',
];

$pngs = [];
foreach ($sizes as $size => $path) {
    $img = letterboxToCanvas($src, $size, $size, $faviconMarginRatio, $bgRgb);
    imagepng($img, $path);
    imagedestroy($img);
    $pngs[$size] = $path;
    echo "Wrote {$path}\n";
}

$icoPath = $root . '/public/favicon.ico';
writeIco($icoPath, [
    file_get_contents($pngs[16]),
    file_get_contents($pngs[32]),
]);
echo "Wrote {$icoPath}\n";

$ogPath = $root . '/public/images/og-share.jpg';
$ogTagline = 'Trusted platform for vehicle trading & shipping';
$og = createOgShareImage($src, 1200, 630, $ogTagline, $bgRgb);
imagejpeg($og, $ogPath, 90);
imagedestroy($og);
echo "Wrote {$ogPath}\n";

imagedestroy($src);

function loadImage(string $path, int $type)
{
    return match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG => @imagecreatefrompng($path),
        IMAGETYPE_GIF => @imagecreatefromgif($path),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
}

function createOgShareImage($src, int $width, int $height, string $tagline, array $bgRgb): \GdImage
{
    $dst = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($dst, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
    imagefilledrectangle($dst, 0, 0, $width, $height, $bg);

    $sw = imagesx($src);
    $sh = imagesy($src);

    $font = findShareFont();
    $taglineSize = $font ? 28 : 0;
    $taglineBlock = $font ? 72 : 0;

    $logoMarginX = 0.035;
    $logoMarginY = 0.03;
    $logoMaxH = $height - $taglineBlock - (int) round($height * $logoMarginY);

    $padX = (int) round($width * $logoMarginX);
    $padY = (int) round($height * $logoMarginY);
    $usableW = max(1, $width - (2 * $padX));
    $usableH = max(1, $logoMaxH - $padY);
    $scale = min($usableW / $sw, $usableH / $sh);
    $destW = max(1, (int) round($sw * $scale));
    $destH = max(1, (int) round($sh * $scale));
    $dx = (int) round(($width - $destW) / 2);
    $dy = (int) round(($logoMaxH - $destH) / 2);

    imagecopyresampled($dst, $src, $dx, $dy, 0, 0, $destW, $destH, $sw, $sh);

    if ($font && $tagline !== '') {
        $textColor = imagecolorallocate($dst, 220, 38, 38);
        $box = imagettfbbox($taglineSize, 0, $font, $tagline);
        if ($box !== false) {
            $tx = (int) floor(($width - ($box[2] - $box[0])) / 2) - (int) $box[0];
            $ty = $height - 34 - (int) $box[1];
            imagettftext($dst, $taglineSize, 0, $tx, $ty, $textColor, $font, $tagline);
        }
    }

    return $dst;
}

function findShareFont(): ?string
{
    $candidates = [
        'C:\\Windows\\Fonts\\segoeui.ttf',
        'C:\\Windows\\Fonts\\arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    ];

    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return null;
}

function writeIco(string $path, array $pngBlobs): void
{
    $images = [];
    foreach ($pngBlobs as $blob) {
        if ($blob === false || $blob === '') {
            continue;
        }
        $images[] = $blob;
    }

    if ($images === []) {
        throw new RuntimeException('No PNG data for ICO');
    }

    $count = count($images);
    $header = pack('vvv', 0, 1, $count);
    $offset = 6 + ($count * 16);
    $entries = '';
    $data = '';

    foreach ($images as $png) {
        $w = ord($png[16]);
        $h = ord($png[21]);
        if ($w === 0) {
            $w = 256;
        }
        if ($h === 0) {
            $h = 256;
        }
        $len = strlen($png);
        $entries .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $len, $offset);
        $data .= $png;
        $offset += $len;
    }

    file_put_contents($path, $header . $entries . $data);
}
