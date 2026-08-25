<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('viewAny', JobApplication::class);

        $applications = request()->user()->jobApplications()
            ->select([
                'id',
                'company_id',
                'role_title',
                'status',
                'sort_order',
                'location',
                'workplace_type',
                'applied_at',
            ])
            ->with('company:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (JobApplication $application): string => $application->status->value);

        $columns = array_map(
            fn (ApplicationStatus $status): array => [
                'status' => $status->value,
                'label' => Str::headline($status->value),
                'applications' => $applications->get($status->value, collect())->values(),
            ],
            ApplicationStatus::cases(),
        );

        return Inertia::render('pipeline/Index', ['columns' => $columns]);
    }
}
