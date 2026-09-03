<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Filters\BySearchTerm;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Dallanj\PiniaHydrate\Facades\PiniaHydrate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use UnexpectedValueException;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request, Pipeline $pipeline): Response
    {
        $request->validated();
        $search = $request->filled('search') ? $request->string('search')->toString() : null;
        $status = $request->filled('status') ? $request->string('status')->toString() : 'open';
        $query = Task::query()
            ->whereIn('job_application_id', $request->user()->jobApplications()->select('id'))
            ->with(['jobApplication:id,company_id,role_title', 'jobApplication.company:id,name']);
        $tasks = $this->applySearch($pipeline, $query, $search)
            ->when($status === 'open', fn (Builder $query): Builder => $query->whereNull('completed_at'))
            ->when($status === 'completed', fn (Builder $query): Builder => $query->whereNotNull('completed_at'))
            ->when($status === 'overdue', fn (Builder $query): Builder => $query->whereNull('completed_at')->whereDate('due_at', '<', today()))
            ->orderByDesc('priority')->orderBy('due_at')->paginate(20)->withQueryString();

        PiniaHydrate::module('tasks', [
            'tasks' => $tasks,
        ], 'replace');

        if (! $request->headers->has('X-Inertia-Partial-Data')) {
            PiniaHydrate::replace('options', ['taskPriorities']);
        }

        return Inertia::render('tasks/Index', [
            '$pinia' => PiniaHydrate::toJson(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Task::class);

        return Inertia::render('tasks/Create', [...$this->formOptions(), 'selectedApplicationId' => request()->user()->jobApplications()->whereKey(request()->integer('application'))->value('id')]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('tasks.show', $task);
    }

    public function show(Task $task): Response
    {
        Gate::authorize('view', $task);

        return Inertia::render('tasks/Show', ['task' => $task->load(['jobApplication:id,company_id,role_title', 'jobApplication.company:id,name']), 'priorities' => $this->priorities()]);
    }

    public function edit(Task $task): Response
    {
        Gate::authorize('update', $task);

        return Inertia::render('tasks/Edit', [...$this->formOptions(), 'task' => $task]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return to_route('tasks.show', $task);
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);
        $task->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return to_route('tasks.index');
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
            'priorities' => $this->priorities(),
        ];
    }

    /** @return list<array{value: int, label: string}> */
    private function priorities(): array
    {
        return array_map(
            fn (TaskPriority $priority): array => [
                'value' => $priority->value,
                'label' => Str::headline($priority->name),
            ],
            TaskPriority::cases(),
        );
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private function applySearch(Pipeline $pipeline, Builder $query, ?string $search): Builder
    {
        $result = $pipeline->send($query)->through([new BySearchTerm($search)])->thenReturn();
        if (! $result instanceof Builder || ! $result->getModel() instanceof Task) {
            throw new UnexpectedValueException('The task search pipeline must return a task query.');
        }

        return $result;
    }
}
