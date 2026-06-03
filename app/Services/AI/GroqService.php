<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GroqService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.groq.com/openai/v1';

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY');
    }

    public function chat(
        array $messages,
        string $model = 'llama-3.3-70b-versatile',
        float $temperature = 0.7,
        bool $jsonMode = false
    ): string {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", $payload);

        if ($response->failed()) {
            throw new \Exception('Groq API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}