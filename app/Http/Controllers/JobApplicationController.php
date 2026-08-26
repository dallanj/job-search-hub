<?php

namespace App\Http\Controllers;

use App\Actions\MoveJobApplication;
use App\Enums\ApplicationStatus;
use App\Http\Requests\IndexJobApplicationRequest;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;
use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexJobApplicationRequest $request): Response
    {
        $filters = $request->validated();
        $applications = $request->user()->jobApplications()
            ->select(['id', 'company_id', 'role_title', 'status', 'location', 'applied_at', 'created_at'])
            ->with('company:id,name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('role_title', 'like', "%{$search}%")
                        ->orWhereHas('company', fn (Builder $companyQuery): Builder => $companyQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query
                ->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('applications/Index', [
            'applications' => $applications,
            'filters' => $filters,
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', JobApplication::class);

        return Inertia::render('applications/Create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobApplicationRequest $request): RedirectResponse
    {
        $application = DB::transaction(function () use ($request): JobApplication {
            $data = $request->safe()->except(['company_name']);
            $data['company_id'] = $this->companyFor($request)->id;
            $data['salary_currency'] = isset($data['salary_currency'])
                ? Str::upper($data['salary_currency'])
                : null;
            $lastPosition = $request->user()->jobApplications()
                ->where('status', $data['status'])
                ->lockForUpdate()
                ->max('sort_order');
            $data['sort_order'] = $lastPosition === null ? 0 : ((int) $lastPosition) + 1;

            $application = $request->user()->jobApplications()->create($data);
            $application->statusEvents()->create([
                'from_status' => null,
                'to_status' => $application->status,
                'changed_at' => now(),
            ]);

            return $application;
        }, attempts: 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application created.')]);

        return to_route('applications.show', $application);
    }

    /**
     * Display the specified resource.
     */
    public function show(JobApplication $application): Response
    {
        Gate::authorize('view', $application);

        return Inertia::render('applications/Show', [
            'application' => $application->load([
                'company:id,name,website',
                'statusEvents' => fn ($query) => $query
                    ->select(['id', 'job_application_id', 'from_status', 'to_status', 'changed_at', 'note'])
                    ->latest('changed_at')
                    ->latest('id'),
                'interviews' => fn ($query) => $query
                    ->with('contact:id,name')
                    ->orderBy('scheduled_at'),
            ]),
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobApplication $application): Response
    {
        Gate::authorize('update', $application);

        return Inertia::render('applications/Edit', [
            ...$this->formOptions(),
            'application' => $application->load('company:id,name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateJobApplicationRequest $request,
        JobApplication $application,
        MoveJobApplication $moveJobApplication,
    ): RedirectResponse {
        $data = $request->safe()->except(['company_name']);
        $targetStatus = ApplicationStatus::from($data['status']);
        unset($data['status']);
        $data['company_id'] = $this->companyFor($request)->id;
        $data['salary_currency'] = isset($data['salary_currency'])
            ? Str::upper($data['salary_currency'])
            : null;

        $application->update($data);

        if ($application->status !== $targetStatus) {
            $moveJobApplication->handle($application, $targetStatus, PHP_INT_MAX);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return to_route('applications.show', $application);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobApplication $application): RedirectResponse
    {
        Gate::authorize('delete', $application);

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('applications.index');
    }

    /**
     * @return array{companies: Collection<int, Company>, statuses: array<int, array{value: string, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'companies' => request()->user()->companies()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'statuses' => $this->statuses(),
        ];
    }

    private function companyFor(StoreJobApplicationRequest|UpdateJobApplicationRequest $request): Company
    {
        if ($request->integer('company_id') > 0) {
            return $request->user()->companies()->findOrFail($request->integer('company_id'));
        }

        return $request->user()->companies()->create([
            'name' => $request->string('company_name')->squish()->toString(),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return array_map(
            fn (ApplicationStatus $status): array => [
                'value' => $status->value,
                'label' => Str::headline($status->value),
            ],
            ApplicationStatus::cases(),
        );
    }
}
