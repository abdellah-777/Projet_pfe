<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount(['tasks', 'members'])
            ->with('owner')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'total'        => $projects->total(),
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create([
            'organization_id' => $request->user()->organization_id,
            'owner_id'        => $request->user()->id,
            'name'            => $request->name,
            'slug'            => Str::slug($request->name) . '-' . Str::random(4),
            'description'     => $request->description,
            'original_idea'   => $request->original_idea,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ]);

        $project->members()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json([
            'message' => 'Project created successfully',
            'data'    => new ProjectResource($project->load('owner')),
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $project->loadCount(['tasks', 'members'])
                ->load(['owner', 'activeSprint']);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $project->update($request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data'    => new ProjectResource($project),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function addMember(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', 'in:manager,developer,viewer'],
        ]);

        $project->members()->syncWithoutDetaching([
            $request->user_id => ['role' => $request->role],
        ]);

        return response()->json([
            'message' => 'Member added successfully',
        ]);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_if(
            $project->organization_id !== $request->user()->organization_id,
            403,
            'Unauthorized'
        );
    }
    public function members(Request $request, Project $project): JsonResponse
{
    $this->authorizeProject($request, $project);

    $members = $project->members()->get()->map(function ($user) {
        return [
            'id'        => $user->uuid,
            'name'      => $user->name,
            'email'     => $user->email,
            'job_title' => $user->job_title,
            'avatar_url'=> $user->avatar_url,
            'role'      => $user->pivot->role,
        ];
    });

    return response()->json(['data' => $members]);
}

public function removeMember(Request $request, Project $project): JsonResponse
{
    $this->authorizeProject($request, $project);

    $request->validate([
        'user_id' => ['required', 'exists:users,id'],
    ]);

    $project->members()->detach($request->user_id);

    return response()->json(['message' => 'Member removed successfully']);
}

public function updateMemberRole(Request $request, Project $project): JsonResponse
{
    $this->authorizeProject($request, $project);

    $request->validate([
        'user_id' => ['required', 'exists:users,id'],
        'role'    => ['required', 'in:manager,developer,viewer'],
    ]);

    $project->members()->updateExistingPivot($request->user_id, [
        'role' => $request->role,
    ]);

    return response()->json(['message' => 'Role updated successfully']);
}
}