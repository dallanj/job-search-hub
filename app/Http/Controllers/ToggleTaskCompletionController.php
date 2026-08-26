<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ToggleTaskCompletionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);
        $task->update(['completed_at' => $task->completed_at === null ? now() : null]);

        return back();
    }
}
