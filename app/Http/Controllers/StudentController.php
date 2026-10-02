<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\StudentExtraClass;
use App\Services\StudentMailService;
use App\Support\DiscountInput;
use App\Support\PaymentHistoryPayload;
use App\Support\ReservaStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentMailService $studentMail,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 4) {
            return response()->json([]);
        }

        $students = Student::query()
            ->with('course')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'last_name' => $student->last_name,
                'full_name' => trim($student->name.' '.$student->last_name),
                'email' => $student->email,
                'phone' => $student->phone,
                'course' => $student->course?->name,
                'allowed_classes' => $student->allowedClassesCount(),
                'completed_classes' => $student->completedClassesCount(),
                'used_classes' => $student->completedClassesCount(),
                'remaining_classes' => $student->remainingClasses(),
                'can_reserve' => $student->canReserve(),
            ]);

        return response()->json($students);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->enrollmentPayload($request);

        $student = DB::transaction(function () use ($payload) {
            $student = Student::query()->create([
                ...$payload,
                'password' => 'password',
                'created_by' => auth()->id(),
            ]);

            $student->recordPayment();

            return $student;
        });

        $student->load('course');

        $fallback = URL::previous() ?: route('admin.students.index');

        return redirect()
            ->to($fallback)
            ->with('assign_schedule', [
                'student_id' => $student->id,
                'student_name' => $student->fullName(),
                'course_name' => $student->course?->name ?? 'Sin curso',
                'num_classes' => $student->remainingClasses(),
            ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $rawExtras = $request->input('extra_classes', []);
        $request->merge([
            'extra_classes' => collect(is_array($rawExtras) ? $rawExtras : [])
                ->filter(fn ($row) => is_array($row) && filled($row['type'] ?? null))
                ->values()
                ->all(),
        ]);

        $payload = $this->enrollmentPayload($request, $student);

        $extras = collect($request->input('extra_classes', []))
            ->map(fn (array $row) => [
                'type' => $row['type'],
                'quantity' => (int) $row['quantity'],
                'notes' => filled($row['notes'] ?? null) ? $row['notes'] : null,
            ])
            ->all();

        DB::transaction(function () use ($student, $payload, $extras) {
            $student->update($payload);
            $student->extraClasses()->delete();

            if ($extras !== []) {
                $student->extraClasses()->createMany($extras);
            }
        });

        return redirect()
            ->to(URL::previous() ?: route('admin.students.index'))
            ->with('success', "Se actualizó la información de {$student->fullName()}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentPayload(Request $request, ?Student $student = null): array
    {
        $isHome = $request->boolean('is_home_class');

        $rules = [
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $student
                    ? Rule::unique('students', 'email')->ignore($student->id)
                    : 'unique:students,email',
            ],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'general_notes' => ['nullable', 'string', 'max:1000'],
            'is_home_class' => ['required', 'boolean'],
            'home_fee' => ['nullable', 'string', 'max:20'],
            'home_fee_mode' => ['nullable', Rule::in(array_keys(Student::HOME_FEE_MODES))],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'meeting_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'meeting_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'discount' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', Rule::in(array_keys(Student::PAYMENT_METHODS))],
            'payment_plan' => ['required', Rule::in(array_keys(Student::PAYMENT_PLANS))],
            'payment_initial' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($student) {
            $rules['extra_classes'] = ['nullable', 'array'];
            $rules['extra_classes.*.type'] = ['required', Rule::in(array_keys(StudentExtraClass::TYPES))];
            $rules['extra_classes.*.quantity'] = ['required', 'integer', 'min:1', 'max:20'];
            $rules['extra_classes.*.notes'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);
        $course = Course::query()->findOrFail($validated['course_id']);
        $payment = $this->paymentFields($course, $request, $isHome, $student);

        return [
            'course_id' => $validated['course_id'],
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip' => $validated['zip'],
            'country' => $validated['country'],
            'notes' => filled($validated['general_notes'] ?? null)
                ? trim((string) $validated['general_notes'])
                : null,
            'is_home_class' => $isHome,
            'meeting_point' => $isHome && filled($validated['meeting_point'] ?? null)
                ? trim((string) $validated['meeting_point'])
                : null,
            'meeting_lat' => $isHome && isset($validated['meeting_lat'], $validated['meeting_lng'])
                ? round((float) $validated['meeting_lat'], 7)
                : null,
            'meeting_lng' => $isHome && isset($validated['meeting_lat'], $validated['meeting_lng'])
                ? round((float) $validated['meeting_lng'], 7)
                : null,
            ...$payment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentFields(Course $course, Request $request, bool $isHome, ?Student $student = null): array
    {
        $courseCost = round((float) $course->cost, 2);
        $homeFee = $isHome
            ? DiscountInput::parseSurcharge($request->input('home_fee'), $courseCost)
            : ['percent' => 0.0, 'amount' => 0.0];
        $homeFeeMode = $isHome
            ? (string) $request->input('home_fee_mode', Student::HOME_FEE_MODE_TOTAL)
            : Student::HOME_FEE_MODE_TOTAL;

        if (! array_key_exists($homeFeeMode, Student::HOME_FEE_MODES)) {
            $homeFeeMode = Student::HOME_FEE_MODE_TOTAL;
        }

        $plan = (int) $request->input('payment_plan');
        $homeFeeTimes = Student::homeFeeMultiplier($homeFeeMode, $plan, (int) $course->num_classes);
        $homeFeeApplied = round((float) $homeFee['amount'] * $homeFeeTimes, 2);
        $subtotal = round($courseCost + $homeFeeApplied, 2);
        $canDiscount = $request->user()?->can('students.discount') ?? false;

        if ($canDiscount) {
            $parsed = DiscountInput::parse($request->input('discount'), $subtotal);
            $percent = $parsed['percent'];
            $amount = $parsed['amount'];
        } elseif ($student) {
            $percent = (float) $student->discount_percent;
            $amount = min((float) $student->discount_amount, $subtotal);
        } else {
            $percent = 0.0;
            $amount = 0.0;
        }

        $total = round(max(0, $subtotal - $amount), 2);
        $initial = 0.0;

        if ($plan === Student::PAYMENT_PLAN_SINGLE && $homeFeeMode === Student::HOME_FEE_MODE_PER_PAYMENT && $homeFeeApplied > 0) {
            $initial = round(max(0, $total - $homeFeeApplied), 2);
        } elseif ($plan > Student::PAYMENT_PLAN_SINGLE) {
            $request->validate([
                'payment_initial' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    function (string $attribute, mixed $value, \Closure $fail) use ($total): void {
                        if (round((float) $value, 2) > $total) {
                            $fail('El abono inicial no puede ser mayor al total.');
                        }
                    },
                ],
            ], [
                'payment_initial.required' => 'Indica la cantidad inicial abonada.',
                'payment_initial.min' => 'El abono inicial debe ser mayor a cero.',
            ]);

            $initial = round((float) $request->input('payment_initial'), 2);
        } elseif ($plan === Student::PAYMENT_PLAN_PER_CLASS) {
            $request->validate([
                'payment_initial' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function (string $attribute, mixed $value, \Closure $fail) use ($total): void {
                        if ($value === null || $value === '') {
                            return;
                        }

                        if (round((float) $value, 2) > $total) {
                            $fail('El abono no puede ser mayor al total.');
                        }
                    },
                ],
            ]);

            $initial = round((float) $request->input('payment_initial', 0), 2);
        }

        return [
            'home_fee_percent' => $homeFee['percent'],
            'home_fee_amount' => $homeFee['amount'],
            'home_fee_mode' => $homeFeeMode,
            'payment_subtotal' => $subtotal,
            'discount_percent' => $percent,
            'discount_amount' => $amount,
            'payment_total' => $total,
            'payment_method' => $request->input('payment_method'),
            'payment_plan' => $plan,
            'payment_initial' => $initial,
        ];
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->fullName();
        $student->delete();

        return redirect()
            ->to(URL::previous() ?: route('admin.students.index'))
            ->with('success', "Se eliminó a {$name} de la lista.");
    }

    public function schedule(Student $student): JsonResponse
    {
        $student->load('course');
        $today = now()->toDateString();

        $reservas = $student->reservas()
            ->with(['instructor', 'vehicle'])
            ->orderByRaw('case when date >= ? then 0 else 1 end', [$today])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $allowed = $student->allowedClassesCount();
        $booked = $reservas->where('status', '!=', 'cancelada')->count();

        $classes = $reservas->map(function (Reservas $reserva) use ($today) {
            $date = $reserva->date instanceof \DateTimeInterface
                ? $reserva->date->format('Y-m-d')
                : substr((string) $reserva->date, 0, 10);
            $time = Reservas::normalizeTime((string) $reserva->time);

            return [
                'id' => $reserva->id,
                'date' => $date,
                'time' => $time,
                'end_time' => date('H:i', strtotime($reserva->endsAt())),
                'is_past' => $date < $today,
                'status' => $reserva->status,
                'status_label' => ReservaStatus::label($reserva->status),
                'status_class' => ReservaStatus::badgeClass($reserva->status),
                'instructor' => $reserva->instructor?->fullName() ?: '—',
                'vehicle' => $reserva->vehicle
                    ? trim($reserva->vehicle->modelo.' ('.$reserva->vehicle->plate.')')
                    : '—',
            ];
        });

        return response()->json([
            'id' => $student->id,
            'name' => $student->fullName(),
            'course' => $student->course?->name,
            'allowed' => $allowed,
            'booked' => $booked,
            'remaining' => max(0, $allowed - $booked),
            'classes' => $classes,
        ]);
    }

    public function sendSchedule(Student $student): JsonResponse
    {
        $sent = $this->studentMail->sendSchedule($student);

        if (! $sent) {
            $hasClasses = $student->reservas()->where('status', '!=', 'cancelada')->exists();

            return response()->json([
                'message' => $hasClasses
                    ? 'No se pudieron enviar los horarios por correo. Intenta de nuevo.'
                    : 'El alumno no tiene horarios asignados para enviar.',
            ], $hasClasses ? 500 : 422);
        }

        return response()->json([
            'message' => "Registro exitoso. Horarios enviados por correo a {$student->email}.",
            'email' => $student->email,
            'receipt_url' => route('admin.students.receipt', $student),
            'receipt_send_url' => route('admin.students.receipt-email', $student),
            'history_url' => route('admin.students.payment-history', $student),
        ]);
    }

    public function receipt(Student $student): Response
    {
        return response($this->studentMail->receiptHtml($student))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function paymentHistory(Student $student): JsonResponse
    {
        return response()->json(PaymentHistoryPayload::from($student));
    }

    public function sendReceipt(Student $student): JsonResponse
    {
        if (! filled($student->email)) {
            return response()->json([
                'message' => 'El alumno no tiene un correo para enviar el recibo.',
            ], 422);
        }

        $sent = $this->studentMail->sendScheduleAndReceipt($student);

        if (! $sent['receipt']) {
            return response()->json([
                'message' => 'No se pudo enviar el recibo por correo. Intenta de nuevo.',
            ], 500);
        }

        if ($sent['has_classes'] && ! $sent['schedule']) {
            return response()->json([
                'message' => "El recibo se envió a {$student->email}, pero no se pudieron enviar los horarios. Intenta de nuevo.",
                'email' => $student->email,
                'receipt_sent' => true,
                'schedule_sent' => false,
            ], 500);
        }

        $message = $sent['has_classes']
            ? "Horarios y recibo enviados por correo a {$student->email}."
            : "Recibo enviado por correo a {$student->email}.";

        return response()->json([
            'message' => $message,
            'email' => $student->email,
            'receipt_sent' => true,
            'schedule_sent' => $sent['schedule'],
        ]);
    }

    public function storePayment(Request $request, Student $student): RedirectResponse
    {
        $student->load('payments');
        $balance = $student->balanceDue();
        $fallback = URL::previous() ?: route('admin.students.index');

        if ($balance <= 0) {
            return redirect()
                ->to($fallback)
                ->with('success', "{$student->fullName()} ya liquidó el curso.");
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, \Closure $fail) use ($balance): void {
                    if (round((float) $value, 2) > $balance) {
                        $fail('El abono no puede ser mayor al saldo pendiente.');
                    }
                },
            ],
            'payment_method' => ['required', Rule::in(array_keys(Student::PAYMENT_METHODS))],
        ]);

        $payment = $student->recordInstallment(
            (float) $validated['amount'],
            $validated['payment_method'],
            now()->toDateString(),
        );

        $remaining = $student->fresh(['payments'])->balanceDue();
        $message = $remaining <= 0
            ? "Se registró el abono de {$student->fullName()}. El curso quedó liquidado."
            : 'Se registró el abono de $'.number_format((float) $payment?->amount, 2).'. Saldo pendiente: $'.number_format($remaining, 2).'.';

        return redirect()->to($fallback)->with('success', $message);
    }
}
