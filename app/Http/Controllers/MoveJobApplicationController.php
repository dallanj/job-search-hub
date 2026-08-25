<?php

namespace App\Http\Controllers;

use App\Actions\MoveJobApplication;
use App\Enums\ApplicationStatus;
use App\Http\Requests\MoveJobApplicationRequest;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;

class MoveJobApplicationController extends Controller
{
    public function __invoke(
        MoveJobApplicationRequest $request,
        JobApplication $application,
        MoveJobApplication $moveJobApplication,
    ): RedirectResponse {
        $moveJobApplication->handle(
            $application,
            ApplicationStatus::from($request->string('status')->toString()),
            $request->integer('position'),
        );

        return back();
    }
}
