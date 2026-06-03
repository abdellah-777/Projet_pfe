<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Resources\AIArtifactResource;
use App\Models\AI\AIArtifact;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Task;
use App\Services\AI\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIProjectManagerController extends Controller
{
    private GroqService $groq;

    public function __construct()
    {
        $this->groq = new GroqService();
    }

    public function generateStructure(Request $request, Project $project): JsonResponse
    {
        try {
            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Project name: {$project->name}\n\nProject idea:\n{$project->original_idea}",
                    ],
                ],
                temperature: 0.7,
                jsonMode: true
            );

            $result = json_decode($content, true);

            DB::beginTransaction();

            foreach ($result['epics'] as $epicData) {
                $epic = Epic::create([
                    'project_id'   => $project->id,
                    'title'        => $epicData['title'],
                    'description'  => $epicData['description'],
                    'priority'     => $epicData['priority'] ?? 'medium',
                    'ai_generated' => true,
                ]);

                foreach ($epicData['stories'] ?? [] as $story) {
                    $storyTask = Task::create([
                        'project_id'   => $project->id,
                        'epic_id'      => $epic->id,
                        'reporter_id'  => $request->user()->id,
                        'title'        => $story['title'],
                        'description'  => $story['description'],
                        'type'         => 'story',
                        'priority'     => $story['priority'] ?? 'medium',
                        'story_points' => $story['story_points'] ?? null,
                        'ai_generated' => true,
                    ]);

                    foreach ($story['tasks'] ?? [] as $taskData) {
                        Task::create([
                            'project_id'      => $project->id,
                            'epic_id'         => $epic->id,
                            'parent_task_id'  => $storyTask->id,
                            'reporter_id'     => $request->user()->id,
                            'title'           => $taskData['title'],
                            'description'     => $taskData['description'],
                            'type'            => 'task',
                            'priority'        => $taskData['priority'] ?? 'medium',
                            'estimated_hours' => $taskData['estimated_hours'] ?? null,
                            'ai_generated'    => true,
                        ]);
                    }
                }
            }

            $artifact = AIArtifact::create([
                'project_id' => $project->id,
                'created_by' => $request->user()->id,
                'type'       => 'project_structure',
                'title'      => "Project Structure — {$project->name}",
                'content'    => json_encode($result),
                'metadata'   => [
                    'epics_count'       => count($result['epics']),
                    'timeline_weeks'    => $result['suggested_timeline_weeks'] ?? null,
                    'team_roles_needed' => $result['team_roles_needed'] ?? [],
                ],
            ]);

            $project->update([
                'ai_metadata' => [
                    'summary'        => $result['summary'] ?? '',
                    'generated_at'   => now()->toDateTimeString(),
                    'timeline_weeks' => $result['suggested_timeline_weeks'] ?? null,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message'  => 'Project structure generated successfully',
                'artifact' => new AIArtifactResource($artifact),
                'summary'  => $result['summary'] ?? '',
                'stats'    => [
                    'epics_created' => count($result['epics']),
                    'timeline'      => ($result['suggested_timeline_weeks'] ?? '?') . ' weeks',
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to generate project structure',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert AI Project Manager. Analyze the project idea and generate a complete project structure.

Return a JSON object with this EXACT structure:
{
  "summary": "Brief 2-3 sentence project summary",
  "epics": [
    {
      "title": "Epic title",
      "description": "Epic description",
      "priority": "high",
      "estimated_weeks": 2,
      "stories": [
        {
          "title": "As a user, I want...",
          "description": "User story description",
          "priority": "high",
          "story_points": 5,
          "tasks": [
            {
              "title": "Technical task title",
              "description": "What needs to be done",
              "priority": "medium",
              "estimated_hours": 4
            }
          ]
        }
      ]
    }
  ],
  "suggested_timeline_weeks": 12,
  "team_roles_needed": ["Backend Developer", "Frontend Developer"],
  "risks": ["Risk description 1", "Risk description 2"]
}

Rules:
- Generate minimum 3 epics
- Each epic must have 2-4 stories
- Each story must have 2-5 tasks
- Be realistic with estimations
- priorities must be: critical, high, medium, or low
PROMPT;
    }
}