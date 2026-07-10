<?php

namespace App\Http\Controllers;

use App\Http\Requests\AIAssistantChatRequest;
use App\Models\Inquiry;
use App\Services\AIAssistant\AIAssistantClient;
use App\Services\AIAssistant\AIAssistantPromptBuilder;
use App\Services\AIAssistant\AssigneeRecommendationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIAssistantController extends Controller
{
    public function chat(
        AIAssistantChatRequest $request,
        AIAssistantPromptBuilder $promptBuilder,
        AIAssistantClient $client,
        AssigneeRecommendationService $assigneeRecommendationService,
    ): JsonResponse {
        $data = $request->validated();
        app()->setLocale($data['locale']);

        $inquiry = isset($data['inquiry_number'])
            ? Inquiry::query()->where('number', $data['inquiry_number'])->first()
            : null;
        $recommendations = $data['job'] === 'recommend_assignee'
            ? $assigneeRecommendationService->forInquiry($inquiry)
            : [];

        try {
            return response()->json([
                'message' => $client->chat($promptBuilder->messages($data, $inquiry)),
                'recommendations' => $recommendations,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            if ($data['job'] === 'recommend_assignee' && $recommendations !== []) {
                return response()->json([
                    'message' => __('The AI service did not respond, but these specialists match the inquiry by role and current workload.'),
                    'recommendations' => $recommendations,
                ]);
            }

            return response()->json([
                'message' => __('AI assistant is temporarily unavailable.'),
                'recommendations' => [],
            ], 502);
        }
    }
}
