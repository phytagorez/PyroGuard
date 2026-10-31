<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananBotTelegram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Tangani panggilan webhook dari Telegram Bot API (§14.3 design.md).
     */
    public function __invoke(Request $request, LayananBotTelegram $telegram): JsonResponse
    {
        $hasil = $telegram->tanganiWebhook($request->all());

        return response()->json([
            'ok' => true,
            'result' => $hasil,
        ]);
    }
}
