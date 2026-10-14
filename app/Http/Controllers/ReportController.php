<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TYPES = [
        ['value' => 'patients',     'label' => 'Patient Records'],
        ['value' => 'appointments', 'label' => 'Appointments'],
        ['value' => 'treatments',   'label' => 'Treatments'],
        ['value' => 'inventory',    'label' => 'Inventory'],
    ];

    // Only the columns Patient::full_name needs
    private const PATIENT_NAME_COLS = 'id,first_name,middle_name,last_name';

    public function index(Request $request)
    {
        $type    = $this->type($request);
        $filters = $this->filters($request);
        [$columns, $rows, $summary] = $this->build($type, $filters);

        return Inertia::render('Report/Index', [
            'types'       => self::TYPES,
            'type'        => $type,
            'filters'     => $filters,
            'columns'     => $columns,
            'rows'        => $rows,
            'summary'     => $summary,
            'statuses'    => collect(AppointmentStatusEnum::cases())
                ->map(fn ($c) => ['value' => $c->value, 'label' => $c->name])->values(),
            'generatedAt' => now()->format('M d, Y h:i A'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // TODO: re-enable before going live (CSV contains medical info)
        // abort_unless($request->user()->can('reports.download'), 403);

        $type    = $this->type($request);
        $filters = $this->filters($request);
        [$columns, $rows] = $this->build($type, $filters);

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads it correctly
            fputcsv($out, array_column($columns, 'label'), ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv(
                    $out,
                    array_map(fn ($c) => $this->csvSafe($row[$c['key']] ?? ''), $columns),
                    ',', '"', '\\'
                );
            }
            fclose($out);
        }, "{$type}-report-" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ---------------------------------------------------------------

    private function type(Request $request): string
    {
        $type = $request->input('type', 'patients');
        abort_unless(in_array($type, array_column(self::TYPES, 'value'), true), 404);

        if ($type === 'inventory') {
            abort_unless($request->user()->can('inventory.view'), 403);
        }

        return $type;
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'from'   => $request->input('from') ?: null,
            'to'     => $request->input('to') ?: null,
            'status' => $request->input('status') ?: null,
            'search' => $request->input('search') ?: null,
        ];
    }

    private function build(string $type, array $f): array
    {
        return match ($type) {
            'patients'     => $this->patients($f),
            'appointments' => $this->appointments($f),
            'treatments'   => $this->treatments($f),
            'inventory'    => $this->inventory($f),
        };
    }

    /** Neutralise Excel formula injection (=, +, -, @ at the start of a text cell) */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    // ---------------------- reports ----------------------

    private function patients(array $f): array
    {
        $apptFilter = fn ($q) => $q
            ->when($f['from'], fn ($q, $v) => $q->whereDate('appointment_date', '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate('appointment_date', '<=', $v))
            ->when($f['status'], fn ($q, $v) => $q->where('status', $v));

        $patients = Patient::query()
            ->searchName($f['search'])
            ->withCount(['appointments as visits' => $apptFilter])
            ->withMax(['appointments as last_visit' => $apptFilter], 'appointment_date')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $columns = [
            ['key' => 'name',       'label' => 'Patient'],
            ['key' => 'age',        'label' => 'Age', 'align' => 'right'],
            ['key' => 'gender',     'label' => 'Gender'],
            ['key' => 'contact',    'label' => 'Contact'],
            ['key' => 'allergies',  'label' => 'Allergies'],
            ['key' => 'visits',     'label' => 'Visits', 'align' => 'right'],
            ['key' => 'last_visit', 'label' => 'Last Visit'],
            ['key' => 'status',     'label' => 'Status'],
        ];

        $rows = $patients->map(fn ($p) => [
            'name'       => $p->full_name,
            'age'        => \Carbon\Carbon::parse($p->date_of_birth)->age,
            'gender'     => $p->gender,
            'contact'    => $p->contact_number ?? '—',
            'allergies'  => $p->allergies ?? '—',
            'visits'     => $p->visits,
            'last_visit' => $p->last_visit ?? '—',
            'status'     => $p->is_active?->name ?? (string) $p->getRawOriginal('is_active'),
        ])->values()->all();

        $summary = [
            ['label' => 'Total Patients',  'value' => $patients->count()],
            ['label' => 'Active Patients', 'value' => $patients->where('is_active', IsActiveEnum::ACTIVE)->count()],
            ['label' => 'Total Visits',    'value' => (int) $patients->sum('visits')],
        ];

        return [$columns, $rows, $summary];
    }

    private function appointments(array $f): array
    {
        $appointments = Appointment::with(['patient:' . self::PATIENT_NAME_COLS, 'procedure', 'staff'])
            ->when($f['from'], fn ($q, $v) => $q->whereDate('appointment_date', '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate('appointment_date', '<=', $v))
            ->when($f['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($f['search'], fn ($q, $v) => $q->whereHas('patient', fn ($p) => $p->searchName($v)))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $columns = [
            ['key' => 'date',      'label' => 'Date'],
            ['key' => 'time',      'label' => 'Time'],
            ['key' => 'patient',   'label' => 'Patient'],
            ['key' => 'procedure', 'label' => 'Procedure'],
            ['key' => 'staff',     'label' => 'Staff'],
            ['key' => 'status',    'label' => 'Status'],
        ];

        $rows = $appointments->map(fn ($a) => [
            'date'      => $a->appointment_date,
            'time'      => $a->appointment_time,
            'patient'   => $a->patient->full_name,
            'procedure' => $a->procedure->name ?? '—',
            'staff'     => $a->staff->name ?? '—',
            'status'    => $a->status,
        ])->values()->all();

        $summary = [['label' => 'Total', 'value' => $appointments->count()]];
        foreach ($appointments->groupBy('status') as $status => $group) {
            $summary[] = ['label' => (string) $status, 'value' => $group->count()];
        }

        return [$columns, $rows, $summary];
    }

    private function treatments(array $f): array
    {
        $treatments = Treatment::with([
                'doctor',
                'appointment.patient:' . self::PATIENT_NAME_COLS,
                'appointment.procedure',
                'treatmentInventories.items',
            ])
            ->whereHas('appointment', fn ($q) => $q
                ->when($f['from'], fn ($q, $v) => $q->whereDate('appointment_date', '>=', $v))
                ->when($f['to'], fn ($q, $v) => $q->whereDate('appointment_date', '<=', $v))
                ->when($f['status'], fn ($q, $v) => $q->where('status', $v))
                ->when($f['search'], fn ($q, $v) => $q->whereHas('patient', fn ($p) => $p->searchName($v))))
            ->latest()
            ->get();

        $columns = [
            ['key' => 'date',      'label' => 'Date'],
            ['key' => 'patient',   'label' => 'Patient'],
            ['key' => 'procedure', 'label' => 'Procedure'],
            ['key' => 'doctor',    'label' => 'Doctor'],
            ['key' => 'duration',  'label' => 'Duration (min)', 'align' => 'right'],
            ['key' => 'summary',   'label' => 'Summary'],
            ['key' => 'items',     'label' => 'Items Used'],
            ['key' => 'follow_up', 'label' => 'Follow-up'],
        ];

        $rows = $treatments->map(fn ($t) => [
            'date'      => $t->appointment->appointment_date,
            'patient'   => $t->appointment->patient->full_name,
            'procedure' => $t->appointment->procedure->name ?? '—',
            'doctor'    => $t->doctor->name ?? '—',
            'duration'  => $t->duration_minutes ?? 0,
            'summary'   => $t->summary ?? '—',
            'items'     => $t->treatmentInventories
                ->map(fn ($ti) => ($ti->items->item_name ?? '?') . ' × ' . ($ti->quantity + 0))
                ->implode(', ') ?: '—',
            'follow_up' => $t->follow_up_schedule ?? '—',
        ])->values()->all();

        $summary = [
            ['label' => 'Treatments',    'value' => $treatments->count()],
            ['label' => 'Total Minutes', 'value' => (int) $treatments->sum('duration_minutes')],
        ];

        return [$columns, $rows, $summary];
    }

    private function inventory(array $f): array
    {
        $period = fn ($q) => $q
            ->when($f['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

        $items = InventoryItem::query()
            ->withSum('inventoryStock as all_added', 'quantity')
            ->withSum('treatmentInventories as all_used', 'quantity')
            ->withSum(['inventoryStock as period_added' => $period], 'quantity')
            ->withSum(['treatmentInventories as period_used' => $period], 'quantity')
            ->when($f['search'], fn ($q, $v) => $q->where('item_name', 'like', "%{$v}%"))
            ->orderBy('item_name')
            ->get();

        $columns = [
            ['key' => 'item',      'label' => 'Item'],
            ['key' => 'added',     'label' => 'Added', 'align' => 'right'],
            ['key' => 'used',      'label' => 'Used', 'align' => 'right'],
            ['key' => 'remaining', 'label' => 'Remaining', 'align' => 'right'],
            ['key' => 'minimum',   'label' => 'Minimum', 'align' => 'right'],
            ['key' => 'status',    'label' => 'Status'],
        ];

        $low = 0;
        $rows = $items->map(function ($i) use (&$low) {
            $remaining = max(0, ($i->all_added ?? 0) - ($i->all_used ?? 0)); // all-time
            $isLow = $i->minimum_stock !== null && $remaining <= $i->minimum_stock;
            $low += $isLow ? 1 : 0;

            return [
                'item'      => $i->item_name,
                'added'     => (float) ($i->period_added ?? 0),
                'used'      => (float) ($i->period_used ?? 0),
                'remaining' => (float) $remaining,
                'minimum'   => $i->minimum_stock !== null ? (float) $i->minimum_stock : '—',
                'status'    => $isLow ? 'LOW STOCK' : 'OK',
            ];
        })->values()->all();

        $summary = [
            ['label' => 'Items',     'value' => $items->count()],
            ['label' => 'Low Stock', 'value' => $low],
        ];

        return [$columns, $rows, $summary];
    }
}