<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->startOfDay();
        $from = $this->parseDate($request->input('from')) ?? $today->copy();
        $to = $this->parseDate($request->input('to')) ?? $from->copy();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $fromString = $from->toDateString();
        $toString = $to->toDateString();
        $isSingleDay = $fromString === $toString;

        $sales = StudentPayment::query()
            ->with(['student.course', 'student.creator', 'creator'])
            ->whereDate('created_at', '>=', $fromString)
            ->whereDate('created_at', '<=', $toString)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $firstPaymentIds = StudentPayment::query()
            ->selectRaw('MIN(id) as id')
            ->groupBy('student_id')
            ->pluck('id');

        $sales->each(function (StudentPayment $sale) use ($firstPaymentIds) {
            $sale->setAttribute('is_first_payment', $firstPaymentIds->contains($sale->id));
        });

        $full = $sales->filter(fn (StudentPayment $sale) => $sale->isFullPayment());
        $installments = $sales->reject(fn (StudentPayment $sale) => $sale->isFullPayment());

        return view('admin.sales.index', [
            'sales' => $sales,
            'from' => $fromString,
            'to' => $toString,
            'is_today' => $isSingleDay && $from->isToday(),
            'is_single_day' => $isSingleDay,
            'date_label' => $this->periodLabel($from, $to, $isSingleDay),
            'kpis' => [
                'total' => round((float) $sales->sum('amount'), 2),
                'total_count' => $sales->count(),
                'full' => round((float) $full->sum('amount'), 2),
                'full_count' => $full->count(),
                'installments' => round((float) $installments->sum('amount'), 2),
                'installment_count' => $installments->count(),
            ],
        ]);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function periodLabel(Carbon $from, Carbon $to, bool $isSingleDay): string
    {
        $start = $from->copy()->locale('es');
        $end = $to->copy()->locale('es');

        if ($isSingleDay) {
            return $start->isoFormat('dddd D [de] MMMM [de] YYYY');
        }

        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $start->isoFormat('D').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
        }

        if ($start->isSameYear($end)) {
            return $start->isoFormat('D [de] MMMM').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
        }

        return $start->isoFormat('D [de] MMMM [de] YYYY').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
    }
}
