<?php

namespace App\Services;

use App\Enums\InOutType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Sale;
use Error;
use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;
use function Livewire\store;

class InventoryService
{

    // this needs to do create for sale,payroll, and add_ingredeint✅


    // let focus on just making inventory

    //step:
    //1. Inventory($data) will recieve data as already validated
    //2. it save data and sync but it must go to check first
    // -data is just save in here but the validation happen before, check 
    // is possible if it is busines logic like below
    //check: it must check first if branch and ingredient alrady exist in pivot table


    // $data=[{
//     inventory=>[type=>IN,
//     encoder_id=>3,
//     inventory_type=>,
// ],
//     productList =>[product1QTY=>1,product2QTY=>1,,product3QTY=>1,],
// }]
    function InventoryIN(array $data)
    {
        // $data=['branch_id'=>1,'ingredient_id'=>1,'quantity'=>10]

        try {

            return DB::transaction(
                function () use ($data) {


                    //ASK-YOURSELF should i put this outside or inside
    
                    // TEST if this fail it should not run the foreact
                    $branch = Branch::findOrFail($data['branch_id']);
                    
                    $inv = new Inventory();
                    $inv->fill($data['inventory']);

                    $inv->encoder_id = auth()->id();
                    $inv->inventory_type = InOutType::In->value;
                    $inv->branch_id = $branch->id; //ensure only insert if it exist, instead fo data[branch_id] which has possiblity of not existing
    
                    $inv->save();

                    // we need for each because we will have collection of product
                    // and we just update the quantity, but we still need to do the check
                    // -also we need to re think the storing of pivot table id to inventory, like 
                    // is that really possible, think do we cerate the so called pivot first or later, 
                    // becuase if first then it is possible to store it in iventory if not we cant store 
                    // pivot id to inventory, becuase it needs to exist first,my plan is full of holes and 
                    // am suppose to watch all vid in tablet lets do that no more load fuck it, just connnect ot other`s
    

                    foreach ($data['productList'] as $listItem) {
                        $productID = $listItem['product_id'];
                        $qty = $listItem['quantity'];


                        // ASK-YOURSELF - i think this is n+1, becaus we are first finding, and updating, and we do this
                        //for each record, this was comfirm by ai, below commented code is ai solution, but i have not study it,
                        //just looking at it now
    
                        //                     $existingProducts = $branch->products()
                        // ->whereIn(
                        //     'product_id',
                        //     collect($data['productList'])->pluck('product_id')
                        // )
                        // ->get()
                        // ->keyBy('id');
                        //SOLUTION for n+1, instead of doing first, why not just get the whole collection save it then we dont 
                        //have to retake select all the time becasue we ahve it, but am not sure
                        $existting = $branch->products()->where('product_id', $productID)->first();
                        if ($existting) {
                            $branch->products()->updateExistingPivot(
                                $productID,
                                [
                                    'quantity' => $existting->pivot->quantity + $qty,
                                    //with need ot add withpivot for model 
                                ],
                            );
                            // dd($existting,'good');
    
                        } else {
                            // check if this has performance issues
                            $branch->product()->attach($productID, ['quantity' => $qty, 'branch_id' => $branch->id]);
                            // dd($existting,'badd');
    
                        }

                    }
                    // dd($inv,'asdfaef');
    
                    return $inv; //no reason to do this other than confirm operation ws successfull
    
                    // $inv->stock_movement->syncn([$inv]);
                    //REMOVE - implement what i did below but do it with eloquent 
                    //     $exist = DB::table('add_ingredients')
                    //         ->where('branch_id', $data->branch_id)
                    //         ->where('ingredient_id', $data->ingredient_id)
                    //         ->first();
    
                    //     if (!$exist) {
                    //         DB::table('add_ingredients')->insert($data);
                    //     }
                    // else{
                    //         DB::table('add_ingredients')
                    //          ->where('branch_id', $data->branch_id)
                    //         ->where('ingredient_id', $data->ingredient_id)
                    //         ->update(['quantity'=>$data->quantity]);
                    // }
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



    // we need ot figure out what form of data will this recieve

    // ok we will revice data in json form, or object, since it is better for scale an its
    // is more maintable since we have single source, but honestly thier is likely no choice
    // it grow to the point it will be a problem if we put it in different var but i like that 
    // object array we get to laern more, of course if i mess up i will just go back to what i know
// $data=[{
//     inventory=>[type=>IN,
//     encoder_id=>3,
//     inventory_type=>,
// ],
//     productList =>[product1QTY=>1,product2QTY=>1,,product3QTY=>1,],
//     sale=>[branch_id=>1,inventory_id=>id,shift=>openning,cash_amount=>50,gcash_amount=>50]
// }]
    function InventoryOUT(array $data)
    {
        // $data=['branch_id'=>1,'ingredient_id'=>1,'quantity'=>10]

        try {

            return DB::transaction(
                function () use ($data) {

                    //FAULT-TOLERANCE sicne this is here we might as will use it to check, if i fail whole thing should stop
                    //ASK-YOURSELF we will have validation for branch:exist and that is enough so we dont really need the find or fila
                    //we can use just fine, but it is  not bad to use findorfail but if it has performance added on top then we weill use find()
                    $branch = Branch::findOrFail($data['branch_id']);
                    $inv = new Inventory();
                    $inv->fill($data['inventory']);

                    $inv->encoder_id = auth()->id();
                    $inv->inventory_type = InOutType::Out->value;
                    $inv->branch_id = $branch->id; //ensure only insert if it exist, instead fo data[branch_id] which has possiblity of not existing
                    $inv->save();

                    $sale = new Sale();
                    $sale->fill($data['sale']);

                    //ASK-YOURSELF i dont know if this will work since we are doing it in same transaction
                    $sale->branch_id = $branch->id; //ensure only insert if it exist, instead fo data[branch_id] which has possiblity of not existing, also becuase input is not validated;
                    $sale->inventory_id = $inv->id;
                    $sale->save();


                    foreach ($data['productList'] as $listItem) {
                        $productID = $listItem['product_id'];
                        $qty = $listItem['quantity'];

                        // FAULT-TOLERANCE - this just for safe check, can be remove, ask 
                        // ASK-YOURSELF later, because i suspect we dont need this , because we expect product to be already thier
                        //OPTIMIZE - seem redundant to get the whole model 
                        $existting = $branch->products()->where('product_id', $productID)->first();
                        if ($existting) {
                            $branch->products()->updateExistingPivot( //update to follow pattern 2
                                $productID,
                                [
                                    'quantity' => $existting->pivot->quantity - $qty,
                                    //with need ot add withpivot for model 
                                ],
                            );

                        } else {
                            dd($existting, 'bad');

                            //we need to throw error here since , in here we expect that an product already exist in this branch
                            //if not then something is wrong, we should'nt even be able to make this request
                            throw new Error('development: this error is bad, INVENTORYOUT, unexpected error');
                        }

                    }

                    return $inv; //no reason to do this other than confirm operation ws successfull
    

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


}


// finish this today so that we can move on to to pern which should be easier, learn isolation for it   

// figure out how you will send data here first and fins the fucing pivot pk    