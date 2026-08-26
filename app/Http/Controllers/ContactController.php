<?php

namespace App\Http\Controllers;

use App\Filters\ByCompany;
use App\Filters\BySearchTerm;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use UnexpectedValueException;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request, Pipeline $pipeline): Response
    {
        $request->validated();
        $search = $request->filled('search') ? $request->string('search')->toString() : null;
        $companyId = $request->integer('company_id') ?: null;
        $query = $request->user()->contacts()->getQuery()
            ->select(['id', 'company_id', 'name', 'job_title', 'email', 'phone', 'created_at'])
            ->with('company:id,name');

        $contacts = $this->applyFilters($pipeline, $query, $search, $companyId)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contacts/Index', [
            'contacts' => $contacts,
            'companies' => $this->companyOptions(),
            'filters' => ['search' => $search, 'company_id' => $companyId],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Contact::class);

        return Inertia::render('contacts/Create', [
            'companies' => $this->companyOptions(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = DB::transaction(function () use ($request): Contact {
            $data = $request->safe()->except(['company_name']);
            $data['company_id'] = $this->companyFor($request)->id;

            return $request->user()->contacts()->create($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return to_route('contacts.show', $contact);
    }

    public function show(Contact $contact): Response
    {
        Gate::authorize('view', $contact);

        return Inertia::render('contacts/Show', [
            'contact' => $contact->load('company:id,name,website'),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        Gate::authorize('update', $contact);

        return Inertia::render('contacts/Edit', [
            'contact' => $contact->load('company:id,name'),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($request, $contact): void {
            $data = $request->safe()->except(['company_name']);
            $data['company_id'] = $this->companyFor($request)->id;
            $contact->update($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.show', $contact);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('delete', $contact);
        $contact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact deleted.')]);

        return to_route('contacts.index');
    }

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    private function applyFilters(
        Pipeline $pipeline,
        Builder $query,
        ?string $search,
        ?int $companyId,
    ): Builder {
        $filteredQuery = $pipeline->send($query)
            ->through([
                new BySearchTerm($search),
                new ByCompany($companyId),
            ])
            ->thenReturn();

        if (! $filteredQuery instanceof Builder
            || ! $filteredQuery->getModel() instanceof Contact) {
            throw new UnexpectedValueException('The contact filter pipeline must return a contact query.');
        }

        return $filteredQuery;
    }

    /**
     * @return Collection<int, Company>
     */
    private function companyOptions(): Collection
    {
        return request()->user()->companies()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function companyFor(StoreContactRequest|UpdateContactRequest $request): Company
    {
        if ($request->integer('company_id') > 0) {
            return $request->user()->companies()->findOrFail($request->integer('company_id'));
        }

        return $request->user()->companies()->create([
            'name' => $request->string('company_name')->squish()->toString(),
        ]);
    }
}
