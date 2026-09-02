<?php

use App\Enums\BranchType;
use Filament\Livewire\Sidebar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('location',100)->nullable(); 

            // i want to make this unique since they use it alot and lets just levrage that thinking to if they want 
            // search they want it on the name not location
            $table->string('name',75)->unique(); 
            $table->string('branch_type',40)->default(BranchType::Branch->value);//dont really need this just mean first branch as main
            // $table->index('location','branches_location_index');
            // i index this becauase i will use locaton alot for filter , joins , and search espicailly in admin Sidebar
            // and cashier view for branch_ingredient, branch_order, etc

            
            $table->timestamps();

            //check what is joining with this by location, becuase from my knowlegde thier should be little 
            //locatoin maybe under 50 so maybe its not even worth making it indexes
            //same goes for branch_type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
