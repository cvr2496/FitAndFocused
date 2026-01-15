<?php

namespace App\Http\Controllers;

use App\Services\AnthropicService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function chat(Request $request, AnthropicService $ai)
    {
        $request->validate([
            'message' => 'required|string',
            'context' => 'nullable|array'
        ]);

        $response = $ai->chat(
            $request->input('message'),
            $request->input('context', [])
        );

        return response()->json([
            'response' => $response
        ]);
    }
}
