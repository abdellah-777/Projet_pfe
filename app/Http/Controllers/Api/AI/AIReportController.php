<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIArtifact;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\AI\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIReportController extends Controller
{
    private GroqService $groq;

    public function __construct()
    {
        $this->groq = new GroqService();
    }

    public function generateSprintReport(Request $request, Sprint $sprint): JsonResponse
    {
        try {
            $sprintData = $this->collectSprintData($sprint);

            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => 'You are an expert Scrum Master. Generate a professional sprint report in Markdown format. Include: executive summary, completed work, incomplete work, team performance insights, blockers encountered, and recommendations for next sprint.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $sprintData,
                    ],
                ],
                temperature: 0.4
            );

            $artifact = AIArtifact::create([
                'project_id' => $sprint->project_id,
                'created_by' => $request->user()->id,
                'type'       => 'sprint_report',
                'title'      => "Sprint Report — {$sprint->name}",
                'content'    => $content,
                'metadata'   => [
                    'sprint_id'    => $sprint->id,
                    'generated_at' => now()->toDateTimeString(),
                ],
            ]);

            return response()->json([
                'message'  => 'Sprint report generated successfully',
                'artifact' => [
                    'id'      => $artifact->id,
                    'title'   => $artifact->title,
                    'content' => $artifact->content,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate sprint report',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function weeklyProjectSummary(Request $request, Project $project): JsonResponse
    {
        try {
            $completedThisWeek = $project->tasks()
                ->whereBetween('updated_at', [now()->subWeek(), now()])
                ->where('status', 'done')
                ->count();

            $inProgress = $project->tasks()->where('status', 'in_progress')->count();
            $todo       = $project->tasks()->where('status', 'todo')->count();

            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => 'Generate a concise weekly project summary in Markdown. Include: progress highlights, key metrics, team achievements, concerns, and next week priorities.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Project: {$project->name}\nCompleted this week: {$completedThisWeek}\nIn progress: {$inProgress}\nTodo: {$todo}\nWeek: " . now()->format('W, Y'),
                    ],
                ],
                temperature: 0.4
            );

            AIArtifact::create([
                'project_id' => $project->id,
                'created_by' => $request->user()->id,
                'type'       => 'sprint_report',
                'title'      => "Weekly Summary — " . now()->format('M d, Y'),
                'content'    => $content,
                'metadata'   => ['week' => now()->format('W-Y')],
            ]);

            return response()->json([
                'message' => 'Weekly summary generated',
                'content' => $content,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate weekly summary',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function collectSprintData(Sprint $sprint): string
    {
        $sprint->load('tasks.assignee');

        $totalTasks      = $sprint->tasks->count();
        $completedTasks  = $sprint->tasks->where('status', 'done')->count();
        $inProgress      = $sprint->tasks->where('status', 'in_progress')->count();
        $todo            = $sprint->tasks->where('status', 'todo')->count();
        $totalPoints     = $sprint->tasks->sum('story_points');
        $completedPoints = $sprint->tasks->where('status', 'done')->sum('story_points');

        return <<<DATA
Sprint: {$sprint->name}
Goal: {$sprint->goal}
Period: {$sprint->start_date} to {$sprint->end_date}
Status: {$sprint->status}

Tasks Summary:
- Total: {$totalTasks}
- Completed: {$completedTasks}
- In Progress: {$inProgress}
- Todo: {$todo}

Story Points:
- Total: {$totalPoints}
- Completed: {$completedPoints}
DATA;
    }
}