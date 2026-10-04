<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FileCannotBeDeletedException;
use App\Jobs\Files\PurgeCDNCacheJob;
use App\Models\File;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FileUploadService
{

    private Filesystem $disk;

    public function __construct(
        public CDNService $cdnService
    )
    {
        $this->disk = Storage::disk(config('upload.storage.disk'));
    }

    public function getFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';

        return implode('_', [
                Str::orderedUuid(),
                Str::random(12),
            ]) . "." . $extension;
    }

    /**
     * @throws Throwable
     */
    public function storeUploadedFile(UploadedFile $file, User $user): File
    {
        $storagePath = config('upload.storage.path');
        $name = $this->getFilename($file);
        $path = $this->disk->putFileAs($storagePath, $file, $name, [
            'visibility' => 'public'
        ]);
        $uploadedFile = new File([
            'file_location' => $path,
            'filename' => $name,
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        try {
            $user->files()->save($uploadedFile);
        } catch (Throwable $exception) {
            $this->disk->delete($path);

            throw $exception;
        }

        return $uploadedFile;
    }

    /**
     * Deletes a file from the disk, database, and purges it from the CDN
     * @throws FileCannotBeDeletedException
     */
    public function delete(File $file): void
    {
        $result = $this->disk->delete($file->file_location);
        if (!$result) {
            throw new FileCannotBeDeletedException("Failed to delete the file from disk");
        }
        $slug = $file->file_location;
        $file->delete();
        dispatch(new PurgeCDNCacheJob($slug));
    }
}
