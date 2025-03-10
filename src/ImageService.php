<?php
declare(strict_types=1);

namespace Flynn314\Service;

use Imagine\Image\Box;
use Imagine\Image\Point;

final class ImageService
{
    public function resizeImage(string $url, int $maxWidth, int $maxHeight, string $format = 'jpeg', int $quality = 90): string
    {
        $imagine = new \Imagine\Gd\Imagine();
        // $imagine = new \Imagine\Imagick\Imagine();
        // $imagine = new \Imagine\Gmagick\Imagine();
        $img = $imagine->open($url);

        $exifData = @exif_read_data($url);
        if ($exifData && isset($exifData['Orientation'])) {
            switch ($exifData['Orientation']) {
                case 3:
                    $img->rotate(180);
                    break;
                case 6:
                    $img->rotate(90);
                    break;
                case 8:
                    $img->rotate(-90);
                    break;
            }
        }

        return $img->thumbnail($this->calculateMaxSizes($img->getSize()->getWidth(), $img->getSize()->getHeight(), $maxWidth, $maxHeight))->get($format, ['quality' => $quality]);
    }

    public function resizeImageAsBinary(string $binary, int $maxWidth, int $maxHeight, string $format = 'jpeg', int $quality = 90, int $blur = 0): string
    {
        $imagine = new \Imagine\Gd\Imagine();
        // $imagine = new \Imagine\Imagick\Imagine();
        // $imagine = new \Imagine\Gmagick\Imagine();
        $img = $imagine->load($binary);

        $tmpFileaname = storage_path('tmp_' . time());
        file_put_contents($tmpFileaname, $binary);
        if (file_exists($tmpFileaname)) {
            $exifData = @exif_read_data($tmpFileaname);
            unlink($tmpFileaname);
            if ($exifData && isset($exifData['Orientation'])) {
                switch ($exifData['Orientation']) {
                    case 3:
                        $img->rotate(180);
                        break;
                    case 6:
                        $img->rotate(90);
                        break;
                    case 8:
                        $img->rotate(-90);
                        break;
                }
            }
        }

        $resizedImage = $img->thumbnail($this->calculateMaxSizes($img->getSize()->getWidth(), $img->getSize()->getHeight(), $maxWidth, $maxHeight));
        if ($blur) {
            $resizedImage->effects()->blur($blur);
        }

        return $resizedImage->get($format, ['quality' => $quality]);
    }

    public function squareImageAsBinary(string $binary, int $targetSize, string $format = 'jpeg', int $quality = 90, int $blur = 0): string
    {
        $imagine = new \Imagine\Gd\Imagine();
        // $imagine = new \Imagine\Imagick\Imagine();
        // $imagine = new \Imagine\Gmagick\Imagine();
        $img = $imagine->load($binary);

        $tmpFileaname = storage_path('tmp_' . time());
        file_put_contents($tmpFileaname, $binary);
        $exifData = @exif_read_data($tmpFileaname);
        if (file_exists($tmpFileaname)) {
            unlink($tmpFileaname);
        }
        if ($exifData && isset($exifData['Orientation'])) {
            switch ($exifData['Orientation']) {
                case 3:
                    $img->rotate(180);
                    break;
                case 6:
                    $img->rotate(90);
                    break;
                case 8:
                    $img->rotate(-90);
                    break;
            }
        }

        $size = $img->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();

        $side = min($width, $height); // square size
        $x = ($width - $side) / 2; // starting point of horizontal cut
        $y = ($height - $side) / 2; // starting point of vertical cut

        $cropPoint = new Point($x, $y);
        $cropBox = new Box($side, $side);

        return $img->crop($cropPoint, $cropBox)->resize(new Box($targetSize, $targetSize))->get($format, ['quality' => $quality]);
    }

    private function calculateMaxSizes($originalWidth, $originalHeight, $maxWidth = 512, $maxHeight = 512): Box
    {
        // Calculate aspect ratio
        $ratio = $originalWidth / $originalHeight;

        // Initialize new width and height
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        // Adjust width and height based on aspect ratio
        if ($newWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = $newWidth / $ratio;
        }

        if ($newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = $newHeight * $ratio;
        }

        return new Box($newWidth, $newHeight);
    }
}
