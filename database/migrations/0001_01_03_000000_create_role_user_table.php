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
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->constrained();
           $table->primary(['user_id', 'role_id']);
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
        Schema::dropIfExists('role_user');
    }
};
