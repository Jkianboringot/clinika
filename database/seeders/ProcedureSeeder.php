<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $procedures = [
            // Preventive / diagnostic
            ['name' => 'Oral Prophylaxis (Cleaning)', 'duration' => 30, 'price' => 800],
            ['name' => 'Dental Consultation', 'duration' => 15, 'price' => 300],
            ['name' => 'Oral Examination & Charting', 'duration' => 20, 'price' => 350],
            ['name' => 'Dental X-Ray (Periapical)', 'duration' => 15, 'price' => 400],
            ['name' => 'Panoramic X-Ray', 'duration' => 20, 'price' => 1200],
            ['name' => 'Fluoride Treatment', 'duration' => 15, 'price' => 500],
            ['name' => 'Pit and Fissure Sealant', 'duration' => 20, 'price' => 600],
            ['name' => 'Oral Prophylaxis with Polishing', 'duration' => 45, 'price' => 1000],

            // Restorative
            ['name' => 'Composite Filling (1 Surface)', 'duration' => 30, 'price' => 1200],
            ['name' => 'Composite Filling (2 Surfaces)', 'duration' => 45, 'price' => 1800],
            ['name' => 'Composite Filling (3+ Surfaces)', 'duration' => 60, 'price' => 2500],
            ['name' => 'Amalgam Filling', 'duration' => 30, 'price' => 900],
            ['name' => 'Glass Ionomer Restoration', 'duration' => 30, 'price' => 1000],
            ['name' => 'Temporary Filling', 'duration' => 15, 'price' => 500],
            ['name' => 'Inlay/Onlay Restoration', 'duration' => 60, 'price' => 6000],

            // Endodontic
            ['name' => 'Root Canal Treatment (Anterior)', 'duration' => 90, 'price' => 8000],
            ['name' => 'Root Canal Treatment (Premolar)', 'duration' => 90, 'price' => 9000],
            ['name' => 'Root Canal Treatment (Molar)', 'duration' => 120, 'price' => 12000],
            ['name' => 'Pulpotomy (Pediatric)', 'duration' => 45, 'price' => 3000],
            ['name' => 'Pulp Capping', 'duration' => 30, 'price' => 1500],
            ['name' => 'Root Canal Retreatment', 'duration' => 120, 'price' => 14000],

            // Surgical / extraction
            ['name' => 'Simple Tooth Extraction', 'duration' => 30, 'price' => 1500],
            ['name' => 'Surgical Tooth Extraction', 'duration' => 60, 'price' => 3500],
            ['name' => 'Impacted Wisdom Tooth Extraction', 'duration' => 90, 'price' => 8000],
            ['name' => 'Alveoloplasty', 'duration' => 60, 'price' => 5000],
            ['name' => 'Frenectomy', 'duration' => 45, 'price' => 4000],
            ['name' => 'Biopsy (Soft Tissue)', 'duration' => 45, 'price' => 3500],
            ['name' => 'Incision and Drainage of Abscess', 'duration' => 30, 'price' => 2000],

            // Periodontal
            ['name' => 'Scaling and Root Planing (Per Quadrant)', 'duration' => 60, 'price' => 2500],
            ['name' => 'Gingivectomy', 'duration' => 60, 'price' => 4000],
            ['name' => 'Periodontal Flap Surgery', 'duration' => 90, 'price' => 8000],
            ['name' => 'Gum Depigmentation', 'duration' => 60, 'price' => 5000],

            // Prosthodontic
            ['name' => 'Complete Denture (Upper & Lower)', 'duration' => 60, 'price' => 15000],
            ['name' => 'Partial Denture (Acrylic)', 'duration' => 60, 'price' => 8000],
            ['name' => 'Partial Denture (Flexible/Valplast)', 'duration' => 60, 'price' => 12000],
            ['name' => 'Denture Relining', 'duration' => 45, 'price' => 3000],
            ['name' => 'Denture Repair', 'duration' => 30, 'price' => 1500],
            ['name' => 'Dental Crown (Porcelain Fused to Metal)', 'duration' => 60, 'price' => 8000],
            ['name' => 'Dental Crown (All-Ceramic/Zirconia)', 'duration' => 60, 'price' => 12000],
            ['name' => 'Dental Bridge (Per Unit)', 'duration' => 60, 'price' => 9000],
            ['name' => 'Dental Implant Placement', 'duration' => 120, 'price' => 45000],
            ['name' => 'Implant-Supported Crown', 'duration' => 60, 'price' => 35000],

            // Orthodontic
            ['name' => 'Braces Consultation', 'duration' => 30, 'price' => 500],
            ['name' => 'Metal Braces (Full Set)', 'duration' => 60, 'price' => 35000],
            ['name' => 'Ceramic Braces (Full Set)', 'duration' => 60, 'price' => 50000],
            ['name' => 'Orthodontic Adjustment (Monthly)', 'duration' => 20, 'price' => 800],
            ['name' => 'Clear Aligners (Per Set)', 'duration' => 45, 'price' => 60000],
            ['name' => 'Space Maintainer (Pediatric)', 'duration' => 30, 'price' => 3000],

            // Cosmetic
            ['name' => 'Teeth Whitening (In-Office)', 'duration' => 60, 'price' => 6000],
            ['name' => 'Teeth Whitening (Take-Home Kit)', 'duration' => 20, 'price' => 3500],
            ['name' => 'Dental Veneer (Porcelain)', 'duration' => 60, 'price' => 15000],
            ['name' => 'Dental Veneer (Composite)', 'duration' => 45, 'price' => 5000],
            ['name' => 'Smile Makeover Consultation', 'duration' => 30, 'price' => 500],

            // Pediatric
            ['name' => 'Pediatric Dental Checkup', 'duration' => 20, 'price' => 400],
            ['name' => 'Stainless Steel Crown (Pediatric)', 'duration' => 30, 'price' => 2500],
            ['name' => 'Nerve Treatment (Pediatric Pulpectomy)', 'duration' => 45, 'price' => 3500],

            // TMJ / other
            ['name' => 'Night Guard / Occlusal Splint', 'duration' => 30, 'price' => 4000],
            ['name' => 'TMJ Consultation', 'duration' => 30, 'price' => 600],
            ['name' => 'Mouthguard (Sports)', 'duration' => 30, 'price' => 2500],
        ];

        $rows = collect($procedures)
            ->unique('name')
            ->map(function ($proc) {
                return [
                    'name' => $proc['name'],
                    'description' => fake()->boolean(60) ? fake()->sentence(10) : null,
                    'duration' => $proc['duration'],
                    'price' => $proc['price'],
                    'is_active' => fake()->boolean(90), // most active, a few disabled/retired
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values();

        $rows->chunk(20)->each(function ($chunk) {
            Procedure::insert($chunk->toArray());
        });

        $this->command->info("Seeded {$rows->count()} dental procedures.");
    }
}