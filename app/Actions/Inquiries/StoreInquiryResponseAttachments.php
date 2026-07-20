<?php

namespace App\Actions\Inquiries;

use App\Models\InquiryResponse;
use App\Models\InquiryResponseAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreInquiryResponseAttachments
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, InquiryResponseAttachment>
     */
    public function handle(InquiryResponse $response, User $uploader, array $files): array
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($response, $uploader, $files, &$storedPaths): array {
                $attachments = [];

                foreach ($files as $file) {
                    $path = $file->store("inquiry-responses/{$response->uuid}/attachments", 'local');

                    if ($path === false) {
                        throw new RuntimeException('The response attachment could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $realPath = $file->getRealPath();
                    $checksum = is_string($realPath) ? hash_file('sha256', $realPath) : false;
                    $originalName = Str::limit(
                        basename(str_replace('\\', '/', $file->getClientOriginalName())),
                        200,
                        '',
                    );

                    $attachment = $response->attachments()->create([
                        'uploaded_by_id' => $uploader->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $originalName ?: 'attachment',
                        'stored_name' => basename($path),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'extension' => strtolower($file->getClientOriginalExtension()) ?: null,
                        'size_bytes' => $file->getSize(),
                        'checksum' => is_string($checksum) ? $checksum : null,
                    ]);
                    $attachments[] = $attachment;

                    $this->recordEvent->handle($response->inquiry, 'response_attachment_uploaded', $uploader, [
                        'attachment_id' => $attachment->uuid,
                        'file_name' => $attachment->original_name,
                        'size_bytes' => $attachment->size_bytes,
                    ], $response);
                }

                return $attachments;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }
}
