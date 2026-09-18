<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsService $analytics): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'analytics' => $analytics->dashboard(),
        ]);
    }
}
