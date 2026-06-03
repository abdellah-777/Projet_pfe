<?php

namespace App\Http\Controllers\Api\Epic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epic\StoreEpicRequest;
use App\Http\Resources\EpicResource;
use App\Models\Epic;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpicController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $epics = Epic::query()
            ->where('project_id', $project->id)
            ->withCount('tasks')
            ->latest()
            ->get();

        return response()->json([
            'data' => EpicResource::collection($epics),
        ]);
    }

    public function store(StoreEpicRequest $request, Project $project): JsonResponse
    {
        $epic = Epic::create([
    ...$request->validated(),
    'project_id'   => $project->id,
    'status'       => 'open',
    'ai_generated' => false,
]);

        return response()->json([
            'message' => 'Epic created successfully',
            'data'    => new EpicResource($epic),
        ], 201);
    }

    public function show(Request $request, Project $project, Epic $epic): JsonResponse
    {
        $epic->load('tasks.assignee');

        return response()->json([
            'data' => new EpicResource($epic),
        ]);
    }

    public function update(Request $request, Project $project, Epic $epic): JsonResponse
    {
        $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['sometimes', 'in:critical,high,medium,low'],
            'status'      => ['sometimes', 'in:open,in_progress,done'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
        ]);

        $epic->update($request->validated());

        return response()->json([
            'message' => 'Epic updated successfully',
            'data'    => new EpicResource($epic),
        ]);
    }

    public function destroy(Request $request, Project $project, Epic $epic): JsonResponse
    {
        $epic->delete();

        return response()->json([
            'message' => 'Epic deleted successfully',
        ]);
    }
}