<?php

declare(strict_types=1);

namespace App\Services\Backgrounds;

use App\Models\Background;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BackgroundImageStorage
{
    private const string DISK = 'public';
    private const string DIRECTORY = 'backgrounds';
    private const string PUBLIC_PREFIX = 'storage';
    private const string ALLOWED_EXTENSIONS = 'jpg,jpeg,png,gif,webp';
    private const int MAX_KILOBYTES = 8192;

    /**
     * @return array<int, string>
     */
    public static function uploadRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:' . self::ALLOWED_EXTENSIONS,
            'extensions:' . self::ALLOWED_EXTENSIONS,
            'max:' . self::MAX_KILOBYTES,
        ];
    }

    public function store(mixed $upload): string
    {
        if (! $upload instanceof UploadedFile) {
            throw new InvalidArgumentException('Background uploads must be storable uploaded files.');
        }

        Validator::make(
            ['upload' => $upload],
            ['upload' => self::uploadRules(required: true)],
        )->validate();

        return $this->publicPath($upload->store(self::DIRECTORY, self::DISK));
    }

    /**
     * @return array<int, string>
     */
    public function managedPublicPathsFor(Background $background): array
    {
        return array_values(array_unique(array_filter(
            array_merge([$background->image], $this->variantImages($background->variants)),
            fn (?string $path): bool => is_string($path) && $this->diskPathFor($path) !== null,
        )));
    }

    public function deleteIfUnreferenced(string $publicPath): void
    {
        $this->deleteUnreferenced([$publicPath]);
    }

    /**
     * Delete every given public path that is no longer referenced by any
     * background row. The set of currently-referenced paths is loaded once
     * up front instead of re-querying it for every path being checked.
     *
     * @param  array<int, string>  $publicPaths
     */
    public function deleteUnreferenced(array $publicPaths): void
    {
        if ($publicPaths === []) {
            return;
        }

        $referenced = $this->allReferencedPaths();
        $disk = Storage::disk(self::DISK);

        foreach ($publicPaths as $publicPath) {
            $diskPath = $this->diskPathFor($publicPath);

            if ($diskPath === null || in_array($publicPath, $referenced, true)) {
                continue;
            }

            if (! $disk->exists($diskPath)) {
                continue;
            }

            if (! $disk->delete($diskPath)) {
                throw new RuntimeException("Unable to delete background image [{$diskPath}].");
            }
        }
    }

    public function diskPathFor(?string $publicPath): ?string
    {
        if (! is_string($publicPath) || trim($publicPath) === '') {
            return null;
        }

        $publicPath = ltrim(trim($publicPath), '/');

        if (Str::startsWith($publicPath, ['http://', 'https://', '//'])) {
            return null;
        }

        $publicPrefix = trim(self::PUBLIC_PREFIX, '/');
        $directory = trim(self::DIRECTORY, '/');
        $managedPrefix = $publicPrefix . '/' . $directory . '/';

        if (! Str::startsWith($publicPath, $managedPrefix)) {
            return null;
        }

        $diskPath = Str::after($publicPath, $publicPrefix . '/');

        if ($diskPath === '' || str_contains($diskPath, '..') || Str::startsWith($diskPath, '/')) {
            return null;
        }

        return Str::startsWith($diskPath, $directory . '/') ? $diskPath : null;
    }

    private function publicPath(string $diskPath): string
    {
        return trim(self::PUBLIC_PREFIX, '/') . '/' . ltrim($diskPath, '/');
    }

    /**
     * @return array<int, string>
     */
    private function allReferencedPaths(): array
    {
        $paths = [];

        Background::query()->get(['image', 'variants'])->each(function (Background $background) use (&$paths): void {
            if (is_string($background->image)) {
                $paths[] = $background->image;
            }

            array_push($paths, ...$this->variantImages($background->variants));
        });

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    private function variantImages(mixed $variants): array
    {
        if (! is_array($variants)) {
            return [];
        }

        $images = [];

        foreach ($variants as $variant) {
            if (is_array($variant) && is_string($variant['image'] ?? null)) {
                $images[] = $variant['image'];
            }
        }

        return $images;
    }
}
