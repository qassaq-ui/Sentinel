<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreInquiryAttachments
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, InquiryAttachment>
     */
    public function handle(Inquiry $inquiry, array $files): array
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($inquiry, $files, &$storedPaths): array {
                $attachments = [];

                foreach ($files as $file) {
                    $path = $file->store("inquiries/{$inquiry->uuid}/attachments", 'local');

                    if ($path === false) {
                        throw new RuntimeException('The inquiry attachment could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $realPath = $file->getRealPath();
                    $checksum = is_string($realPath) ? hash_file('sha256', $realPath) : false;
                    $extension = strtolower($file->getClientOriginalExtension());
                    $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
                    $originalName = Str::limit(
                        basename(str_replace('\\', '/', $file->getClientOriginalName())),
                        200,
                        '',
                    );

                    $attachment = $inquiry->attachments()->create([
                        'uploaded_by_id' => null,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $originalName ?: 'attachment',
                        'stored_name' => basename($path),
                        'mime_type' => $mimeType,
                        'extension' => $extension !== '' ? $extension : null,
                        'file_type' => $this->fileType($mimeType, $extension),
                        'size_bytes' => $file->getSize(),
                        'checksum' => is_string($checksum) ? $checksum : null,
                        'metadata' => ['source' => 'public_submission'],
                    ]);

                    $attachments[] = $attachment;
                    $this->recordEvent->handle($inquiry, 'inquiry_attachment_uploaded', null, [
                        'attachment_id' => $attachment->uuid,
                        'file_name' => $attachment->original_name,
                        'file_type' => $attachment->file_type,
                        'size_bytes' => $attachment->size_bytes,
                    ]);
                }

                return $attachments;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    private function fileType(string $mimeType, string $extension): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return InquiryAttachment::TYPE_PHOTO;
        }

        if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'm4a', 'wav', 'ogg', 'webm'], true)) {
            return InquiryAttachment::TYPE_AUDIO;
        }

        return match ($extension) {
            'pdf' => InquiryAttachment::TYPE_PDF,
            'xls', 'xlsx' => InquiryAttachment::TYPE_SPREADSHEET,
            'txt' => InquiryAttachment::TYPE_TEXT,
            'doc', 'docx' => InquiryAttachment::TYPE_DOCUMENT,
            default => InquiryAttachment::TYPE_OTHER,
        };
    }
}
