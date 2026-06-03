<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProjectInvitationController extends Controller
{
    // إرسال invitation
    public function invite(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:manager,developer,viewer'],
        ]);

        // تحقق إلا كان already member
        $existingMember = $project->members()
            ->where('email', $request->email)
            ->exists();

        if ($existingMember) {
            return response()->json([
                'message' => 'This user is already a member',
            ], 422);
        }

        // تحقق إلا كان already invited
        $existingInvitation = ProjectInvitation::where('project_id', $project->id)
            ->where('email', $request->email)
            ->where('status', 'pending')
            ->exists();

        if ($existingInvitation) {
            return response()->json([
                'message' => 'An invitation has already been sent to this email',
            ], 422);
        }

        // إنشاء invitation
        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $request->user()->id,
            'email'      => $request->email,
            'role'       => $request->role,
        ]);

        // إرسال email
        Mail::to($request->email)->send(
            new ProjectInvitationMail($invitation->load(['project', 'invitedBy']))
        );

        return response()->json([
            'message' => 'Invitation sent successfully',
            'data'    => [
                'id'         => $invitation->id,
                'email'      => $invitation->email,
                'role'       => $invitation->role,
                'expires_at' => $invitation->expires_at->toDateTimeString(),
            ],
        ]);
    }

    // قبول invitation
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->where('status', 'pending')
            ->with(['project', 'invitedBy'])
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            return response()->json(['message' => 'Invitation has expired'], 422);
        }

        // تحقق إلا كان user موجود
        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            return response()->json([
                'message'          => 'Please create an account first',
                'requires_signup'  => true,
                'email'            => $invitation->email,
                'project_name'     => $invitation->project->name,
            ], 200);
        }

        // زيد المستخدم للمشروع
        $invitation->project->members()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role],
        ]);

        // تحديث status
        $invitation->update(['status' => 'accepted']);

        return response()->json([
            'message'      => 'Invitation accepted successfully',
            'project_id'   => $invitation->project->uuid,
            'project_name' => $invitation->project->name,
        ]);
    }

    // رفض invitation
    public function decline(string $token): JsonResponse
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'declined']);

        return response()->json([
            'message' => 'Invitation declined',
        ]);
    }

    // قائمة الـ invitations ديال المشروع
    public function index(Request $request, Project $project): JsonResponse
    {
        $invitations = ProjectInvitation::where('project_id', $project->id)
            ->with('invitedBy')
            ->latest()
            ->get()
            ->map(fn($inv) => [
                'id'         => $inv->id,
                'email'      => $inv->email,
                'role'       => $inv->role,
                'status'     => $inv->status,
                'invited_by' => $inv->invitedBy->name,
                'expires_at' => $inv->expires_at->toDateTimeString(),
                'created_at' => $inv->created_at->toDateTimeString(),
            ]);

        return response()->json(['data' => $invitations]);
    }

    // حذف invitation
    public function destroy(Request $request, Project $project, ProjectInvitation $invitation): JsonResponse
    {
        $invitation->delete();

        return response()->json(['message' => 'Invitation cancelled']);
    }
}