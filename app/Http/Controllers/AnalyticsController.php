<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): Response
    {
        return Inertia::render('analytics/index', [
            'analytics' => $analytics->payload($request->user()),
        ]);
    }
}
