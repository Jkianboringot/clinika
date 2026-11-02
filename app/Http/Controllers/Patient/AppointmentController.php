<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsApproveEnum;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Procedure;
use App\Models\User;
use App\Services\DentistAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct(private DentistAvailability $availability) {}

    public function index()
    {
        $appointments = Appointment::where('patient_id', auth('patient')->id())
            ->with('procedure:id,name')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(10);

        return Inertia::render('Patients/Appointment/Index', [
            'appointments' => $appointments,
        ]);
    }

    /** Calendar booking page. */
    public function create()
    {
        return Inertia::render('Patients/Appointment/Calendar', [
            'dentists' => $this->availability->dentists()
                ->map(fn (User $d) => ['id' => $d->id, 'name' => $d->name])
                ->values(),
            'procedures' => Procedure::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** JSON: calendar colours for one month, plus this patient's own bookings. */
    public function calendarData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'dentist_id' => ['nullable', 'exists:users,id'],
        ]);

        return response()->json([
            'days' => $this->availability->monthStatuses($data['month'], $data['dentist_id'] ?? null),
            'mine' => $this->myMonthMarks($data['month']),
        ]);
    }

    /** JSON: open times for one day. Any dentist's open time counts unless one is chosen. */
    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'dentist_id' => ['nullable', 'exists:users,id'],
        ]);

        $times = [];

        foreach ($this->availability->dentists($data['dentist_id'] ?? null) as $dentist) {
            foreach ($this->availability->freeTimes($dentist, $data['date']) as $time) {
                $times[$time] = true;
            }
        }

        $list = array_keys($times);
        sort($list);

        return response()->json(['times' => $list]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'procedure_id' => ['required', 'exists:procedures,id'],
            'dentist_id' => ['nullable', 'exists:users,id'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Candidate ids are read before the transaction starts, so the locking read below is the first read inside it.
        $candidateIds = $this->availability->dentists($data['dentist_id'] ?? null)->pluck('id');

        $booked = DB::transaction(function () use ($data, $candidateIds) {
            // Lock every candidate dentist so two patients can't take the same slot at once.
            User::whereIn('id', $candidateIds)->orderBy('id')->lockForUpdate()->get();

            $dentist = $this->availability->pickDentist(
                $data['appointment_date'],
                $data['appointment_time'],
                $data['dentist_id'] ?? null,
            );

            if (! $dentist) {
                return false;
            }

            Appointment::create([
                'patient_id' => auth('patient')->id(),
                'procedure_id' => $data['procedure_id'],
                'dentist_id' => $dentist->id,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'description' => $data['description'] ?? null,
                'status' => AppointmentStatusEnum::Scheduled->value,
                'is_approve' => IsApproveEnum::Pending->value,
            ]);

            return true;
        });

        if (! $booked) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => 'That time is no longer available. Please choose another.']);
        }

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', 'Appointment request sent. The clinic will confirm it.');
    }

    /** Date => mark for this patient's own bookings in the month. reschedule > pending > confirmed. */
    private function myMonthMarks(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $rows = Appointment::where('patient_id', auth('patient')->id())
            ->whereBetween('appointment_date', [
                $start->toDateString(),
                $start->copy()->endOfMonth()->toDateString(),
            ])
            ->whereNotIn('status', [
                AppointmentStatusEnum::Cancelled->value,
                AppointmentStatusEnum::Completed->value,
            ])
            ->get(['appointment_date', 'status', 'is_approve']);

        $rank = ['confirmed' => 1, 'pending' => 2, 'reschedule' => 3];
        $marks = [];

        foreach ($rows as $row) {
            $status = $this->value($row->status);
            $approve = $this->value($row->is_approve);

            $mark = match (true) {
                $status === AppointmentStatusEnum::Rescheduled->value
                    || $approve === IsApproveEnum::PendingReScheduled->value => 'reschedule',
                $approve === IsApproveEnum::Pending->value => 'pending',
                $approve === IsApproveEnum::Approve->value => 'confirmed',
                default => null,
            };

            if ($mark === null) {
                continue;
            }

            $date = Carbon::parse($row->appointment_date)->toDateString();

            if (! isset($marks[$date]) || $rank[$mark] > $rank[$marks[$date]]) {
                $marks[$date] = $mark;
            }
        }

        return $marks;
    }

    /** Works whether or not the model casts the column to an enum. */
    private function value(mixed $v): ?string
    {
        return $v instanceof \BackedEnum ? $v->value : $v;
    }
}