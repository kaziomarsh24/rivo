<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pet;
use App\Models\Store;
use App\Models\Review;
use App\Models\StoreDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use KaziOmar\SmartUpload\DTO\UploadCollection;
use KaziOmar\SmartUpload\DTO\UploadResult;
use KaziOmar\SmartUpload\Facades\Upload;
use Tests\TestCase;

class SmartUploadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_single_file_upload_with_smart_upload(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

        $result = Upload::file($file)
            ->scaleDown(400, 400)
            ->toWebp(85)
            ->save('avatars');

        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertNotNull($result->path);
        $this->assertStringStartsWith('avatars/', $result->path);
        $this->assertStringEndsWith('.webp', $result->path);
        Storage::disk('public')->assertExists($result->path);

        // Polymorphic paths() on single result (v1.0.1)
        $this->assertEquals([$result->path], $result->paths());

        // Test upload_url helper
        $url = upload_url($result->path);
        $this->assertStringContainsString('/storage/' . $result->path, $url);
    }

    public function test_multiple_file_upload_always_returns_collection_in_v1_0_1(): void
    {
        // 1. Single file uploaded through files() -> returns UploadCollection in v1.0.1
        $file1 = UploadedFile::fake()->image('doc1.png');
        $collection1 = Upload::files([$file1])->scaleDown(1200)->toWebp(85)->save('test-multiple');

        $this->assertInstanceOf(UploadCollection::class, $collection1);
        $this->assertCount(1, $collection1->paths());
        $this->assertStringEndsWith('.webp', $collection1->path); // Magic ->path getter
        Storage::disk('public')->assertExists($collection1->path);

        // 2. Multiple files uploaded through files()
        $file2 = UploadedFile::fake()->image('doc2.jpg');
        $file3 = UploadedFile::fake()->image('doc3.jpg');
        $collection2 = Upload::files([$file2, $file3])->scaleDown(1200)->toWebp(85)->save('test-multiple');

        $this->assertInstanceOf(UploadCollection::class, $collection2);
        $this->assertCount(2, $collection2->paths());
        Storage::disk('public')->assertExists($collection2->paths()[0]);
        Storage::disk('public')->assertExists($collection2->paths()[1]);
    }

    public function test_request_helper_method_in_v1_0_1(): void
    {
        // 1. Single file from Request
        $file = UploadedFile::fake()->image('profile.jpg');
        $request1 = Request::create('/test', 'POST', [], [], ['avatar' => $file]);

        $result = Upload::request($request1, 'avatar')->toWebp()->save('avatars');
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertStringEndsWith('.webp', $result->path);
        Storage::disk('public')->assertExists($result->path);

        // 2. Multiple files from Request
        $p1 = UploadedFile::fake()->image('p1.jpg');
        $p2 = UploadedFile::fake()->image('p2.jpg');
        $request2 = Request::create('/test', 'POST', [], [], ['photos' => [$p1, $p2]]);

        $photos = Upload::request($request2, 'photos')->toWebp()->save('products')->paths();
        $this->assertCount(2, $photos);
        Storage::disk('public')->assertExists($photos[0]);
        Storage::disk('public')->assertExists($photos[1]);

        // 3. Empty input from Request (zero crashes guarantee)
        $emptyRequest = Request::create('/test', 'POST');
        $emptyPaths = Upload::request($emptyRequest, 'photos')->toWebp()->save('products')->paths();
        $this->assertIsArray($emptyPaths);
        $this->assertEmpty($emptyPaths);
    }

    public function test_file_deletion_and_model_purge(): void
    {
        $file = UploadedFile::fake()->image('pet.jpg');
        $result = Upload::file($file)->toWebp()->save('pets');
        $savedPath = $result->path;

        Storage::disk('public')->assertExists($savedPath);

        // Delete file directly using Upload facade
        Upload::delete($savedPath);
        Storage::disk('public')->assertMissing($savedPath);

        // Model purging on User
        $avatarFile = UploadedFile::fake()->image('user_avatar.jpg');
        $avatarResult = Upload::file($avatarFile)->save('avatars');
        
        $user = User::factory()->create([
            'avatar' => $avatarResult->path,
        ]);
        Storage::disk('public')->assertExists($avatarResult->path);

        $user->delete();
        Storage::disk('public')->assertMissing($avatarResult->path);
    }

    public function test_smart_array_diffing_in_has_upload_purge(): void
    {
        // Test that HasUploadPurge diffs arrays and only deletes removed files!
        $fileA = UploadedFile::fake()->image('photoA.jpg');
        $fileB = UploadedFile::fake()->image('photoB.jpg');
        $fileC = UploadedFile::fake()->image('photoC.jpg');

        $pathA = Upload::file($fileA)->save('gallery')->path;
        $pathB = Upload::file($fileB)->save('gallery')->path;
        $pathC = Upload::file($fileC)->save('gallery')->path;

        Storage::disk('public')->assertExists($pathA);
        Storage::disk('public')->assertExists($pathB);
        Storage::disk('public')->assertExists($pathC);

        // Simulate model with array of photos using Review
        $user = User::factory()->create();
        $pet = Pet::create([
            'user_id' => $user->id,
            'name' => 'Charlie',
            'type' => 'dog',
            'breed' => 'Golden Retriever',
            'gender' => 'male',
            'birth_date' => '2023-01-01',
            'weight' => 15.5,
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => Pet::class,
            'reviewable_id' => $pet->id,
            'rating' => 5,
            'photos' => [$pathA, $pathB, $pathC],
        ]);

        // Remove photoB from the array
        $review->update([
            'photos' => [$pathA, $pathC],
        ]);

        // photoB should be purged, while photoA and photoC remain untouched!
        Storage::disk('public')->assertMissing($pathB);
        Storage::disk('public')->assertExists($pathA);
        Storage::disk('public')->assertExists($pathC);
    }

    public function test_upload_url_helper_null_and_external_fallbacks(): void
    {
        $this->assertNull(upload_url(null));
        $this->assertNull(upload_url(''));
        
        $externalUrl = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1';
        $this->assertEquals($externalUrl, upload_url($externalUrl));
    }

    public function test_security_hardening_in_v1_0_3(): void
    {
        // 1. Anti-path traversal guard in Upload::delete
        $this->assertFalse(Upload::delete('../secret.env'));
        $this->assertFalse(Upload::delete('avatars/../../etc/passwd'));
        $this->assertFalse(Upload::delete("avatars/image.webp\0extra"));

        // 2. Unsafe data URI restriction (only data:image/ is allowed)
        $safeDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $this->assertEquals($safeDataUri, upload_url($safeDataUri));

        $unsafeDataUri = 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==';
        $this->assertNull(upload_url($unsafeDataUri));
    }
}
