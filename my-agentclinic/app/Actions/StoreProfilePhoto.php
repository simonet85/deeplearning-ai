<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores an uploaded profile photo on the private photo disk. A photo that is already small is kept exactly as
 * uploaded; a large one (heavy file or big dimensions) is scaled down and converted to WebP so the clinic does not
 * store or send multi-megabyte avatars.
 */
class StoreProfilePhoto
{
    /** A file above this many bytes is compressed. */
    public const MAX_BYTES = 512 * 1024;

    /** An image wider or taller than this many pixels is scaled down to fit. */
    public const MAX_SIDE = 800;

    /** WebP quality, from 1 to 100. */
    public const QUALITY = 80;

    /** @return string the path of the stored photo on the photo disk */
    public function handle(UploadedFile $file): string
    {
        if (! $this->isTooLarge($file)) {
            return $file->store('', User::PHOTO_DISK);
        }

        $encoded = Image::decodePath($file->getRealPath())
            ->scaleDown(self::MAX_SIDE, self::MAX_SIDE)
            ->encodeUsingFormat(Format::WEBP, quality: self::QUALITY);

        $path = Str::random(40).'.webp';

        Storage::disk(User::PHOTO_DISK)->put($path, (string) $encoded);

        return $path;
    }

    private function isTooLarge(UploadedFile $file): bool
    {
        [$width, $height] = getimagesize($file->getRealPath());

        return $file->getSize() > self::MAX_BYTES || max($width, $height) > self::MAX_SIDE;
    }
}
