<?php

namespace App\Services;

use App\Models\TeamMember;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Composites a 1200×630 social share (OpenGraph) image for a team member —
 * their photo alongside their name and title — so link previews look polished.
 * The image is written to the public media disk and its relative path returned.
 */
class TeamOgImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private const PHOTO_WIDTH = 470;

    public function generate(TeamMember $member): ?string
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        $font = resource_path('fonts/DejaVuSans.ttf');
        if (! is_file($font)) {
            return null;
        }

        $canvas = new Imagick;
        $canvas->newImage(self::WIDTH, self::HEIGHT, new ImagickPixel('#111827'));
        $canvas->setImageFormat('jpeg');

        $this->drawPhoto($canvas, $member);
        $textLeft = self::PHOTO_WIDTH + 60;
        $textWidth = self::WIDTH - $textLeft - 60;

        $this->drawText($canvas, $font, $member->name, $textLeft, 250, $textWidth, 66, '#ffffff', true);
        $this->drawText($canvas, $font, $member->title, $textLeft, 360, $textWidth, 34, '#9ca3af', false);
        $this->drawText($canvas, $font, 'Native Dads Network', $textLeft, self::HEIGHT - 70, $textWidth, 26, '#6b7280', false);

        $path = "og/team/{$member->slug}.jpg";
        $canvas->setImageCompressionQuality(85);
        Storage::disk('public')->put($path, $canvas->getImageBlob());

        $canvas->clear();

        return $path;
    }

    public function delete(TeamMember $member): void
    {
        if ($member->og_image_path) {
            Storage::disk('public')->delete($member->og_image_path);
        }
    }

    private function drawPhoto(Imagick $canvas, TeamMember $member): void
    {
        $source = $member->photo?->getFirstMedia('original')?->getPath();

        if ($source === null || ! is_file($source)) {
            // No photo: fill the panel with a slightly lighter block + initial.
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#1f2937'));
            $draw->rectangle(0, 0, self::PHOTO_WIDTH, self::HEIGHT);
            $canvas->drawImage($draw);

            return;
        }

        $photo = new Imagick($source);
        $photo->setImageColorspace(Imagick::COLORSPACE_SRGB);
        // Cover-crop to fill the photo panel exactly.
        $photo->cropThumbnailImage(self::PHOTO_WIDTH, self::HEIGHT);
        $canvas->compositeImage($photo, Imagick::COMPOSITE_OVER, 0, 0);
        $photo->clear();
    }

    /**
     * Draws text, shrinking the font until it fits $maxWidth (single line).
     */
    private function drawText(Imagick $canvas, string $font, string $text, int $x, int $y, int $maxWidth, int $size, string $color, bool $bold): void
    {
        if ($text === '') {
            return;
        }

        $draw = new ImagickDraw;
        $draw->setFont($font);
        $draw->setFillColor(new ImagickPixel($color));
        if ($bold) {
            $draw->setTextKerning(0.5);
            $draw->setStrokeColor(new ImagickPixel($color));
            $draw->setStrokeWidth(1);
        }

        do {
            $draw->setFontSize($size);
            $metrics = $canvas->queryFontMetrics($draw, $text);
            if ($metrics['textWidth'] <= $maxWidth || $size <= 20) {
                break;
            }
            $size -= 2;
        } while (true);

        $canvas->annotateImage($draw, $x, $y, 0, $text);
        $draw->clear();
    }
}
