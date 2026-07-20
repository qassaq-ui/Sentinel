<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inquiry_events')
            ->whereIn('type', ['response_approved', 'response_returned'])
            ->whereNotNull('inquiry_response_id')
            ->orderBy('id')
            ->each(function (object $event): void {
                $metadata = json_decode((string) $event->metadata, true);
                $comment = is_array($metadata) ? ($metadata['comment'] ?? null) : null;

                if (! is_string($comment) || trim($comment) === '') {
                    return;
                }

                DB::table('inquiry_comments')->insert([
                    'uuid' => (string) Str::uuid(),
                    'inquiry_id' => $event->inquiry_id,
                    'inquiry_response_id' => $event->inquiry_response_id,
                    'user_id' => $event->actor_id,
                    'author_name' => $event->actor_name,
                    'author_role' => $event->actor_role,
                    'body' => $comment,
                    'source' => 'legacy_review',
                    'created_at' => $event->created_at,
                    'updated_at' => $event->created_at,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inquiry_comments')->where('source', 'legacy_review')->delete();
    }
};
