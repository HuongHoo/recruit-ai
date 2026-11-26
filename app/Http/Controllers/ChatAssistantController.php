<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatAssistantRequest;
use App\Services\AiServiceException;
use App\Services\ChatAssistant;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoint behind the floating chat widget. Open to guests; rate limited by the route.
 */
class ChatAssistantController extends Controller
{
    public function __invoke(ChatAssistantRequest $request, ChatAssistant $assistant): JsonResponse
    {
        try {
            return response()->json($assistant->reply($request->conversation()));
        } catch (AiServiceException) {
            return response()->json([
                'reply' => 'Trợ lý đang bận, bạn thử lại sau ít phút nhé.',
                'jobs' => [],
                'total' => 0,
                'see_all_url' => null,
            ], 503);
        }
    }
}
