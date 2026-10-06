<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WebpImages
{
    public function store(UploadedFile $file, string $directory): string
    {
        $source = $file->getRealPath();
        if ($source === false || ! is_file($source)) {
            throw new RuntimeException('Не удалось прочитать загруженное изображение.');
        }

        $bytes = $this->encode($source);
        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, $bytes)) {
            throw new RuntimeException('Не удалось сохранить изображение WebP.');
        }

        return 'storage/'.$path;
    }

    public function encode(string $source): string
    {
        $mime = mime_content_type($source);
        if ($mime === 'image/webp') {
            return file_get_contents($source) ?: throw new RuntimeException('Пустое изображение WebP.');
        }

        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('Поддерживаются только JPEG, PNG и WebP.');
        }

        if (extension_loaded('imagick') && \Imagick::queryFormats('WEBP')) {
            $image = new \Imagick($source);
            if (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(82);
            $result = $image->getImagesBlob();
            $image->clear();
            return $result;
        }

        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromjpeg')) {
            throw new RuntimeException('На сервере отсутствует поддержка WebP (GD или Imagick).');
        }

        $image = $mime === 'image/jpeg' ? @imagecreatefromjpeg($source) : @imagecreatefrompng($source);
        if ($image === false) {
            throw new RuntimeException('Не удалось декодировать изображение.');
        }

        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        } elseif (function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;
            if (in_array($orientation, [2, 5, 7], true)) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            } elseif ($orientation === 4) {
                imageflip($image, IMG_FLIP_VERTICAL);
            }
            $rotated = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                5, 6 => imagerotate($image, -90, 0),
                7, 8 => imagerotate($image, 90, 0),
                default => false,
            };
            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        ob_start();
        $ok = imagewebp($image, null, 82);
        $result = ob_get_clean();
        imagedestroy($image);

        if (! $ok || ! is_string($result) || $result === '') {
            throw new RuntimeException('Не удалось преобразовать изображение в WebP.');
        }

        return $result;
    }
}
