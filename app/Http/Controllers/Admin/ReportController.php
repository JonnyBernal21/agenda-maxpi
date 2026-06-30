<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly DailyReportService $dailyReport,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date')->toString())->startOfDay()
            : now()->startOfDay();

        $report = $this->dailyReport->forDate($date);

        return view('admin.reports.index', [
            'report' => $report,
            'selectedDate' => $report['date'],
        ]);
    }
}
