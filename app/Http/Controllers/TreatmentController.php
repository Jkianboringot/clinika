<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Services\TreatmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

//process of this is it cant be created without appointment or item attach to it both must be true before it could be create, patient will be taken from appointment and this treatment will be yhe one to initiate the reduction of stock


class TreatmentController extends Controller
{
    public function __construct(protected TreatmentService $treatmentService)
    {
    }
    
    public function index(){
        $t=Treatment::all();
        return Inertia::render('Treatments/Index', ['treatments'=>$t]);
    }



//this needs to start from appointment and all we need to do is click it and put as in create of treatment or we could just show it in drop-down 

//this needs the appointment info such as patient and id of appointment,procedure, item, the treatment description is not needed since we dont need to show data on that,
public function create(Appointment $appointment){
    
    
    return Inertia::render('Treatments/Create', ['appointment'=>$appointment,
        'items'=>InventoryItem::all(),
        'procedures'=>Procedure::all()
    ]);
    
}


public function store($request){
    
 $data=$request->validated();   
    $treatment=$this->treatmentService->treatmentStore([
        
    ]
        
    )
    
    
}
 

}