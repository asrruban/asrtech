<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductReleaseFileService
{
    private const DISK = 'local';

    /** @return array{disk: string, file_path: string, original_filename: string, mime_type: string|null, file_size: int, checksum_sha256: string} */
    public function store(Product $product, UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $checksum = is_string($realPath) ? hash_file('sha256', $realPath) : false;

        if (! is_string($checksum)) {
            throw new RuntimeException('Unable to calculate the release checksum.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $filename = (string) Str::uuid().($extension !== '' ? ".{$extension}" : '');
        $path = Storage::disk(self::DISK)->putFileAs(
            "product-releases/{$product->id}",
            $file,
            $filename,
        );

        if (! is_string($path)) {
            throw new RuntimeException('Unable to store the release package.');
        }

        return [
            'disk' => self::DISK,
            'file_path' => $path,
            'original_filename' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType() ?: null,
            'file_size' => max(0, (int) $file->getSize()),
            'checksum_sha256' => $checksum,
        ];
    }

    public function delete(ProductRelease $release): void
    {
        Storage::disk($release->disk)->delete($release->file_path);
    }

    /** @param array{disk: string, file_path: string} $storedFile */
    public function deleteStoredFile(array $storedFile): void
    {
        Storage::disk($storedFile['disk'])->delete($storedFile['file_path']);
    }
}
