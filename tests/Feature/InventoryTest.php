<?php

use App\Enums\IsActiveEnum;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStockAddition;
use App\Models\Treatment;
use App\Models\TreatmentInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has procedures.view/create/update/delete

    $this->actingAs($this->user);
});

/**
 * Creates an inventory item. Used as the building block for stock tests.
 */
function makeInventoryTestItem(string $name, ?float $minimum = null, ?float $critical = null): InventoryItem
{
    return InventoryItem::create([
        'item_name' => $name,
        'minimum_stock' => $minimum,
        'critical_stock' => $critical,
    ]);
}

/**
 * Records usage of an item in a treatment. treatment_id is not mass assignable.
 */
function recordTreatmentUseForInventory(InventoryItem $item, float $quantity): void
{
    $row = new TreatmentInventory([
        'inventory_item_id' => $item->id,
        'quantity' => $quantity,
    ]);
    $row->treatment_id = Treatment::factory()->create()->id;
    $row->save();
}

dataset('invalidInventoryData', [
    'itemList missing' => [[], 'itemList'],
    'itemList empty' => [['itemList' => []], 'itemList'],
    'item_id missing' => [['itemList' => [['quantity' => 5]]], 'itemList.0.item_id'],
    'item_id does not exist' => [['itemList' => [['item_id' => 999999, 'quantity' => 5]]], 'itemList.0.item_id'],
    'quantity missing' => [['itemList' => [['item_id' => 'ITEM', 'quantity' => null]]], 'itemList.0.quantity'],
    'quantity zero' => [['itemList' => [['item_id' => 'ITEM', 'quantity' => 0]]], 'itemList.0.quantity'],
    'quantity negative' => [['itemList' => [['item_id' => 'ITEM', 'quantity' => -1]]], 'itemList.0.quantity'],
    'quantity must be numeric' => [['itemList' => [['item_id' => 'ITEM', 'quantity' => 'abc']]], 'itemList.0.quantity'],
    'quantity over maximum' => [['itemList' => [['item_id' => 'ITEM', 'quantity' => 100000]]], 'itemList.0.quantity'],
]);

// ---------------------------------------------------------------------------
// listing
// ---------------------------------------------------------------------------

it('lists inventories with the staff name and their items', function () {
    $item = makeInventoryTestItem('Dental Syringe');

    $this->post(route('inventories.store'), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 10]],
    ]);

    $response = $this->get(route('inventories.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Inventories/Index')
        ->has('inventories.data', 1)
        ->where('inventories.data.0.staff_name', $this->user->name)
        ->where('inventories.data.0.items.0.item_name', 'Dental Syringe')
        ->where('inventories.data.0.items.0.quantity', 10)
    );
});

it('filters inventories by item name', function () {
    $syringe = makeInventoryTestItem('Dental Syringe');
    $mirror = makeInventoryTestItem('Dental Mirror');

    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $syringe->id, 'quantity' => 5]]]);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $mirror->id, 'quantity' => 5]]]);

    $response = $this->get(route('inventories.index', ['search' => 'Syringe']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('inventories.data', 1)
        ->where('inventories.data.0.items.0.item_name', 'Dental Syringe')
    );
});

// ---------------------------------------------------------------------------
// creating
// ---------------------------------------------------------------------------

it('creates an inventory with valid data, redirects with a success message and records it in db', function () {
    $item = makeInventoryTestItem('Dental Syringe');

    $response = $this->post(route('inventories.store'), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 10]],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('inventories.index'));
    $response->assertSessionHas('message', 'Successfully created inventory');

    $this->assertDatabaseHas('inventories', ['staff_id' => $this->user->id]);
    $this->assertDatabaseHas('inventory_stock_additions', [
        'inventory_item_id' => $item->id,
        'quantity' => 10,
    ]);
});

