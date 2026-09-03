<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPipelineRequest;
use Dallanj\PiniaHydrate\Facades\PiniaHydrate;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function __invoke(IndexPipelineRequest $request): Response
    {
        $request->validated();
        PiniaHydrate::replace('pipeline', [
            'columns',
        ]);

        if (! $request->headers->has('X-Inertia-Partial-Data')) {
            PiniaHydrate::replace('options', ['companies']);
        }

        return Inertia::render('pipeline/Index', [
            '$pinia' => PiniaHydrate::toJson(),
        ]);
    }
}
