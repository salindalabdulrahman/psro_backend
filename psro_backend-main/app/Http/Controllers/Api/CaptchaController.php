<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    /**
     * Issue a one-time numeric captcha (10 min TTL). Image is PNG (data URI) or SVG fallback.
     */
    public function show(): JsonResponse
    {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= (string) random_int(0, 9);
        }

        $id = (string) Str::uuid();
        Cache::put($this->cacheKey($id), $code, now()->addMinutes(10));

        $image = $this->renderCaptchaImage($code);

        return response()
            ->json([
                'captcha_id' => $id,
                'image' => $image,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('CDN-Cache-Control', 'no-store');
    }

    public static function cacheKey(string $id): string
    {
        return 'captcha:'.$id;
    }

    private function renderCaptchaImage(string $code): string
    {
        if (function_exists('imagecreatetruecolor')) {
            return $this->renderPng($code);
        }

        return $this->renderSvg($code);
    }

    private function renderPng(string $code): string
    {
        $width = 260;
        $height = 40;
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $gray = imagecolorallocate($im, 64, 64, 64);
        $line = imagecolorallocate($im, 200, 200, 210);
        imagefill($im, 0, 0, $white);

        for ($n = 0; $n < 5; $n++) {
            imageline($im, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $line);
        }

        $digits = str_split($code);
        $x = 14;
        foreach ($digits as $ch) {
            imagestring($im, 5, $x + random_int(-2, 2), 11 + random_int(-2, 2), $ch, $gray);
            $x += 36;
        }

        ob_start();
        imagepng($im);
        imagedestroy($im);
        $raw = ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($raw);
    }

    private function renderSvg(string $code): string
    {
        $chars = str_split($code);
        $elements = '';
        foreach ($chars as $i => $ch) {
            $x = 14 + $i * 36 + random_int(-2, 2);
            $y = 26 + random_int(-2, 2);
            $elements .= sprintf(
                '<text x="%d" y="%d" font-family="ui-monospace,monospace" font-size="18" font-weight="600" fill="#404040">%s</text>',
                $x,
                $y,
                htmlspecialchars($ch, ENT_QUOTES | ENT_XML1)
            );
        }

        $lines = '';
        for ($n = 0; $n < 5; $n++) {
            $lines .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="rgba(148,163,184,0.45)" stroke-width="0.75"/>',
                random_int(0, 80),
                random_int(0, 40),
                random_int(180, 260),
                random_int(0, 40)
            );
        }

        $svg = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="260" height="40" viewBox="0 0 260 40">'
            .'<rect width="260" height="40" fill="#ffffff"/>'
            .$lines
            .$elements
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
