<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectFile;
use App\Services\ProjectWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectWorkspaceService $workspace) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Client/Projects/Index', [
            'projects' => Project::query()->where('user_id', $request->user()->getAuthIdentifier())->withCount(['milestones', 'approvals as pending_approvals_count' => fn ($query) => $query->where('status', 'pending')])->latest()->paginate(12),
        ]);
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorizeProject($request, $project);

        return Inertia::render('Client/Projects/Show', ['project' => $this->workspace->payload($project)]);
    }

    public function postUpdate(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($request, $project);
        $this->workspace->ensureOpen($project);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $this->workspace->withOpenProject($project, fn (Project $locked) => $locked->updates()->create([...$data, 'user_id' => $request->user()->getAuthIdentifier()]));

        return back();
    }

    public function upload(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($request, $project);
        $this->workspace->upload($request, $project, false);

        return back();
    }

    public function download(Request $request, Project $project, ProjectFile $file): StreamedResponse
    {
        $this->authorizeProject($request, $project);

        return $this->workspace->download($project, $file);
    }

    public function decide(Request $request, Project $project, ProjectApproval $approval): RedirectResponse
    {
        $this->authorizeProject($request, $project);
        abort_unless((int) $approval->project_id === (int) $project->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'changes_requested'])],
            'response' => ['required_if:status,changes_requested', 'nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($project, $approval, $data, $request): void {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            $this->workspace->ensureOpen($project);
            if (! $project->approvals()->whereKey($approval->id)->where('status', 'pending')->update([
                ...$data, 'decided_by' => $request->user()->getAuthIdentifier(), 'decided_at' => now(),
            ])) {
                throw ValidationException::withMessages(['approval' => 'This request has already been decided or cancelled. Refresh the project to see the latest decision.']);
            }
        });

        return back();
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless((int) $project->user_id === (int) $request->user()->getAuthIdentifier(), 404);
    }
}
