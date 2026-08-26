<?php

namespace App\Http\Controllers;

use App\Actions\GetUpcomingActions;
use App\Http\Requests\IndexUpcomingActionRequest;
use Inertia\Inertia;
use Inertia\Response;

class UpcomingActionController extends Controller
{
    public function __invoke(
        IndexUpcomingActionRequest $request,
        GetUpcomingActions $getUpcomingActions,
    ): Response {
        $request->validated();
        $days = $request->integer('days', 14);

        return Inertia::render('upcoming-actions/Index', [
            'actions' => $getUpcomingActions->handle($request->user(), $days),
            'filters' => ['days' => $days],
        ]);
    }
}
