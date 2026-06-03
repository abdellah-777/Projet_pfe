<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIArtifact;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\AI\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIRiskController extends Controller
{
    private GroqService $groq;

    public function __construct()
    {
        $this->groq = new GroqService();
    }

    public function analyzeRisks(Request $request, Project $project): JsonResponse
    {
        try {
            $projectData = $this->collectProjectData($project);

            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => $this->getRiskPrompt(),
                    ],
                    [
                        'role'    => 'user',
                        'content' => $projectData,
                    ],
                ],
                temperature: 0.3,
                jsonMode: true
            );

            $result = json_decode($content, true);

            AIArtifact::create([
                'project_id' => $project->id,
                'created_by' => $request->user()->id,
                'type'       => 'risk_analysis',
                'title'      => "Risk Analysis — {$project->name}",
                'content'    => json_encode($result),
                'metadata'   => ['generated_at' => now()->toDateTimeString()],
            ]);

            return response()->json([
                'message' => 'Risk analysis completed',
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to analyze risks',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function sprintRiskScore(Request $request, Sprint $sprint): JsonResponse
    {
        try {
            $totalTasks     = $sprint->tasks()->count();
            $completedTasks = $sprint->tasks()->where('status', 'done')->count();
            $overdueTasks   = $sprint->tasks()
                ->where('status', '!=', 'done')
                ->where('due_date', '<', now())
                ->count();
            $blockedTasks   = $sprint->tasks()->get()->filter->isBlocked()->count();
            $daysLeft       = now()->diffInDays($sprint->end_date, false);

            $content = $this->groq->chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => 'You are a sprint risk analyzer. Return JSON with: risk_score (0-100), risk_level (low/medium/high/critical), delay_probability_percent, summary, recommendations array.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Sprint: {$sprint->name}\nTotal tasks: {$totalTasks}\nCompleted: {$completedTasks}\nOverdue: {$overdueTasks}\nBlocked: {$blockedTasks}\nDays left: {$daysLeft}\nEnd date: {$sprint->end_date}",
                    ],
                ],
                temperature: 0.2,
                jsonMode: true
            );

            $result = json_decode($content, true);

            return response()->json([
                'message' => 'Sprint risk score calculated',
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to calculate sprint risk',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function collectProjectData(Project $project): string
    {
        $totalTasks    = $project->tasks()->count();
        $doneTasks     = $project->tasks()->where('status', 'done')->count();
        $overdueTasks  = $project->tasks()
            ->where('status', '!=', 'done')
            ->where('due_date', '<', now())
            ->count();
        $blockedTasks  = $project->tasks()->get()->filter->isBlocked()->count();
        $activeSprint  = $project->activeSprint;

        return <<<DATA
Project: {$project->name}
Status: {$project->status}
Total tasks: {$totalTasks}
Completed tasks: {$doneTasks}
Overdue tasks: {$overdueTasks}
Blocked tasks: {$blockedTasks}
Active sprint: {$activeSprint?->name}
Sprint end date: {$activeSprint?->end_date}
Project end date: {$project->end_date}
DATA;
    }

    private function getRiskPrompt(): string
    {
        return <<<PROMPT
You are an expert AI Risk Predictor for software projects.

Return JSON with this structure:
{
  "overall_risk_level": "low|medium|high|critical",
  "delay_probability_percent": 65,
  "risks": [
    {
      "type": "deadline|resource|technical|dependency",
      "title": "Risk title",
      "description": "Detailed description",
      "impact": "high|medium|low",
      "probability": "high|medium|low",
      "mitigation": "Suggested action"
    }
  ],
  "recommendations": ["Action 1", "Action 2"],
  "summary": "Overall risk assessment summary"
}
PROMPT;
    }
}