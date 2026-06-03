<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Project\ProjectController;
use App\Http\Controllers\Api\Project\ProjectInvitationController;
use App\Http\Controllers\Api\Task\TaskController;
use App\Http\Controllers\Api\Sprint\SprintController;
use App\Http\Controllers\Api\Epic\EpicController;
use App\Http\Controllers\Api\AI\AIProjectManagerController;
use App\Http\Controllers\Api\AI\AIBusinessAnalystController;
use App\Http\Controllers\Api\AI\AIRiskController;
use App\Http\Controllers\Api\AI\AIReportController;
use App\Http\Controllers\Api\AI\AIMeetingController;

// =====================
// Test Route
// =====================
Route::get('/test', function () {
    return response()->json(['message' => 'API working!']);
});

// =====================
// Public Routes
// =====================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// Invitations — public
Route::post('invitations/{token}/accept',  [ProjectInvitationController::class, 'accept']);
Route::post('invitations/{token}/decline', [ProjectInvitationController::class, 'decline']);

// =====================
// Protected Routes
// =====================
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Members
    Route::post('projects/{project}/members',        [ProjectController::class, 'addMember']);
    Route::get('projects/{project}/members',         [ProjectController::class, 'members']);
    Route::delete('projects/{project}/members',      [ProjectController::class, 'removeMember']);
    Route::patch('projects/{project}/members/role',  [ProjectController::class, 'updateMemberRole']);

    // Invitations
    Route::post('projects/{project}/invite',                          [ProjectInvitationController::class, 'invite']);
    Route::get('projects/{project}/invitations',                      [ProjectInvitationController::class, 'index']);
    Route::delete('projects/{project}/invitations/{invitation}',      [ProjectInvitationController::class, 'destroy']);

    // Tasks + Sprints + Epics
    Route::prefix('projects/{project}')->group(function () {
        Route::apiResource('tasks',   TaskController::class);
        Route::patch('tasks/{task}/move', [TaskController::class, 'moveTask']);
        Route::apiResource('sprints', SprintController::class);
        Route::apiResource('epics',   EpicController::class);
    });

    // =====================
    // AI Routes
    // =====================
    Route::prefix('ai')->group(function () {

        // AI Project Manager
        Route::post('projects/{project}/generate-structure',
            [AIProjectManagerController::class, 'generateStructure']);

        // AI Business Analyst
        Route::post('projects/{project}/generate-srs',
            [AIBusinessAnalystController::class, 'generateSRS']);
        Route::post('projects/{project}/ask-analyst',
            [AIBusinessAnalystController::class, 'askQuestion']);

        // AI Risk Predictor
        Route::get('projects/{project}/risk-analysis',
            [AIRiskController::class, 'analyzeRisks']);
        Route::get('sprints/{sprint}/risk-score',
            [AIRiskController::class, 'sprintRiskScore']);

        // AI Report Generator
        Route::post('sprints/{sprint}/generate-report',
            [AIReportController::class, 'generateSprintReport']);
        Route::post('projects/{project}/weekly-summary',
            [AIReportController::class, 'weeklyProjectSummary']);

        // AI Meeting Assistant
        Route::post('projects/{project}/process-meeting-notes',
            [AIMeetingController::class, 'processMeetingNotes']);
    });
});