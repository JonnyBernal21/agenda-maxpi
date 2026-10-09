<?php

namespace App\Services;

use App\Models\AdminNotificationRead;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public const PERMISSIONS = [
        'students.view',
        'students.manage',
        'students.edit',
        'sales.view',
    ];

    private const FEED_DAYS = 30;

    private const FEED_LIMIT = 40;

    private const DUE_PREVIEW = 8;

    public function canAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return collect(self::PERMISSIONS)->contains(fn (string $key) => $user->hasPermission($key));
    }

    /**
     * @return array{unread: int, items: list<array<string, mixed>>}
     */
    public function feed(User $user): array
    {
        $reads = AdminNotificationRead::query()
            ->where('user_id', $user->id)
            ->pluck('item_key')
            ->all();
        $readKeys = array_flip($reads);

        $events = collect()
            ->merge($this->paymentItems())
            ->merge($this->studentItems())
            ->sortByDesc('timestamp')
            ->values()
            ->take(self::FEED_LIMIT);

        $items = collect($this->dueReminder())
            ->merge($events)
            ->map(function (array $item) use ($readKeys) {
                $item['unread'] = ! isset($readKeys[$item['key']]);
                unset($item['timestamp']);

                return $item;
            })
            ->all();

        $unread = collect($items)->where('unread', true)->count();

        return [
            'unread' => $unread,
            'items' => array_values($items),
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    public function markRead(User $user, array $keys): void
    {
        $keys = collect($keys)
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->unique()
            ->values();

        foreach ($keys as $key) {
            AdminNotificationRead::query()->firstOrCreate([
                'user_id' => $user->id,
                'item_key' => $key,
            ]);
        }
    }

    public function markAll(User $user): void
    {
        $keys = collect($this->feed($user)['items'])
            ->pluck('key')
            ->all();

        $this->markRead($user, $keys);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dueReminder(): array
    {
        $today = $this->today()->toDateString();
        $debtors = $this->debtors();

        if ($debtors->isEmpty()) {
            return [];
        }

        $total = round((float) $debtors->sum(fn (Student $student) => $student->balanceDue()), 2);
        $preview = $debtors->take(self::DUE_PREVIEW);
        $more = max(0, $debtors->count() - $preview->count());
        $count = $debtors->count();

        return [[
            'key' => 'balance_due:'.$today,
            'type' => 'balance_due',
            'title' => 'Cuotas pendientes',
            'body' => $count === 1
                ? '1 alumno tiene saldo por cobrar · '.$this->money($total)
                : $count.' alumnos tienen saldo por cobrar · '.$this->money($total),
            'time' => 'Hoy',
            'url' => route('admin.students.index'),
            'tone' => 'warning',
            'icon' => 'bi-exclamation-circle',
            'people' => $preview->map(fn (Student $student) => [
                'name' => $student->fullName(),
                'course' => $student->course?->name ?: 'Sin curso',
                'phone' => $student->phone ?: '—',
                'balance' => $student->balanceDue(),
                'balance_label' => $this->money($student->balanceDue()),
            ])->all(),
            'more' => $more,
            'timestamp' => $this->today()->timestamp,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentItems(): array
    {
        $since = now()->subDays(self::FEED_DAYS);

        return StudentPayment::query()
            ->with(['student' => fn ($query) => $query->with('course')->withSum('payments as paid_amount', 'amount')])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(self::FEED_LIMIT)
            ->get()
            ->map(function (StudentPayment $payment) {
                $student = $payment->student;
                $amount = round((float) $payment->amount, 2);
                $total = round((float) ($student?->payment_total ?? 0), 2);
                $isFull = $total > 0 && $amount >= $total;
                $balance = $student?->balanceDue() ?? 0.0;
                $name = $student?->fullName() ?: 'Alumno';
                $method = $payment->paymentMethodLabel();
                $course = $student?->course?->name;

                $body = $name.' pagó '.$this->money($amount).' ('.$method.')';

                if ($course) {
                    $body .= ' · '.$course;
                }

                $body .= $balance > 0
                    ? '. Saldo pendiente: '.$this->money($balance)
                    : '. Curso liquidado.';

                return [
                    'key' => 'payment:'.$payment->id,
                    'type' => 'payment',
                    'title' => $isFull ? 'Pago completo' : 'Abono registrado',
                    'body' => $body,
                    'time' => $this->timeLabel($payment->created_at),
                    'url' => route('admin.students.index'),
                    'tone' => 'success',
                    'icon' => $isFull ? 'bi-check-circle' : 'bi-cash-coin',
                    'people' => [],
                    'more' => 0,
                    'timestamp' => $payment->created_at?->timestamp ?? 0,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentItems(): array
    {
        $since = now()->subDays(self::FEED_DAYS);

        return Student::query()
            ->with('course')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(self::FEED_LIMIT)
            ->get()
            ->map(function (Student $student) {
                $details = array_filter([
                    $student->course?->name ?: 'Sin curso',
                    $student->paymentPlanLabel(),
                    $student->phone ?: null,
                    $student->email ?: null,
                ]);

                return [
                    'key' => 'student:'.$student->id,
                    'type' => 'student_registered',
                    'title' => 'Nuevo alumno',
                    'body' => $student->fullName().' · '.implode(' · ', $details),
                    'time' => $this->timeLabel($student->created_at),
                    'url' => route('admin.students.index'),
                    'tone' => 'info',
                    'icon' => 'bi-person-plus',
                    'people' => [],
                    'more' => 0,
                    'timestamp' => $student->created_at?->timestamp ?? 0,
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, Student>
     */
    private function debtors(): Collection
    {
        return Student::query()
            ->with('course')
            ->withSum('payments as paid_amount', 'amount')
            ->where('payment_total', '>', 0)
            ->orderBy('name')
            ->orderBy('last_name')
            ->get()
            ->filter(fn (Student $student) => $student->balanceDue() > 0)
            ->values();
    }

    private function today(): Carbon
    {
        return Carbon::now(SameDayScheduleCutoff::TIMEZONE)->startOfDay();
    }

    private function timeLabel(?Carbon $date): string
    {
        if (! $date) {
            return '—';
        }

        $local = $date->timezone(SameDayScheduleCutoff::TIMEZONE);
        $now = Carbon::now(SameDayScheduleCutoff::TIMEZONE);

        if ($local->isSameDay($now)) {
            return 'Hoy '.$local->format('g:i A');
        }

        if ($local->isSameDay($now->copy()->subDay())) {
            return 'Ayer '.$local->format('g:i A');
        }

        return $local->format('d/m/Y g:i A');
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }
}
