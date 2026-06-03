<?php

namespace App\Http\Controllers\Api\Sprint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sprint\StoreSprintRequest;
use App\Http\Requests\Sprint\UpdateSprintRequest;
use App\Http\Resources\SprintResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $sprints = Sprint::query()
            ->where('project_id', $project->id)
            ->withCount('tasks')
            ->latest()
            ->get();

        return response()->json([
            'data' => SprintResource::collection($sprints),
        ]);
    }

    public function store(StoreSprintRequest $request, Project $project): JsonResponse
    {
        $sprint = Sprint::create([
    ...$request->validated(),
    'project_id' => $project->id,
    'status'     => 'planning',
]);

        return response()->json([
            'message' => 'Sprint created successfully',
            'data'    => new SprintResource($sprint),
        ], 201);
    }

    public function show(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        $sprint->load('tasks.assignee');

        return response()->json([
            'data'  => new SprintResource($sprint),
            'tasks' => TaskResource::collection($sprint->tasks),
        ]);
    }

    public function update(UpdateSprintRequest $request, Project $project, Sprint $sprint): JsonResponse
    {
        if ($request->status === 'active') {
            Sprint::where('project_id', $project->id)
                  ->where('status', 'active')
                  ->where('id', '!=', $sprint->id)
                  ->update(['status' => 'planning']);
        }

        $sprint->update($request->validated());

        return response()->json([
            'message' => 'Sprint updated successfully',
            'data'    => new SprintResource($sprint),
        ]);
    }

    public function destroy(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        $sprint->delete();

        return response()->json([
            'message' => 'Sprint deleted successfully',
        ]);
    }
}