<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medications = [
            // Analgesics / anti-inflammatory (post-extraction, general pain)
            ['name' => 'Mefenamic Acid 500mg', 'generic_name' => 'Mefenamic Acid', 'dosage_form' => 'Capsule', 'strength' => '500mg'],
            ['name' => 'Ibuprofen 400mg', 'generic_name' => 'Ibuprofen', 'dosage_form' => 'Tablet', 'strength' => '400mg'],
            ['name' => 'Paracetamol 500mg', 'generic_name' => 'Paracetamol', 'dosage_form' => 'Tablet', 'strength' => '500mg'],
            ['name' => 'Paracetamol 250mg/5ml', 'generic_name' => 'Paracetamol', 'dosage_form' => 'Syrup', 'strength' => '250mg/5ml'],
            ['name' => 'Celecoxib 200mg', 'generic_name' => 'Celecoxib', 'dosage_form' => 'Capsule', 'strength' => '200mg'],
            ['name' => 'Diclofenac Sodium 50mg', 'generic_name' => 'Diclofenac Sodium', 'dosage_form' => 'Tablet', 'strength' => '50mg'],
            ['name' => 'Naproxen 500mg', 'generic_name' => 'Naproxen', 'dosage_form' => 'Tablet', 'strength' => '500mg'],
            ['name' => 'Tramadol 50mg', 'generic_name' => 'Tramadol Hydrochloride', 'dosage_form' => 'Capsule', 'strength' => '50mg'],

            // Antibiotics (post-surgical, infection control — very common in dental Rx)
            ['name' => 'Amoxicillin 500mg', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg'],
            ['name' => 'Amoxicillin 250mg/5ml', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Syrup', 'strength' => '250mg/5ml'],
            ['name' => 'Co-Amoxiclav 625mg', 'generic_name' => 'Amoxicillin/Clavulanic Acid', 'dosage_form' => 'Tablet', 'strength' => '625mg'],
            ['name' => 'Clindamycin 300mg', 'generic_name' => 'Clindamycin', 'dosage_form' => 'Capsule', 'strength' => '300mg'],
            ['name' => 'Metronidazole 500mg', 'generic_name' => 'Metronidazole', 'dosage_form' => 'Tablet', 'strength' => '500mg'],
            ['name' => 'Azithromycin 500mg', 'generic_name' => 'Azithromycin', 'dosage_form' => 'Tablet', 'strength' => '500mg'],
            ['name' => 'Cephalexin 500mg', 'generic_name' => 'Cephalexin', 'dosage_form' => 'Capsule', 'strength' => '500mg'],
            ['name' => 'Doxycycline 100mg', 'generic_name' => 'Doxycycline', 'dosage_form' => 'Capsule', 'strength' => '100mg'],

            // Local anesthetics (dispensed/administered, sometimes listed as prescribable items too)
            ['name' => 'Lidocaine HCl 2% with Epinephrine', 'generic_name' => 'Lidocaine Hydrochloride', 'dosage_form' => 'Injection', 'strength' => '2%'],
            ['name' => 'Articaine HCl 4% with Epinephrine', 'generic_name' => 'Articaine Hydrochloride', 'dosage_form' => 'Injection', 'strength' => '4%'],
            ['name' => 'Benzocaine Topical Gel 20%', 'generic_name' => 'Benzocaine', 'dosage_form' => 'Gel', 'strength' => '20%'],

            // Antiseptics / mouth rinses
            ['name' => 'Chlorhexidine Gluconate 0.12% Mouthwash', 'generic_name' => 'Chlorhexidine Gluconate', 'dosage_form' => 'Mouthwash', 'strength' => '0.12%'],
            ['name' => 'Povidone-Iodine 10% Solution', 'generic_name' => 'Povidone-Iodine', 'dosage_form' => 'Solution', 'strength' => '10%'],
            ['name' => 'Hydrogen Peroxide 3% Rinse', 'generic_name' => 'Hydrogen Peroxide', 'dosage_form' => 'Rinse', 'strength' => '3%'],

            // Antifungals (denture stomatitis, oral candidiasis)
            ['name' => 'Nystatin Oral Suspension 100,000 units/ml', 'generic_name' => 'Nystatin', 'dosage_form' => 'Suspension', 'strength' => '100,000units/ml'],
            ['name' => 'Miconazole Oral Gel 2%', 'generic_name' => 'Miconazole', 'dosage_form' => 'Gel', 'strength' => '2%'],
            ['name' => 'Fluconazole 150mg', 'generic_name' => 'Fluconazole', 'dosage_form' => 'Capsule', 'strength' => '150mg'],

            // Antihistamines / anti-inflammatory adjuncts
            ['name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'dosage_form' => 'Tablet', 'strength' => '10mg'],
            ['name' => 'Loratadine 10mg', 'generic_name' => 'Loratadine', 'dosage_form' => 'Tablet', 'strength' => '10mg'],
            ['name' => 'Dexamethasone 0.5mg', 'generic_name' => 'Dexamethasone', 'dosage_form' => 'Tablet', 'strength' => '0.5mg'],
            ['name' => 'Prednisone 5mg', 'generic_name' => 'Prednisone', 'dosage_form' => 'Tablet', 'strength' => '5mg'],

            // Fluoride / remineralization
            ['name' => 'Sodium Fluoride 0.05% Rinse', 'generic_name' => 'Sodium Fluoride', 'dosage_form' => 'Rinse', 'strength' => '0.05%'],
            ['name' => 'Sodium Fluoride Varnish 5%', 'generic_name' => 'Sodium Fluoride', 'dosage_form' => 'Varnish', 'strength' => '5%'],

            // Vitamins / adjunct healing
            ['name' => 'Vitamin C 500mg', 'generic_name' => 'Ascorbic Acid', 'dosage_form' => 'Tablet', 'strength' => '500mg'],
            ['name' => 'Multivitamins with Zinc', 'generic_name' => 'Multivitamin/Zinc Complex', 'dosage_form' => 'Tablet', 'strength' => 'N/A'],

            // Sedatives (occasionally used for anxious patients pre-procedure)
            ['name' => 'Diazepam 5mg', 'generic_name' => 'Diazepam', 'dosage_form' => 'Tablet', 'strength' => '5mg'],
            ['name' => 'Midazolam 15mg', 'generic_name' => 'Midazolam', 'dosage_form' => 'Tablet', 'strength' => '15mg'],
        ];

        $rows = collect($medications)
            ->unique('name')
            ->map(function ($med) {
                return array_merge($med, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            })
            ->values();

        $rows->chunk(20)->each(function ($chunk) {
            Medication::insert($chunk->toArray());
        });

        $this->command->info("Seeded {$rows->count()} dental clinic medications.");
    }
}