<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function create()
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('Appointments/Create', []);
    }

    public function store(AppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $request->validated();

        Appointment::create($request->all());
        return redirect()->route('appointments.index')->with('message', 'Appointment Created Successfully');
    }

    public function destroy(Request $request)
    {
        $appointment = Appointment::findOrFail($request->id);
        $this->authorize('delete', $appointment);

        $appointment->deleteOrFail();
        return redirect()->route('appointments.index')->with('message', 'Appointment Deleted Successfully');
    }

    public function edit(Request $request)
    {
        $p = Appointment::findOrFail($request->id);
        $this->authorize('view', $p);

        return Inertia::render('Appointments/Edit', ['appointments' => $p]);
    }

    public function update(AppointmentRequest $request)
    {
        $p = Appointment::findOrFail($request->id);
        $this->authorize('update', $p);

        $request->validated();
        $p->update($request->all());

        return redirect()->route('appointments.index')->with('message', 'Appointment Updated Successfully');
    }

    public function index(SearchRequest $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validated();
        $appointments = Appointment::query()
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['search']),
        ]);
    }
}