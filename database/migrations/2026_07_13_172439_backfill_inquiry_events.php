<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inquiries')
            ->leftJoin('users', 'users.id', '=', 'inquiries.created_by_id')
            ->select([
                'inquiries.id',
                'inquiries.created_by_id',
                'inquiries.submitted_at',
                'inquiries.type',
                'inquiries.status',
                'users.name as actor_name',
            ])
            ->orderBy('inquiries.id')
            ->chunkById(200, function ($inquiries): void {
                foreach ($inquiries as $inquiry) {
                    DB::table('inquiry_events')->insert([
                        'inquiry_id' => $inquiry->id,
                        'actor_id' => $inquiry->created_by_id,
                        'actor_name' => $inquiry->actor_name,
                        'type' => 'inquiry_created',
                        'metadata' => json_encode([
                            'type' => $inquiry->type,
                            'status' => $inquiry->status,
                            'backfilled' => true,
                        ], JSON_THROW_ON_ERROR),
                        'created_at' => $inquiry->submitted_at,
                    ]);
                }
            }, 'inquiries.id', 'id');

        DB::table('inquiry_response_events as response_events')
            ->join('inquiry_responses as responses', 'responses.id', '=', 'response_events.inquiry_response_id')
            ->leftJoin('users', 'users.id', '=', 'response_events.user_id')
            ->select([
                'response_events.id',
                'response_events.inquiry_response_id',
                'response_events.user_id',
                'response_events.type',
                'response_events.status_from',
                'response_events.status_to',
                'response_events.comment',
                'response_events.payload',
                'response_events.created_at',
                'responses.inquiry_id',
                'users.name as actor_name',
            ])
            ->orderBy('response_events.id')
            ->chunkById(200, function ($events): void {
                foreach ($events as $event) {
                    $payload = $event->payload === null
                        ? []
                        : (json_decode($event->payload, true, flags: JSON_THROW_ON_ERROR) ?: []);

                    DB::table('inquiry_events')->insert([
                        'inquiry_id' => $event->inquiry_id,
                        'actor_id' => $event->user_id,
                        'inquiry_response_id' => $event->inquiry_response_id,
                        'actor_name' => $event->actor_name,
                        'type' => 'response_'.$event->type,
                        'metadata' => json_encode([
                            ...$payload,
                            'status_from' => $event->status_from,
                            'status_to' => $event->status_to,
                            'comment' => $event->comment,
                            'backfilled' => true,
                        ], JSON_THROW_ON_ERROR),
                        'created_at' => $event->created_at,
                    ]);
                }
            }, 'response_events.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfilled audit events are intentionally retained until the table is rolled back.
    }
};
