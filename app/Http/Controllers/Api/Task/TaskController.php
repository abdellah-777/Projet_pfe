<?php

namespace App\Http\Controllers\Api\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\MoveTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee', 'epic', 'sprint'])
            ->withCount('subtasks')
            ->orderBy('status')
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = Task::create([
    ...$request->validated(),
    'project_id'   => $project->id,
    'reporter_id'  => $request->user()->id,
    'status'       => $request->status ?? 'todo',
    'order'        => 0,
    'logged_hours' => 0,
    'ai_generated' => false,
]);

        return response()->json([
            'message' => 'Task created successfully',
            'data'    => new TaskResource(
                $task->load(['assignee', 'epic', 'sprint'])
            ),
        ], 201);
    }

    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $task->load(['assignee', 'epic', 'sprint', 'subtasks', 'comments.user']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $task->update($request->validated());

        return response()->json([
            'message' => 'Task updated successfully',
            'data'    => new TaskResource(
                $task->load(['assignee', 'epic', 'sprint'])
            ),
        ]);
    }

    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    public function moveTask(MoveTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $task->update([
            'status' => $request->status,
            'order'  => $request->order ?? $task->order,
        ]);

        return response()->json([
            'message' => 'Task moved successfully',
            'data'    => new TaskResource($task),
        ]);
    }
}