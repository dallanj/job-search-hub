<?php

namespace App\Http\Controllers;

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Http\Requests\StoreInterviewRequest;
use App\Http\Requests\UpdateInterviewRequest;
use App\Models\Interview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Interview::class);

        $interviews = Interview::query()
            ->whereIn('job_application_id', request()->user()->jobApplications()->select('id'))
            ->with(['jobApplication:id,company_id,role_title', 'jobApplication.company:id,name', 'contact:id,name'])
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return Inertia::render('interviews/Index', ['interviews' => $interviews]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Interview::class);
        $selectedApplicationId = request()->user()->jobApplications()
            ->whereKey(request()->integer('application'))
            ->value('id');

        return Inertia::render('interviews/Create', [
            ...$this->formOptions(),
            'selectedApplicationId' => $selectedApplicationId,
        ]);
    }

    public function store(StoreInterviewRequest $request): RedirectResponse
    {
        $interview = Interview::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interview scheduled.')]);

        return to_route('interviews.show', $interview);
    }

    public function show(Interview $interview): Response
    {
        Gate::authorize('view', $interview);

        return Inertia::render('interviews/Show', [
            'interview' => $interview->load([
                'jobApplication:id,company_id,role_title',
                'jobApplication.company:id,name,website',
                'contact:id,name,email,phone,linkedin_url',
            ]),
            'types' => $this->enumOptions(InterviewType::cases()),
            'outcomes' => $this->enumOptions(InterviewOutcome::cases()),
        ]);
    }

    public function edit(Interview $interview): Response
    {
        Gate::authorize('update', $interview);

        return Inertia::render('interviews/Edit', [
            ...$this->formOptions(),
            'interview' => $interview,
        ]);
    }

    public function update(UpdateInterviewRequest $request, Interview $interview): RedirectResponse
    {
        $interview->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interview updated.')]);

        return to_route('interviews.show', $interview);
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        Gate::authorize('delete', $interview);
        $interview->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interview deleted.')]);

        return to_route('interviews.index');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'applications' => request()->user()->jobApplications()
                ->select(['id', 'company_id', 'role_title'])
                ->with('company:id,name')
                ->latest()
                ->get(),
            'contacts' => request()->user()->contacts()
                ->select(['id', 'company_id', 'name', 'job_title'])
                ->orderBy('name')
                ->get(),
            'types' => $this->enumOptions(InterviewType::cases()),
            'outcomes' => $this->enumOptions(InterviewOutcome::cases()),
        ];
    }

    /**
     * @param  list<InterviewType|InterviewOutcome>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(fn ($case): array => [
            'value' => $case->value,
            'label' => Str::headline($case->value),
        ], $cases);
    }
}
