<?php

namespace Tests\Feature;

use App\Actions\StoreProfilePhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreProfilePhotoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::PHOTO_DISK);
    }

    private function store(UploadedFile $file): string
    {
        return app(StoreProfilePhoto::class)->handle($file);
    }

    private function dimensionsOf(string $path): array
    {
        [$width, $height, $type] = getimagesizefromstring(Storage::disk(User::PHOTO_DISK)->get($path));

        return [$width, $height, $type];
    }

    public function test_a_small_photo_is_kept_exactly_as_uploaded(): void
    {
        $file = UploadedFile::fake()->image('small.png', 200, 200);
        $original = file_get_contents($file->getRealPath());

        $path = $this->store($file);

        $this->assertStringEndsWith('.png', $path);
        $this->assertSame($original, Storage::disk(User::PHOTO_DISK)->get($path));
    }

    public function test_a_photo_at_the_dimension_limit_is_not_touched(): void
    {
        $path = $this->store(UploadedFile::fake()->image('edge.jpg', StoreProfilePhoto::MAX_SIDE, 300));

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame(StoreProfilePhoto::MAX_SIDE, $this->dimensionsOf($path)[0]);
    }

    public function test_a_photo_with_big_dimensions_is_scaled_down_keeping_its_ratio(): void
    {
        $path = $this->store(UploadedFile::fake()->image('wide.png', 1600, 900));

        [$width, $height, $type] = $this->dimensionsOf($path);

        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(800, $width);
        $this->assertSame(450, $height);
    }

    public function test_a_tall_photo_is_limited_by_its_height(): void
    {
        [$width, $height] = $this->dimensionsOf($this->store(UploadedFile::fake()->image('tall.jpg', 768, 1364)));

        $this->assertSame(800, $height);
        $this->assertLessThan(800, $width);
    }

    public function test_a_heavy_file_is_compressed_even_when_its_dimensions_are_small(): void
    {
        // The reported size is what counts, so a 600 KB file is over the limit however small the pixels are.
        $path = $this->store(UploadedFile::fake()->image('heavy.jpg', 300, 300)->size(600));

        [$width, $height, $type] = $this->dimensionsOf($path);

        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame([300, 300], [$width, $height]);
        $this->assertStringEndsWith('.webp', $path);
    }

    public function test_a_photo_is_never_enlarged(): void
    {
        [$width, $height] = $this->dimensionsOf($this->store(UploadedFile::fake()->image('tiny.png', 100, 100)->size(600)));

        $this->assertSame([100, 100], [$width, $height]);
    }

    public function test_the_compressed_file_is_smaller_than_a_noisy_original(): void
    {
        // A photo-like image: random noise cannot be compressed losslessly, so PNG is large and WebP is smaller.
        $image = imagecreatetruecolor(1200, 1200);
        mt_srand(7);
        for ($x = 0; $x < 1200; $x += 2) {
            for ($y = 0; $y < 1200; $y += 2) {
                imagefilledrectangle($image, $x, $y, $x + 1, $y + 1, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            }
        }
        $png = tempnam(sys_get_temp_dir(), 'noise').'.png';
        imagepng($image, $png);
        $originalBytes = filesize($png);

        $path = $this->store(new UploadedFile($png, 'noise.png', 'image/png', null, true));

        $this->assertGreaterThan(StoreProfilePhoto::MAX_BYTES, $originalBytes);
        $this->assertLessThan($originalBytes, Storage::disk(User::PHOTO_DISK)->size($path));
        $this->assertSame(800, $this->dimensionsOf($path)[0]);

        unlink($png);
    }
}
