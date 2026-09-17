<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectFile;
use App\Models\ProjectInquiry;
use App\Models\ProjectMilestone;
use App\Models\Quote;
use App\Models\User;
use App\Services\AdminAuditService;
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

    public function index(): Response
    {
        return Inertia::render('Admin/Projects/Index', [
            'projects' => Project::query()->with('user:id,name,email')->withCount('milestones')->latest()->paginate(20),
            'clients' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'inquiries' => ProjectInquiry::query()->whereNotNull('user_id')->whereDoesntHave('project')->latest()->get(['id', 'name', 'user_id']),
            'quotes' => Quote::query()->whereIn('status', [QuoteStatus::Accepted, QuoteStatus::Converted])->whereNotIn('id', Project::query()->whereNotNull('quote_id')->select('quote_id'))->latest()->get(['id', 'quote_number', 'user_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'project_inquiry_id' => ['nullable', 'integer', 'exists:project_inquiries,id'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'target_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $project = DB::transaction(function () use ($data, $request): Project {
            $inquiry = ! empty($data['project_inquiry_id']) ? ProjectInquiry::query()->lockForUpdate()->whereKey($data['project_inquiry_id'])->firstOrFail() : null;
            $quote = ! empty($data['quote_id']) ? Quote::query()->lockForUpdate()->whereKey($data['quote_id'])->firstOrFail() : null;
            if ($inquiry && (int) $inquiry->user_id !== (int) $data['user_id']) {
                throw ValidationException::withMessages(['project_inquiry_id' => 'Select an inquiry explicitly linked to this client.']);
            }
            if ($quote && ((int) $quote->user_id !== (int) $data['user_id'] || ! in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::Converted], true))) {
                throw ValidationException::withMessages(['quote_id' => 'Choose an accepted quote for this client.']);
            }
            if ($quote && $inquiry && (int) $inquiry->quote_id !== (int) $quote->id) {
                throw ValidationException::withMessages(['quote_id' => 'The quote must be the quote linked to this inquiry.']);
            }
            $existing = ($inquiry ? $inquiry->project : null) ?? ($quote ? Project::query()->where('quote_id', $quote->id)->first() : null);
            if ($existing) {
                return $existing;
            }
            $project = Project::query()->create([...$data, 'status' => 'planned']);
            $inquiry?->update(['status' => 'won']);
            /** @var Admin $admin */
            $admin = $request->user('admin');
            app(AdminAuditService::class)->record($admin, 'project.created', 'Created client project workspace.', $project, $project->only(['user_id', 'project_inquiry_id', 'quote_id']));

            return $project;
        });

        return redirect()->route('admin.projects.show', $project);
    }

    public function show(Project $project): Response
    {
        return Inertia::render('Admin/Projects/Show', ['project' => $this->workspace->payload($project)]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['required', 'string', 'max:10000'],
            'status' => ['required', Rule::in(Project::STATUSES)], 'target_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        DB::transaction(function () use ($project, $data): void {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if (in_array($data['status'], ['completed', 'cancelled'], true) && $project->approvals()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['status' => 'Resolve or cancel pending approval requests before closing the project.']);
            }
            if ($data['status'] === 'completed' && $project->milestones()->where('status', '!=', 'completed')->exists()) {
                throw ValidationException::withMessages(['status' => 'Complete every milestone before completing this project.']);
            }
            $oldStatus = $project->status;
            $project->update($data);
            if ($oldStatus !== $data['status']) {
                $project->updates()->create(['body' => 'Project status changed from '.$oldStatus.' to '.$data['status'].'.', 'admin_id' => request()->user('admin')->getAuthIdentifier()]);
            }
        });

        return back();
    }

    public function milestone(Request $request, Project $project): RedirectResponse
    {
        $this->workspace->ensureOpen($project);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $this->workspace->withOpenProject($project, fn (Project $locked) => $locked->milestones()->create([...$data, 'status' => 'planned']));

        return back();
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless((int) $milestone->project_id === (int) $project->id, 404);
        $this->workspace->ensureOpen($project);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'blocked', 'completed'])],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $this->workspace->withOpenProject($project, fn (Project $locked) => $locked->milestones()->whereKey($milestone->id)->update([...$data, 'completed_at' => $data['status'] === 'completed' ? ($milestone->completed_at ?? now()) : null]));

        return back();
    }

    public function postUpdate(Request $request, Project $project): RedirectResponse
    {
        $this->workspace->ensureOpen($project);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $this->workspace->withOpenProject($project, fn (Project $locked) => $locked->updates()->create([...$data, 'admin_id' => $request->user('admin')->getAuthIdentifier()]));

        return back();
    }

    public function upload(Request $request, Project $project): RedirectResponse
    {
        $this->workspace->upload($request, $project, true);

        return back();
    }

    public function download(Project $project, ProjectFile $file): StreamedResponse
    {
        return $this->workspace->download($project, $file);
    }

    public function approval(Request $request, Project $project): RedirectResponse
    {
        $this->workspace->ensureOpen($project);
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'description' => ['required', 'string', 'max:10000']]);
        $this->workspace->withOpenProject($project, fn (Project $locked) => $locked->approvals()->create([...$data, 'status' => 'pending', 'requested_by' => $request->user('admin')->getAuthIdentifier()]));

        return back();
    }

    public function cancelApproval(Project $project, ProjectApproval $approval): RedirectResponse
    {
        abort_unless((int) $approval->project_id === (int) $project->id, 404);
        $this->workspace->ensureOpen($project);
        if (! $project->approvals()->whereKey($approval->id)->where('status', 'pending')->update(['status' => 'cancelled', 'decided_at' => now()])) {
            throw ValidationException::withMessages(['approval' => 'Only pending requests can be cancelled.']);
        }

        return back();
    }
}
