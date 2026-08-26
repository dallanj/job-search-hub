<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationNoteRequest;
use App\Http\Requests\UpdateApplicationNoteRequest;
use App\Models\ApplicationNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationNoteController extends Controller
{
    public function store(StoreApplicationNoteRequest $request): RedirectResponse
    {
        $request->user()->applicationNotes()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note added.')]);

        return back();
    }

    public function edit(ApplicationNote $applicationNote): Response
    {
        Gate::authorize('update', $applicationNote);

        return Inertia::render('application-notes/Edit', [
            'note' => $applicationNote->load([
                'jobApplication:id,company_id,role_title',
                'jobApplication.company:id,name',
            ]),
        ]);
    }

    public function update(
        UpdateApplicationNoteRequest $request,
        ApplicationNote $applicationNote,
    ): RedirectResponse {
        $applicationNote->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note updated.')]);

        return to_route('applications.show', $applicationNote->job_application_id);
    }

    public function destroy(ApplicationNote $applicationNote): RedirectResponse
    {
        Gate::authorize('delete', $applicationNote);
        $applicationNote->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note deleted.')]);

        return back();
    }
}
