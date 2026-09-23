<?php

namespace App\Services;

use App\Models\TreatmentPrescriptionDetails;
use App\Enums\AppointmentStatusEnum;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;




class InventoryService
{






    function inventoryStore(array $data)
    {

        try {

            return DB::transaction(
                function () use ($data) {

                    // this is a service becuase it needs to have stock policy, it pretty much check stock at all times
    
                    $tr = new Inventory();
                    //we dont really need the file since all the column inventory has is staff_id which i dont want the client-side to input
                    $tr->staff_id = auth()->id();
                    $tr->save();


                    // so this is all we needed
                    foreach ($data['itemList'] as $listItem) {
                        $tr->inventoryStock()->create([
                            'inventory_item_id' => $listItem['item_id'],
                            'quantity' => $listItem['quantity'],
                        ]);
                        // dd($tr);
    
                    }   

                    // dd($tr, $tr->inventoryStock());

                    return $tr;


                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            //   ask what is this for
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error($th);
            }
        }



    }


    // am honestly just using what i already have beucas ei cant be bother to type it out all over again adn i dont wnat to use ai to generate this since it just give me something wrong or annoying



    function inventoryUpdate(Inventory $inv, array $data)
    {

        try {

            return DB::transaction(
                function () use ($inv, $data) {


                    $inv->fill($data['inventory']);
                    $inv->staff_id = auth()->id(); //i want this so that when it is edit it will tak the id of who ever edit
                    $inv->save();


                    foreach ($data['itemList'] as $listItem) {
                        $inv->inventoryStock()->updateOrCreate([
                            'inventory_item_id' => $listItem['item_id'],
                            'quantity' => $listItem['quantity'],
                        ]);
                    }
                    return $inv;


                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            //   ask what is this for
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error($th);
            }
        }



    }








    // when delte happens:
// 1.reverse the reduction on stock by deleting the record of invnetoryItem or setting them to is_deleted, just let this auto since we are doing pattern 1 or live data
// 2.appointment status cancelled
// 3.prescriptionlist and treatment needs to be delete

    // test:
// 1.when deleting we need to do db check if prescriptionlist is actually gone along with Treatment
// 2.check current stock if it return, or in this case just check if inventoryItem is delete, since we have no way to know how many it is or rather is not needed 
// 3.check if appointment status is updated properly
    function treatmentDelete(Treatment $treatment)
    {



        try {
            return DB::transaction(function () use ($treatment) {



            });
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
























    //     // we need ot figure out what form of data will this recieve

    //     // ok we will revice data in json form, or object, since it is better for scale an its
//     // is more maintable since we have single source, but honestly thier is likely no choice
//     // it grow to the point it will be a problem if we put it in different var but i like that 
//     // object array we get to laern more, of course if i mess up i will just go back to what i know
// // $data=[{
// //     inventory=>[type=>IN,
// //     encoder_id=>3,
// //     inventory_type=>,
// // ],
// //     productList =>[product1QTY=>1,product2QTY=>1,,product3QTY=>1,],
// //     sale=>[branch_id=>1,inventory_id=>id,shift=>openning,cash_amount=>50,gcash_amount=>50]
// // }]
//     function InventoryOUT(array $data)
//     {
//         // $data=['branch_id'=>1,'ingredient_id'=>1,'quantity'=>10]

    //         try {

    //             return DB::transaction(
//                 function () use ($data) {

    //                     //FAULT-TOLERANCE sicne this is here we might as will use it to check, if i fail whole thing should stop
//                     //ASK-YOURSELF we will have validation for branch:exist and that is enough so we dont really need the find or fila
//                     //we can use just fine, but it is  not bad to use findorfail but if it has performance added on top then we weill use find()
//                     $branch = Branch::findOrFail($data['branch_id']);
//                     $inv = new Inventory();
//                     $inv->fill($data['inventory']);

    //                     $inv->encoder_id = auth()->id();
//                     $inv->inventory_type = InOutType::Out->value;
//                     $inv->branch_id = $branch->id; //ensure only insert if it exist, instead fo data[branch_id] which has possiblity of not existing
//                     $inv->save();

    //                     $sale = new Sale();
//                     $sale->fill($data['sale']);

    //                     //ASK-YOURSELF i dont know if this will work since we are doing it in same transaction
//                     $sale->branch_id = $branch->id; //ensure only insert if it exist, instead fo data[branch_id] which has possiblity of not existing, also becuase input is not validated;
//                     $sale->inventory_id = $inv->id;
//                     $sale->save();


    //                     foreach ($data['productList'] as $listItem) {
//                         $productID = $listItem['product_id'];
//                         $qty = $listItem['quantity'];

    //                         // FAULT-TOLERANCE - this just for safe check, can be remove, ask 
//                         // ASK-YOURSELF later, because i suspect we dont need this , because we expect item to be already thier
//                         //OPTIMIZE - seem redundant to get the whole model 
//                         $existting = $branch->products()->where('product_id', $productID)->first();
//                         if ($existting) {
//                             $branch->products()->updateExistingPivot( //update to follow pattern 2
//                                 $productID,
//                                 [
//                                     'quantity' => $existting->pivot->quantity - $qty,
//                                     //with need ot add withpivot for model 
//                                 ],
//                             );

    //                         } else {
//                             dd($existting, 'bad');

    //                             //we need to throw error here since , in here we expect that an item already exist in this branch
//                             //if not then something is wrong, we should'nt even be able to make this request
//                             throw new Error('development: this error is bad, INVENTORYOUT, unexpected error');
//                         }

    //                     }

    //                     return $inv; //no reason to do this other than confirm operation ws successfull


    //                 }
//             );

    //         } catch (\Throwable $th) {
//             if (DB::transactionLevel() > 0) {
//                 DB::rollBack();
//             }

    //             //   ask what is this for
//             if (
//                 !($th instanceof \Illuminate\Validation\ValidationException) &&
//                 !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
//             ) {
//                 Log::error($th);
//             }
//         }



    //     }


}


// finish this today so that we can move on to to pern which should be easier, learn isolation for it   

// figure out how you will send data here first and fins the fucing pivot pk