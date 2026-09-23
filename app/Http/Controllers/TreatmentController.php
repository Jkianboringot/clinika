<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatusEnum as EnumsAppointmentStatusEnum;
use App\Http\Requests\TreatmentRequest;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Services\TreatmentService;
use AppointmentStatusEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;

//process of this is it cant be created without appointment or item attach to it both must be true before it could be create, patient will be taken from appointment and this treatment will be yhe one to initiate the reduction of stock


class TreatmentController extends Controller
{
    public function __construct(protected TreatmentService $treatmentService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $treatments = Treatment::with('appointment.patient')
            ->when($search, fn($q) => $q->whereHas(
                'appointment.patient',
                fn($p) => $p->where('full_name', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($t) => [
                'id' => $t->id,
                'patient_name' => $t->appointment?->patient?->full_name,
                'appointment_id' => $t->appointment_id,
                'follow_up_schedule' => $t->follow_up_schedule,
                'duration_minutes' => $t->duration_minutes,
                'summary' => $t->summary,
                'created_at' => $t->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Treatments/Index', [
            'treatments' => $treatments,
            'filters' => ['search' => $search],
        ]);
    }



    //this needs to start from appointment and all we need to do is click it and put as in create of treatment or we could just show it in drop-down 

    //this needs the appointment info such as patient and id of appointment,procedure, item, the treatment description is not needed since we dont need to show data on that,
    public function create(Appointment $appointment)
    {


        return Inertia::render('Treatments/Create', [
            'appointment' => $appointment->load('patient'),
            'items' => InventoryItem::selectRaw('id, item_name as name')->orderBy('item_name')->get(),
            'procedures' => Procedure::all(),
            'medications' => \App\Models\Medication::all()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name ?? $m->medication_name ?? $m->generic_name ?? "Medication #{$m->id}",
            ]),
            'followUpEnum' => collect(EnumsAppointmentStatusEnum::cases())->map(fn($a) =>
                [
                    'value' => $a->value,
                    'label' => $a->value
                ])
        ]);

    }



    //just summaries it am not stupid,just check the migration
//data=[treatment:...$treatment,
//prescriptionlist:...$treatment_description,//the medicine is just the id
//appoitment:...$appointment
//treatmentItemList:...$items,


    //]

    // Route::post('{appointment}/create')
    public function store(Appointment $appointment, TreatmentRequest $request)
    {

        $data = $request->validated();
        $tr = $this->treatmentService->treatmentStore(
            $appointment,
            [
                'treatment' => [
                    'follow_up_schedule' => $data['follow_up_schedule'],
                    'duration_minutes' => $data['duration_minutes'],
                    'summary' => $data['summary'],
                ],
                'prescriptionlist' => $data['prescriptionlist'],//will already be return asan array, and its not like treatment where it will be givin one by one this one needs to come in as an array since it can be multiple, we could do tha same for treatment but that hard to understand
                'itemList' => $data['itemList'],
            ]

        );

        if (!$tr) {
            return back()->with('error', 'Failed to created treatment');
        }

        return redirect()->route('treatments.index')->with('message', 'Sucessfully created treatment');

    }

    public function edit(Treatment $treatment)
    {
        $treatment->load(['treatmentInventories', 'perscriptions']);

        // lets do it like this so that appointment can only be true to one treatment
        $appointment = Appointment::with('patient')->findOrFail($treatment->appointment_id);

        return Inertia::render('Treatments/Edit', [
            'treatment' => [
                'id' => $treatment->id,
                'follow_up_schedule' => $treatment->follow_up_schedule,
                'duration_minutes' => $treatment->duration_minutes,
                'summary' => $treatment->summary,
                'itemList' => $treatment->treatmentInventories->map(fn($ti) => [
                    'item_id' => $ti->inventory_item_id,
                    'quantity' => $ti->quantity,
                ]),
                'prescriptionlist' => $treatment->perscriptions->map(fn($p) => [
                    'medication_id' => $p->medication_id,
                    'dosage' => $p->dosage,
                    'frequency' => $p->frequency,
                    'duration_days' => $p->duration_days,
                ]),
            ],
            'appointment' => $appointment,
            'items' => \App\Models\InventoryItem::all()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->item_name,
            ]),
            'procedures' => \App\Models\Procedure::all()->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]),
            'medications' => \App\Models\Medication::all()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name ?? $m->medication_name ?? $m->generic_name ?? "Medication #{$m->id}",
            ]),
        ]);
    }



    public function update(Treatment $treatment, TreatmentRequest $request)
    {

        $data = $request->validated();
        $tr = $this->treatmentService->treatmentUpdate(
            $treatment,
            [
                'treatment' => [
                    'follow_up_schedule' => $data['follow_up_schedule'],//need to keep this since it needs outside data, not really i can remove it and it will work fine since we are doing model-binding
                ],
                'prescriptionlist' => $data['prescriptionlist'],//will already be return asan array, and its not like treatment where it will be givin one by one this one needs to come in as an array since it can be multiple, we could do tha same for treatment but that hard to understand
                'itemList' => $data['itemList'],
            ]

        );

        if (!$tr) {
            return back()->with('error', 'Failed to udpated treatment');
        }

        return redirect()->route('treatments.index')->with('message', 'Sucessfully udpated treatment');

    }


    public function delete(Treatment $treatment)
    {
        return;
    }



}