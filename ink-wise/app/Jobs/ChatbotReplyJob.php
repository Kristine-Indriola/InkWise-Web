<?php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatbotReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobId;
    public string $message;
    public string $identifier;

    public function __construct(string $jobId, string $message, string $identifier = '')
    {
        $this->jobId = $jobId;
        $this->message = $message;
        $this->identifier = $identifier;
    }

    public function handle(): void
    {
        $cacheKey = 'chatbot_response:'.$this->jobId;
        try {
            $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
            if (! $apiKey) {
                Cache::put($cacheKey, ['status' => 'done', 'reply' => 'Bot not configured (no API key).'], now()->addMinutes(60));
                return;
            }

            $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are InkWise assistant. Give concise, helpful answers about templates, orders, and site features.'],
                    ['role' => 'user', 'content' => $this->message],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            if ($resp->failed()) {
                Log::error('OpenAI API failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                Cache::put($cacheKey, ['status' => 'done', 'reply' => 'Sorry, the assistant is unavailable right now.'], now()->addMinutes(60));
                return;
            }

            $json = $resp->json();
            $reply = $json['choices'][0]['message']['content'] ?? 'Sorry, no reply available.';

            Cache::put($cacheKey, ['status' => 'done', 'reply' => trim($reply)], now()->addHours(2));
        } catch (\Throwable $e) {
            Log::error('ChatbotReplyJob error: '.$e->getMessage(), ['exception' => $e]);
            Cache::put($cacheKey, ['status' => 'done', 'reply' => 'Server error while contacting assistant.'], now()->addMinutes(60));
        }
    }
}