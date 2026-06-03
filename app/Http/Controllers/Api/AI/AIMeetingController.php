<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIArtifact;
use App\Models\Project;
use App\Models\Task;
use App\Services\AI\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIMeetingController extends Controller
{
    private GroqService $groq;

    public function __construct()
    {
        $this->groq = new GroqService();
    }

    public function processMeetingNotes(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'notes' => ['required', 'string', 'min:10'],
        ]);

        try {
            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => $this->getMeetingPrompt(),
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Project: {$project->name}\n\nMeeting Notes:\n{$request->notes}",
                    ],
                ],
                temperature: 0.3,
                jsonMode: true
            );

            $result = json_decode($content, true);

            DB::beginTransaction();

            $createdTasks = [];
            foreach ($result['tasks'] ?? [] as $taskData) {
                $task = Task::create([
                    'project_id'   => $project->id,
                    'reporter_id'  => $request->user()->id,
                    'title'        => $taskData['title'],
                    'description'  => $taskData['description'],
                    'type'         => 'task',
                    'priority'     => $taskData['priority'] ?? 'medium',
                    'due_date'     => $taskData['due_date'] ?? null,
                    'ai_generated' => true,
                    'ai_metadata'  => ['source' => 'meeting_notes'],
                ]);

                $createdTasks[] = [
                    'id'       => $task->uuid,
                    'title'    => $task->title,
                    'priority' => $task->priority,
                ];
            }

            AIArtifact::create([
                'project_id' => $project->id,
                'created_by' => $request->user()->id,
                'type'       => 'meeting_tasks',
                'title'      => 'Meeting Notes — ' . now()->format('M d, Y'),
                'content'    => $request->notes,
                'metadata'   => [
                    'tasks_created' => count($createdTasks),
                    'decisions'     => $result['decisions'] ?? [],
                    'action_items'  => $result['action_items'] ?? [],
                    'generated_at'  => now()->toDateTimeString(),
                ],
            ]);

            DB::commit();

            return response()->json([
                'message'       => 'Meeting notes processed successfully',
                'tasks_created' => count($createdTasks),
                'tasks'         => $createdTasks,
                'decisions'     => $result['decisions'] ?? [],
                'action_items'  => $result['action_items'] ?? [],
                'summary'       => $result['summary'] ?? '',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process meeting notes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function getMeetingPrompt(): string
    {
        return <<<PROMPT
You are an expert AI Meeting Assistant. Extract structured information from meeting notes.

Return JSON with this EXACT structure:
{
  "summary": "Brief meeting summary",
  "decisions": [
    "Decision 1 made in the meeting",
    "Decision 2 made in the meeting"
  ],
  "action_items": [
    "Action item 1 with owner if mentioned",
    "Action item 2"
  ],
  "tasks": [
    {
      "title": "Clear actionable task title",
      "description": "Task description with context",
      "priority": "high|medium|low",
      "due_date": null
    }
  ]
}

Rules:
- Extract only CLEAR action items as tasks
- Keep task titles concise and actionable
- Set priority based on urgency mentioned
- Only set due_date if explicitly mentioned in format YYYY-MM-DD
PROMPT;
    }
}