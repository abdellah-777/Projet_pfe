<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Resources\AIArtifactResource;
use App\Models\AI\AIArtifact;
use App\Models\AI\AIContext;
use App\Models\Project;
use App\Services\AI\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIBusinessAnalystController extends Controller
{
    private GroqService $groq;

    public function __construct()
    {
        $this->groq = new GroqService();
    }

    public function generateSRS(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'additional_context' => ['nullable', 'string'],
        ]);

        try {
            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => $this->getSRSPrompt(),
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Project: {$project->name}\n\nDescription: {$project->description}\n\nOriginal Idea: {$project->original_idea}\n\nAdditional Context: {$request->additional_context}",
                    ],
                ],
                temperature: 0.5
            );

            $artifact = AIArtifact::create([
                'project_id' => $project->id,
                'created_by' => $request->user()->id,
                'type'       => 'srs_document',
                'title'      => "SRS Document — {$project->name}",
                'content'    => $content,
                'metadata'   => [
                    'generated_at' => now()->toDateTimeString(),
                    'model'        => 'llama-3.3-70b-versatile',
                ],
            ]);

            return response()->json([
                'message'  => 'SRS document generated successfully',
                'artifact' => new AIArtifactResource($artifact),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate SRS',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function askQuestion(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string'],
        ]);

        try {
            $context = AIContext::where('project_id', $project->id)->first();
            $history = $context?->conversation_history ?? [];

            $history[] = [
                'role'    => 'user',
                'content' => $request->question,
            ];

            $messages = [
                [
                    'role'    => 'system',
                    'content' => "You are an expert Business Analyst for the project: {$project->name}. Project idea: {$project->original_idea}. Answer questions clearly and ask for clarification when requirements are incomplete.",
                ],
                ...$history,
            ];

            $answer = $this->groq->chat(
                messages: $messages,
                temperature: 0.6
            );

            $history[] = [
                'role'    => 'assistant',
                'content' => $answer,
            ];

            AIContext::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'context_data'         => ['project_name' => $project->name],
                    'conversation_history' => array_slice($history, -20),
                    'last_updated_at'      => now(),
                ]
            );

            return response()->json([
                'answer' => $answer,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process question',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function getSRSPrompt(): string
    {
        return <<<PROMPT
You are a senior Business Analyst. Generate a complete Software Requirements Specification (SRS) document.

Structure the document as follows:

# Software Requirements Specification
## 1. Introduction
### 1.1 Purpose
### 1.2 Scope
### 1.3 Definitions

## 2. Overall Description
### 2.1 Product Perspective
### 2.2 User Classes and Characteristics
### 2.3 Assumptions and Dependencies

## 3. Functional Requirements
(List all features with FR-001, FR-002 format)

## 4. Non-Functional Requirements
### 4.1 Performance
### 4.2 Security
### 4.3 Scalability

## 5. Database Schema Suggestions
(List main entities and relationships)

## 6. User Roles and Workflows
(Describe each user type and their main workflows)

## 7. API Endpoints Overview
(List main API endpoints needed)

Be thorough, professional, and specific to the project described.
PROMPT;
    }
}