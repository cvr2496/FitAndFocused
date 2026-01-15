<?php

namespace App\Features\AiCoach\Controllers;

use App\Http\Controllers\Controller;
use App\Features\AiCoach\AiCoachService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiChatController extends Controller
{
    public function chat(Request $request, AiCoachService $ai): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'context' => 'nullable|array'
        ]);

        try {
            $response = $ai->chat(
                $request->input('message'),
                $request->input('context', []),
                $request->user()
            );

            return response()->json([
                'response' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
