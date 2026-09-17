<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectWorkspaceService
{
    /** @return array<string, mixed> */
    public function payload(Project $project): array
    {
        $project->load(['user:id,name,email', 'milestones', 'updates', 'files', 'approvals']);

        return [
            ...$project->only(['id', 'title', 'description', 'status', 'user_id', 'quote_id', 'project_inquiry_id']),
            'target_date' => $project->target_date?->toDateString(),
            'client' => $project->user?->only(['id', 'name', 'email']),
            'milestones' => $project->milestones->sortBy('id')->values()->map(fn ($item) => [...$item->only(['id', 'title', 'description', 'status']), 'due_date' => $item->due_date?->toDateString()]),
            'updates' => $project->updates->sortByDesc('id')->values()->map(fn ($item) => [...$item->only(['id', 'body', 'created_at']), 'author' => $item->admin_id ? 'ASR Tech' : 'Client']),
            'files' => $project->files->sortByDesc('id')->values()->map(fn ($item) => [...$item->only(['id', 'original_name', 'size', 'created_at']), 'author' => $item->admin_id ? 'ASR Tech' : 'Client']),
            'approvals' => $project->approvals->sortByDesc('id')->values()->map(fn ($item) => $item->only(['id', 'title', 'description', 'status', 'response', 'created_at', 'decided_at'])),
        ];
    }

    public function ensureOpen(Project $project): void
    {
        if ($project->isClosed()) {
            throw ValidationException::withMessages(['project' => 'This project is closed. Contact support if it needs to be reopened.']);
        }
    }

    /** @param Closure(Project): mixed $callback */
    public function withOpenProject(Project $project, Closure $callback): void
    {
        DB::transaction(function () use ($project, $callback): void {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
            $this->ensureOpen($locked);
            $callback($locked);
        });
    }

    public function upload(Request $request, Project $project, bool $admin): void
    {
        $this->ensureOpen($project);
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:pdf,txt,png,jpg,jpeg,webp,zip', 'extensions:pdf,txt,png,jpg,jpeg,webp,zip']]);
        $file = $request->file('file');
        $path = $file->store('projects/'.$project->id, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['file' => 'The file could not be stored. Please try again.']);
        }
        try {
            $this->withOpenProject($project, fn (Project $locked) => $locked->files()->create([
                'path' => $path,
                'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'admin_id' => $admin ? $request->user('admin')->getAuthIdentifier() : null,
                'user_id' => $admin ? null : $request->user()->getAuthIdentifier(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function download(Project $project, ProjectFile $file): StreamedResponse
    {
        abort_unless((int) $file->project_id === (int) $project->id, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
