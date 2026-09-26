<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Consumables / disposables
            ['item_name' => 'Disposable Dental Bibs', 'minimum_stock' => 100],
            ['item_name' => 'Cotton Rolls', 'minimum_stock' => 200],
            ['item_name' => 'Gauze Pads 2x2', 'minimum_stock' => 150],
            ['item_name' => 'Gauze Pads 4x4', 'minimum_stock' => 150],
            ['item_name' => 'Disposable Mouth Mirrors', 'minimum_stock' => 100],
            ['item_name' => 'Disposable Saliva Ejectors', 'minimum_stock' => 200],
            ['item_name' => 'Disposable Syringe Tips', 'minimum_stock' => 100],
            ['item_name' => 'Cotton Pellets', 'minimum_stock' => 200],
            ['item_name' => 'Dappen Dish Disposable Cups', 'minimum_stock' => 100],
            ['item_name' => 'Articulating Paper', 'minimum_stock' => 20],

            // PPE
            ['item_name' => 'Nitrile Examination Gloves (S)', 'minimum_stock' => 300],
            ['item_name' => 'Nitrile Examination Gloves (M)', 'minimum_stock' => 300],
            ['item_name' => 'Nitrile Examination Gloves (L)', 'minimum_stock' => 300],
            ['item_name' => 'Surgical Face Masks', 'minimum_stock' => 300],
            ['item_name' => 'N95 Respirator Masks', 'minimum_stock' => 100],
            ['item_name' => 'Disposable Face Shields', 'minimum_stock' => 50],
            ['item_name' => 'Disposable Head Caps', 'minimum_stock' => 100],
            ['item_name' => 'Disposable Shoe Covers', 'minimum_stock' => 100],
            ['item_name' => 'Plastic Aprons', 'minimum_stock' => 50],

            // Anesthetics & pharmaceuticals
            ['item_name' => 'Lidocaine 2% Cartridges', 'minimum_stock' => 50],
            ['item_name' => 'Articaine 4% Cartridges', 'minimum_stock' => 50],
            ['item_name' => 'Topical Anesthetic Gel', 'minimum_stock' => 10],
            ['item_name' => 'Dental Anesthesia Needles (27G)', 'minimum_stock' => 100],
            ['item_name' => 'Dental Anesthesia Needles (30G)', 'minimum_stock' => 100],

            // Restorative materials
            ['item_name' => 'Composite Resin Syringes (A2 Shade)', 'minimum_stock' => 10],
            ['item_name' => 'Composite Resin Syringes (A3 Shade)', 'minimum_stock' => 10],
            ['item_name' => 'Glass Ionomer Cement', 'minimum_stock' => 5],
            ['item_name' => 'Dental Amalgam Capsules', 'minimum_stock' => 20],
            ['item_name' => 'Bonding Agent', 'minimum_stock' => 5],
            ['item_name' => 'Etching Gel (37% Phosphoric Acid)', 'minimum_stock' => 5],
            ['item_name' => 'Temporary Filling Material (Cavit)', 'minimum_stock' => 5],
            ['item_name' => 'Calcium Hydroxide Paste', 'minimum_stock' => 5],
            ['item_name' => 'Dental Cement (Zinc Phosphate)', 'minimum_stock' => 5],
            ['item_name' => 'Matrix Bands', 'minimum_stock' => 50],
            ['item_name' => 'Wedges (Wooden)', 'minimum_stock' => 100],

            // Endodontic supplies
            ['item_name' => 'Endodontic Files (K-Files Assorted)', 'minimum_stock' => 30],
            ['item_name' => 'Gutta Percha Points', 'minimum_stock' => 50],
            ['item_name' => 'Paper Points', 'minimum_stock' => 100],
            ['item_name' => 'Root Canal Sealer', 'minimum_stock' => 5],
            ['item_name' => 'Irrigation Solution (Sodium Hypochlorite)', 'minimum_stock' => 10],
            ['item_name' => 'EDTA Gel', 'minimum_stock' => 5],

            // Extraction / surgical
            ['item_name' => 'Sterile Surgical Blades (#15)', 'minimum_stock' => 50],
            ['item_name' => 'Suture Kits (Silk 3-0)', 'minimum_stock' => 30],
            ['item_name' => 'Hemostatic Sponges', 'minimum_stock' => 30],
            ['item_name' => 'Bone Wax', 'minimum_stock' => 10],
            ['item_name' => 'Surgical Gauze Sponges', 'minimum_stock' => 100],

            // Prevention / hygiene
            ['item_name' => 'Fluoride Varnish', 'minimum_stock' => 10],
            ['item_name' => 'Prophylaxis Paste', 'minimum_stock' => 10],
            ['item_name' => 'Dental Sealants', 'minimum_stock' => 10],
            ['item_name' => 'Disposable Prophy Cups', 'minimum_stock' => 100],
            ['item_name' => 'Disposable Prophy Brushes', 'minimum_stock' => 100],
            ['item_name' => 'Toothbrushes (Patient Giveaway)', 'minimum_stock' => 100],
            ['item_name' => 'Fluoride Toothpaste (Sample Size)', 'minimum_stock' => 100],
            ['item_name' => 'Dental Floss', 'minimum_stock' => 100],
            ['item_name' => 'Mouthwash (Antiseptic)', 'minimum_stock' => 20],

            // Impression / prosthodontic
            ['item_name' => 'Alginate Impression Material', 'minimum_stock' => 10],
            ['item_name' => 'Impression Trays (Disposable)', 'minimum_stock' => 100],
            ['item_name' => 'Bite Registration Material', 'minimum_stock' => 10],
            ['item_name' => 'Dental Stone (Plaster)', 'minimum_stock' => 20],
            ['item_name' => 'Denture Adhesive', 'minimum_stock' => 10],
            ['item_name' => 'Denture Repair Kit', 'minimum_stock' => 5],

            // Sterilization / infection control
            ['item_name' => 'Sterilization Pouches', 'minimum_stock' => 200],
            ['item_name' => 'Autoclave Indicator Strips', 'minimum_stock' => 100],
            ['item_name' => 'Surface Disinfectant Spray', 'minimum_stock' => 20],
            ['item_name' => 'Disinfectant Wipes', 'minimum_stock' => 50],
            ['item_name' => 'Instrument Lubricant Spray', 'minimum_stock' => 10],
            ['item_name' => 'Barrier Film Rolls', 'minimum_stock' => 20],
            ['item_name' => 'Handpiece Cleaning Solution', 'minimum_stock' => 10],

            // Small equipment / accessories
            ['item_name' => 'Suction Tips', 'minimum_stock' => 100],
            ['item_name' => 'Air/Water Syringe Tips', 'minimum_stock' => 100],
            ['item_name' => 'Burs (Diamond Assorted)', 'minimum_stock' => 50],
            ['item_name' => 'Burs (Carbide Assorted)', 'minimum_stock' => 50],
            ['item_name' => 'Polishing Discs', 'minimum_stock' => 50],
            ['item_name' => 'Rubber Dam Sheets', 'minimum_stock' => 30],
            ['item_name' => 'Rubber Dam Clamps', 'minimum_stock' => 20],
            ['item_name' => 'X-Ray Sensor Sleeves', 'minimum_stock' => 100],
        ];

        // Give each entry a short filler description and dedupe against
        // any existing rows (item_name is unique) so re-running is safe.
        $rows = collect($items)
            ->unique('item_name')
            ->map(function ($item) {
                return [
                    'item_name' => $item['item_name'],
                    'description' => fake()->boolean(60) ? fake()->sentence(8) : null,
                    'minimum_stock' => $item['minimum_stock'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values();

        $rows->chunk(25)->each(function ($chunk) {
            InventoryItem::upsert(
                $chunk->toArray(),
                ['item_name'],                         // unique key to match on
                ['description', 'minimum_stock', 'updated_at'] // columns to update if it already exists
            );
        });

        $this->command->info("Seeded {$rows->count()} dental clinic inventory items.");
    }
}