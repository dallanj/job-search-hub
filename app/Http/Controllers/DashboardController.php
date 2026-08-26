<?php

namespace App\Http\Controllers;

use App\Actions\BuildDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboard $buildDashboard): Response
    {
        return Inertia::render('Dashboard', $buildDashboard->handle($request->user()));
    }
}