it('stores many items in one inventory', function () {
    $syringe = makeInventoryTestItem('Dental Syringe');
    $mirror = makeInventoryTestItem('Dental Mirror');

    $this->post(route('inventories.store'), [
        'itemList' => [
            ['item_id' => $syringe->id, 'quantity' => 10],
            ['item_id' => $mirror->id, 'quantity' => 4],
        ],
    ]);

    $inventory = Inventory::firstOrFail();

    expect($inventory->inventoryStock()->count())->toBe(2);
    $this->assertDatabaseCount('inventory_stock_additions', 2);
});

it('computes stock as the sum of every ledger row for the item', function () {
    $item = makeInventoryTestItem('Dental Syringe');

    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 5]]]);

    expect($item->fresh()->current_stock)->toBe(15.0);
});

it('stores decimal quantities with two places', function () {
    $item = makeInventoryTestItem('Dental Floss');

    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 2.5]]]);

    $this->assertDatabaseHas('inventory_stock_additions', [
        'inventory_item_id' => $item->id,
        'quantity' => 2.50,
    ]);
});

it('accepts the quantity boundaries 1 and 99999', function (int $quantity) {
    $item = makeInventoryTestItem("Boundary {$quantity}");

    $response = $this->post(route('inventories.store'), [
        'itemList' => [['item_id' => $item->id, 'quantity' => $quantity]],
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_stock_additions', ['inventory_item_id' => $item->id, 'quantity' => $quantity]);
})->with([1, 99999]);

it('rejects create with invalid inventory data and writes nothing', function (array $data, string $expectedErrorField) {
    $item = makeInventoryTestItem('Valid Item');

    // Replace the placeholder item id with the real one for cases that need an item.
    if (isset($data['itemList'][0]['item_id']) && $data['itemList'][0]['item_id'] === 'ITEM') {
        $data['itemList'][0]['item_id'] = $item->id;
    }

    $response = $this->post(route('inventories.store'), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('inventories', 0);
    $this->assertDatabaseCount('inventory_stock_additions', 0);
})->with('invalidInventoryData');

// ---------------------------------------------------------------------------
// editing
// ---------------------------------------------------------------------------

it('shows the edit page with the inventory items and quantities', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->get(route('inventories.edit', $inventory));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Inventories/Edit')
        ->where('inventory.id', $inventory->id)
        ->where('inventory.staff_id', $this->user->id)
        ->where('inventory.itemList.0.item_id', $item->id)
        ->where('inventory.itemList.0.quantity', 10)
    );
});

it('returns 404 when opening the edit page for an inventory that does not exist', function () {
    $this->get(route('inventories.edit', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// updating
// ---------------------------------------------------------------------------

it('updates a quantity so that live stock reflects only the new quantity', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->put(route('inventories.update', $inventory), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 4]],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('inventories.index'));
    $response->assertSessionHas('message', 'Successfully updated inventory');

    expect($item->fresh()->current_stock)->toBe(4.0);
});

it('keeps the stock of other inventories unchanged when one inventory is updated', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 7]]]);

    $first = Inventory::orderBy('id')->firstOrFail();
    $this->put(route('inventories.update', $first), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 3]],
    ]);

    expect($item->fresh()->current_stock)->toBe(10.0); // 3 + 7
});

it('adds a new item to an existing inventory and adds its stock', function () {
    $syringe = makeInventoryTestItem('Dental Syringe');
    $mirror = makeInventoryTestItem('Dental Mirror');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $syringe->id, 'quantity' => 10]]]);
    $inventory = Inventory::firstOrFail();

    $this->put(route('inventories.update', $inventory), [
        'itemList' => [
            ['item_id' => $syringe->id, 'quantity' => 10],
            ['item_id' => $mirror->id, 'quantity' => 6],
        ],
    ]);

    expect($mirror->fresh()->current_stock)->toBe(6.0);
    expect($inventory->inventoryStock()->count())->toBe(2);
});

