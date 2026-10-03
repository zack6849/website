<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\FileCannotBeDeletedException;
use App\Jobs\Files\PurgeCDNCacheJob;
use App\Models\File;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\ServiceTest;
use Throwable;

class FileUploadServiceTest extends TestCase
{
    use ServiceTest;
    public FileUploadService $service;
    public Filesystem $disk;

    public function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake(config('upload.storage.disk'));
        $this->setService(FileUploadService::class);
    }

    #[Test]
    public function randomizesFilenames(): void
    {
        $file = UploadedFile::fake()->image('filename.jpg');
        $this->service = resolve(FileUploadService::class);
        $result = $this->service->getFilename($file);
        $this->assertNotEquals($file->getClientOriginalName(), $result);
        //assert filename contains a uuid
        $parts = explode("_", basename($result));
        $this->assertTrue(Str::isUuid($parts[0]));
        //assert filename contains the original file extension
        $this->assertStringEndsWith($file->getClientOriginalExtension(), $parts[1]);
        $randomString = str_replace("." . $file->getClientOriginalExtension(), "", $parts[1]);
        //assert filename contains 12 random characters
        $this->assertEquals(12, strlen($randomString));
    }

    #[Test]
    public function generatedFilenamesNormalizeTheOriginalExtension(): void
    {
        $file = UploadedFile::fake()->image('filename.JPG');
        $result = $this->service->getFilename($file);

        $this->assertStringEndsWith('.jpg', $result);
    }

    #[Test]
    public function createsFileOnDisk(): void
    {
        $file = UploadedFile::fake()->image('filename.jpg');
        $user = User::factory()->create();
        $this->assertEmpty($user->files);
        $this->reloadService();
        $createdFile = $this->service->storeUploadedFile($file, $user);
        $this->disk->assertExists($createdFile->file_location);
        $this->assertNotEmpty($user->refresh()->files);
    }

    #[Test]
    public function deletesStoredUploadWhenDatabaseSaveFails(): void
    {
        try {
            $this->service->storeUploadedFile(UploadedFile::fake()->image('filename.jpg'), new User());
            $this->fail('Expected the database save to fail for an unsaved user.');
        } catch (Throwable) {
            $this->assertSame([], $this->disk->allFiles(config('upload.storage.path')));
        }
    }

    #[Test]
    public function deleteThrowsExceptionWhenDeleteFails(): void
    {
        $file = File::factory()->create();
        //create a fake filesystem
        $mockDisk = $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->shouldReceive('delete')->andReturn(false);
        });
        //mock that storage disk('spaces') should receive our mock instead
        Storage::shouldReceive('disk')->with(config('upload.storage.disk'))
            ->andReturn($mockDisk);
        //reload the service so it has the mock instance now
        $this->reloadService();
        $this->expectException(FileCannotBeDeletedException::class);
        $this->service->delete($file);
    }

    #[Test]
    public function queuesJobToPurgeCDN(): void
    {
        $file =  File::factory()->create();
        Queue::fake([
            PurgeCDNCacheJob::class
        ]);
        $this->service->delete($file);
        Queue::assertPushed(PurgeCDNCacheJob::class);
    }
}
