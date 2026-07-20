<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

class StoreInquiryComment
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    /** @param array<int, UploadedFile> $files */
    public function handle(
        Inquiry $inquiry,
        User $author,
        string $body,
        ?InquiryComment $parent = null,
        array $files = [],
        string $source = 'manual',
    ): InquiryComment {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($inquiry, $author, $body, $parent, $files, $source, &$storedPaths): InquiryComment {
                $response = $inquiry->response()->firstOrFail();
                $roleName = $author->getRoleNames()->first();
                $role = $roleName === null ? null : Role::query()->where('name', $roleName)->first();
                $fallbackRole = $role?->getAttribute('fallback_label');

                $comment = $inquiry->comments()->create([
                    'inquiry_response_id' => $response->id,
                    'user_id' => $author->id,
                    'parent_id' => $parent?->id,
                    'author_name' => $author->name,
                    'author_role' => $role === null ? null : (is_string($fallbackRole) && $fallbackRole !== '' ? $fallbackRole : Str::headline($role->name)),
                    'body' => $body,
                    'source' => $source,
                ]);

                foreach ($files as $file) {
                    $path = $file->store("inquiry-comments/{$comment->uuid}/attachments", 'local');
                    abort_if($path === false, 500, 'Comment attachment could not be stored.');
                    $storedPaths[] = $path;
                    $realPath = $file->getRealPath();
                    $checksum = is_string($realPath) ? hash_file('sha256', $realPath) : false;

                    $comment->attachments()->create([
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 200, ''),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'extension' => strtolower($file->getClientOriginalExtension()) ?: null,
                        'size_bytes' => $file->getSize(),
                        'checksum' => is_string($checksum) ? $checksum : null,
                    ]);
                }

                if ($source === 'manual') {
                    $this->recordEvent->handle($inquiry, 'comment_added', $author, [
                        'comment_id' => $comment->uuid,
                        'reply_to_id' => $parent?->uuid,
                        'attachments_count' => count($files),
                    ], $response);
                }

                return $comment->load(['attachments', 'parent']);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }
    }
}