it('removes an item from an inventory and takes its stock back out', function () {
    $syringe = makeInventoryTestItem('Dental Syringe');
    $mirror = makeInventoryTestItem('Dental Mirror');
    $this->post(route('inventories.store'), [
        'itemList' => [
            ['item_id' => $syringe->id, 'quantity' => 10],
            ['item_id' => $mirror->id, 'quantity' => 6],
        ],
    ]);
    $inventory = Inventory::firstOrFail();

    $this->put(route('inventories.update', $inventory), [
        'itemList' => [['item_id' => $syringe->id, 'quantity' => 10]],
    ]);

    expect($mirror->fresh()->current_stock)->toBe(0.0);
    $this->assertDatabaseMissing('inventory_stock_additions', [
        'inventory_id' => $inventory->id,
        'inventory_item_id' => $mirror->id,
    ]);
});

it('rejects an update that would take stock below zero because it was already used, and changes nothing', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    recordTreatmentUseForInventory($item, 8);
    $inventory = Inventory::firstOrFail();

    $response = $this->put(route('inventories.update', $inventory), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 1]],
    ]);

    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Cannot complete this change'));
    $this->assertDatabaseHas('inventory_stock_additions', [
        'inventory_id' => $inventory->id,
        'inventory_item_id' => $item->id,
        'quantity' => 10,
    ]);
});

it('warns when an update takes an item below its minimum stock', function () {
    $item = makeInventoryTestItem('Dental Syringe', 10);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 20]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->put(route('inventories.update', $inventory), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 5]],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('warning', fn ($warnings) => collect($warnings)
        ->contains(fn ($w) => str_contains($w, 'below minimum stock')));
});

it('rejects update with invalid inventory data and leaves the record unchanged', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->put(route('inventories.update', $inventory), [
        'itemList' => [['item_id' => $item->id, 'quantity' => 0]],
    ]);

    $response->assertSessionHasErrors('itemList.0.quantity');
    expect($item->fresh()->current_stock)->toBe(10.0);
});

it('returns 404 when updating an inventory that does not exist', function () {
    $this->put(route('inventories.update', 999999), [
        'itemList' => [['item_id' => makeInventoryTestItem('Dental Syringe')->id, 'quantity' => 1]],
    ])->assertNotFound();
});

// ---------------------------------------------------------------------------
// deleting
// ---------------------------------------------------------------------------

it('deletes an inventory, removes its rows and takes its stock back out', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->delete(route('inventories.destroy', $inventory));

    $response->assertRedirect(route('inventories.index'));
    $response->assertSessionHas('message', 'Inventory entry deleted successfully.');
    $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
    $this->assertDatabaseCount('inventory_stock_additions', 0);
    expect($item->fresh()->current_stock)->toBe(0.0);
});

it('deletes only one inventory and leaves the stock of the others', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 7]]]);
    $first = Inventory::orderBy('id')->firstOrFail();

    $this->delete(route('inventories.destroy', $first));

    expect($item->fresh()->current_stock)->toBe(7.0);
});

it('rejects deleting an inventory whose stock was already used and keeps it', function () {
    $item = makeInventoryTestItem('Dental Syringe');
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 10]]]);
    recordTreatmentUseForInventory($item, 8);
    $inventory = Inventory::firstOrFail();

    $response = $this->delete(route('inventories.destroy', $inventory));

    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Cannot complete this change'));
    $this->assertDatabaseHas('inventories', ['id' => $inventory->id]);
    $this->assertDatabaseCount('inventory_stock_additions', 1);
});

it('warns when deleting an inventory leaves an item below its minimum stock', function () {
    $item = makeInventoryTestItem('Dental Syringe', 10);
    $this->post(route('inventories.store'), ['itemList' => [['item_id' => $item->id, 'quantity' => 20]]]);
    $inventory = Inventory::firstOrFail();

    $response = $this->delete(route('inventories.destroy', $inventory));

    $response->assertSessionHas('warning', fn ($warnings) => collect($warnings)
        ->contains(fn ($w) => str_contains($w, 'below minimum stock')));
});

it('returns 404 when deleting an inventory that does not exist', function () {
    $this->delete(route('inventories.destroy', 999999))->assertNotFound();
});