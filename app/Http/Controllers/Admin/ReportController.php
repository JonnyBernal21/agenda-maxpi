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
        $today = now()->startOfDay();
        $legacyDate = $request->filled('date') ? $this->parseDate($request->string('date')->toString()) : null;

        $from = $this->parseDate($request->input('from'))
            ?? $legacyDate
            ?? $today->copy();

        $to = $this->parseDate($request->input('to'))
            ?? $legacyDate
            ?? $from->copy();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $report = $this->dailyReport->forRange($from, $to);

        return view('admin.reports.index', [
            'report' => $report,
            'from' => $report['from'],
            'to' => $report['to'],
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
