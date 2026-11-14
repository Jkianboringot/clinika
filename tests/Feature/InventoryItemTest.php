<?php

use App\Enums\IsActiveEnum;
use App\Enums\StockStatusEnum;
use App\Models\InventoryItem;
use App\Models\InventoryStockAddition;
use App\Models\Treatment;
use App\Models\TreatmentInventory;
use App\Services\InventoryService;
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
 * Adds stock the same way the app does, through InventoryService::inventoryStore().
 */
function simulateInventoryStock(InventoryItem $item, float $quantity): void
{
    app(InventoryService::class)->inventoryStore([
        'itemList' => [['item_id' => $item->id, 'quantity' => $quantity]],
    ]);
}

/**
 * Records item usage in a treatment. treatment_id is not mass assignable,
 * so it is set directly before saving.
 */
function simulateTreatmentUse(InventoryItem $item, float $quantity): void
{
    $row = new TreatmentInventory([
        'inventory_item_id' => $item->id,
        'quantity' => $quantity,
    ]);
    $row->treatment_id = Treatment::factory()->create()->id;
    $row->save();
}

dataset('invalidInventoryItemData', [
    'item_name missing' => [['item_name' => ''], 'item_name'],
    'item_name too short' => [['item_name' => 'a'], 'item_name'],
    'item_name too long' => [['item_name' => str_repeat('a', 76)], 'item_name'],
    'item_name must be string' => [['item_name' => ['array']], 'item_name'],

    'minimum_stock zero' => [['minimum_stock' => 0], 'minimum_stock'],
    'minimum_stock negative' => [['minimum_stock' => -1], 'minimum_stock'],
    'minimum_stock must be numeric' => [['minimum_stock' => 'abc'], 'minimum_stock'],
    'minimum_stock over maximum' => [['minimum_stock' => 100000], 'minimum_stock'],

    'critical_stock zero' => [['critical_stock' => 0], 'critical_stock'],
    'critical_stock negative' => [['critical_stock' => -1], 'critical_stock'],
    'critical_stock must be numeric' => [['critical_stock' => 'abc'], 'critical_stock'],
    'critical_stock over maximum' => [['critical_stock' => 100000], 'critical_stock'],

    'description too short' => [['description' => 'a'], 'description'],
    'description too long' => [['description' => str_repeat('a', 301)], 'description'],
    'description must be string' => [['description' => ['array']], 'description'],
]);

dataset('stockStatusCases', [
    'zero stock with no thresholds is out of stock' => [0, null, null, StockStatusEnum::OutStock],
    'negative stock is out of stock' => [-1, null, null, StockStatusEnum::OutStock],
    'zero stock with thresholds is out of stock' => [0, 10, 3, StockStatusEnum::OutStock],
    'stock above minimum with no thresholds is in stock' => [5, null, null, StockStatusEnum::InStock],
    'stock above minimum is in stock' => [10.01, 10, 3, StockStatusEnum::InStock],
    'stock at minimum is low' => [10, 10, 3, StockStatusEnum::Low],
    'stock between critical and minimum is low' => [5, 10, 3, StockStatusEnum::Low],
    'stock just above critical is low' => [3.01, 10, 3, StockStatusEnum::Low],
    'stock at critical is critical' => [3, 10, 3, StockStatusEnum::Critical],
    'stock below critical is critical' => [2.99, 10, 3, StockStatusEnum::Critical],
    'critical only, stock above it is in stock' => [5, null, 3, StockStatusEnum::InStock],
    'critical only, stock at it is critical' => [3, null, 3, StockStatusEnum::Critical],
    'minimum only, stock at it is low' => [10, 10, null, StockStatusEnum::Low],
    'minimum only, stock above it is in stock' => [11, 10, null, StockStatusEnum::InStock],
    'critical equal to minimum, stock at it is critical' => [5, 5, 5, StockStatusEnum::Critical],
]);

// ---------------------------------------------------------------------------
// listing and search
// ---------------------------------------------------------------------------

it('lists inventory items on the index page', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe']);

    $response = $this->get(route('inventory_items.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Items/Index')
        ->has('inventoryitems.data', 1)
    );
});

it('orders inventory items by name', function () {
    InventoryItem::create(['item_name' => 'Surgical Mirror']);
    InventoryItem::create(['item_name' => 'Dental Syringe']);

    $response = $this->get(route('inventory_items.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('inventoryitems.data.0.item_name', 'Dental Syringe')
        ->where('inventoryitems.data.1.item_name', 'Surgical Mirror')
    );
});

it('filters inventory items by name', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe']);
    InventoryItem::create(['item_name' => 'Suction Tip']);

    $response = $this->get(route('inventory_items.index', ['search' => 'Syringe']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('inventoryitems.data', 1)
        ->where('inventoryitems.data.0.item_name', 'Dental Syringe')
        ->where('filters.search', 'Syringe')
    );
});

it('returns every inventory item when search is empty', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe']);
    InventoryItem::create(['item_name' => 'Suction Tip']);

    $response = $this->get(route('inventory_items.index', ['search' => '']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('inventoryitems.data', 2)
    );
});

it('shows zero stock and out of stock for an item with no stock records', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe', 'minimum_stock' => 10, 'critical_stock' => 3]);

    $response = $this->get(route('inventory_items.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('inventoryitems.data.0.stock', 0)
        ->where('inventoryitems.data.0.stock_status', StockStatusEnum::OutStock->value)
        ->where('inventoryitems.data.0.minimum_stock', 10)
        ->where('inventoryitems.data.0.critical_stock', 3)
    );
});

it('includes items at or below their minimum in lowStockItems', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe', 'minimum_stock' => 10]);
    InventoryItem::create(['item_name' => 'Suction Tip']);

    $response = $this->get(route('inventory_items.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('lowStockItems', 1)
        ->where('lowStockItems.0.item_name', 'Dental Syringe')
    );
});

it('does not list items without thresholds in lowStockItems', function () {
    InventoryItem::create(['item_name' => 'Suction Tip']);

    $response = $this->get(route('inventory_items.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('lowStockItems', 0)
    );
});

it('does not crash on an unknown status filter', function () {
    $response = $this->get(route('inventory_items.index', ['status' => 'not-a-status']));

    expect($response->status())->toBeLessThan(500);
});

// ---------------------------------------------------------------------------
// stock status (pure logic, no stock records needed)
// ---------------------------------------------------------------------------

it('computes the stock status from stock and thresholds', function (float $stock, ?float $minimum, ?float $critical, StockStatusEnum $expected) {
    expect(InventoryItem::statusFor($stock, $minimum, $critical))->toBe($expected);
})->with('stockStatusCases');

// ---------------------------------------------------------------------------
// creating
// ---------------------------------------------------------------------------

it('creates an inventory item with valid data, redirects with a success message and the record exists in db', function () {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => 'Dental Syringe',
        'minimum_stock' => 10,
        'critical_stock' => 3,
        'description' => 'Disposable syringe for local anesthesia',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('inventory_items.index'));
    $response->assertSessionHas('message', 'Inventory Item created successfully.');

    $this->assertDatabaseHas('inventory_items', [
        'item_name' => 'Dental Syringe',
        'minimum_stock' => 10,
        'critical_stock' => 3,
        'description' => 'Disposable syringe for local anesthesia',
    ]);
});

it('creates an inventory item with name only and the thresholds stored as null', function () {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => 'Dental Mirror',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('inventory_items.index'));

    $this->assertDatabaseHas('inventory_items', [
        'item_name' => 'Dental Mirror',
        'minimum_stock' => null,
        'critical_stock' => null,
        'description' => null,
    ]);
});

it('stores decimal thresholds with two places', function () {
    $this->post(route('inventory_items.store'), [
        'item_name' => 'Dental Floss',
        'minimum_stock' => 2.5,
        'critical_stock' => 1.25,
    ]);

    $this->assertDatabaseHas('inventory_items', [
        'item_name' => 'Dental Floss',
        'minimum_stock' => 2.50,
        'critical_stock' => 1.25,
    ]);
});

it('accepts the name boundaries 2 and 75 characters', function (int $length) {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => str_repeat('a', $length),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_items', ['item_name' => str_repeat('a', $length)]);
})->with([2, 75]);

it('accepts the threshold boundaries 1 and 99999', function (int $value) {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => "Threshold {$value}",
        'minimum_stock' => $value,
        'critical_stock' => $value,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_items', ['item_name' => "Threshold {$value}"]);
})->with([1, 99999]);

it('accepts the description boundaries 2 and 300 characters', function (int $length) {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => "Desc {$length}",
        'description' => str_repeat('d', $length),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_items', ['item_name' => "Desc {$length}"]);
})->with([2, 300]);

it('rejects critical stock above minimum stock, since critical must be the lower level', function () {
    $response = $this->post(route('inventory_items.store'), [
        'item_name' => 'Wrong Order',
        'minimum_stock' => 5,
        'critical_stock' => 10,
    ]);

    $response->assertSessionHasErrors('critical_stock');
    $this->assertDatabaseCount('inventory_items', 0);
});

it('rejects a duplicate item name on create and writes no second row', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe']);

    $response = $this->post(route('inventory_items.store'), [
        'item_name' => 'Dental Syringe',
        'minimum_stock' => 10,
    ]);

    $response->assertSessionHas('error', 'Failed to create Inventory Item.');
    $this->assertDatabaseCount('inventory_items', 1);
});

it('rejects create with invalid inventory item data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $data = array_merge([
        'item_name' => 'Valid Item',
        'minimum_stock' => 10,
        'critical_stock' => 5,
        'description' => 'Valid description',
    ], $overrides);

    $response = $this->post(route('inventory_items.store'), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('inventory_items', 0);
})->with('invalidInventoryItemData');

// ---------------------------------------------------------------------------
// editing and updating
// ---------------------------------------------------------------------------

it('shows the edit page with only the editable fields', function () {
    $item = InventoryItem::create([
        'item_name' => 'Dental Syringe',
        'description' => 'Disposable',
        'minimum_stock' => 10,
        'critical_stock' => 3,
    ]);

    $response = $this->get(route('inventory_items.edit', $item));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Items/Edit')
        ->where('inventoryItem.id', $item->id)
        ->where('inventoryItem.item_name', 'Dental Syringe')
        ->where('inventoryItem.description', 'Disposable')
        ->where('inventoryItem.minimum_stock', 10)
        ->where('inventoryItem.critical_stock', 3)
        ->missing('inventoryItem.created_at')
        ->missing('inventoryItem.updated_at')
    );
});

it('returns 404 when opening the edit page for an item that does not exist', function () {
    $this->get(route('inventory_items.edit', 999999))->assertNotFound();
});

it('updates an inventory item with valid data, redirects with a success message and the record is updated in db', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Syringe', 'minimum_stock' => 10]);

    $response = $this->put(route('inventory_items.update', $item), [
        'item_name' => 'Dental Syringe Large',
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'description' => 'Updated description',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('inventory_items.index'));
    $response->assertSessionHas('message', 'Inventory Item updated successfully.');

    $this->assertDatabaseHas('inventory_items', [
        'id' => $item->id,
        'item_name' => 'Dental Syringe Large',
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'description' => 'Updated description',
    ]);
});

it('allows updating an item while keeping its own name', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Mirror']);

    $response = $this->put(route('inventory_items.update', $item), [
        'item_name' => 'Dental Mirror',
        'minimum_stock' => 4,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'item_name' => 'Dental Mirror', 'minimum_stock' => 4]);
});

it('clears a threshold when it is sent as null', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Mirror', 'minimum_stock' => 10, 'critical_stock' => 3]);

    $response = $this->put(route('inventory_items.update', $item), [
        'item_name' => 'Dental Mirror',
        'minimum_stock' => null,
        'critical_stock' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('inventory_items', [
        'id' => $item->id,
        'minimum_stock' => null,
        'critical_stock' => null,
    ]);
});

it('rejects renaming an item to another existing name and leaves the record unchanged', function () {
    InventoryItem::create(['item_name' => 'Existing Item']);
    $item = InventoryItem::create(['item_name' => 'Original Item', 'minimum_stock' => 10]);

    $response = $this->put(route('inventory_items.update', $item), [
        'item_name' => 'Existing Item',
        'minimum_stock' => 20,
    ]);

    $response->assertSessionHas('error', 'Failed to update Inventory Item.');
    $this->assertDatabaseHas('inventory_items', [
        'id' => $item->id,
        'item_name' => 'Original Item',
        'minimum_stock' => 10,
    ]);
});

it('rejects update with invalid inventory item data and leaves the record unchanged', function (array $overrides, string $expectedErrorField) {
    $item = InventoryItem::create([
        'item_name' => 'Stable Item',
        'minimum_stock' => 10,
        'critical_stock' => 5,
        'description' => 'Stable description',
    ]);

    $data = array_merge([
        'item_name' => 'Stable Item',
        'minimum_stock' => 10,
        'critical_stock' => 5,
        'description' => 'Stable description',
    ], $overrides);

    $response = $this->put(route('inventory_items.update', $item), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseHas('inventory_items', [
        'id' => $item->id,
        'item_name' => 'Stable Item',
        'minimum_stock' => 10,
        'critical_stock' => 5,
    ]);
})->with('invalidInventoryItemData');

it('returns 404 when updating an item that does not exist', function () {
    $this->put(route('inventory_items.update', 999999), [
        'item_name' => 'Doesnt Matter',
    ])->assertNotFound();
});

// ---------------------------------------------------------------------------
// deleting
// ---------------------------------------------------------------------------

it('deletes an item that has no stock and is not used in any treatment, redirects with a success message', function () {
    $item = InventoryItem::create(['item_name' => 'Unused Item']);

    $response = $this->delete(route('inventory_items.destroy', $item));

    $response->assertRedirect(route('inventory_items.index'));
    $response->assertSessionHas('message', 'Inventory Item deleted successfully.');
    $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
});

it('rejects deleting an item that still has stock and keeps the record', function () {
    $item = InventoryItem::create(['item_name' => 'Stocked Item']);
    simulateInventoryStock($item, 10);

    $response = $this->delete(route('inventory_items.destroy', $item));

    $response->assertRedirect(route('inventory_items.index'));
    $response->assertSessionHas('error', 'This Inventory Item has stock and cannot be deleted.');
    $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
});

it('rejects deleting an item used in a treatment and keeps the record', function () {
    $item = InventoryItem::create(['item_name' => 'Used Item']);
    simulateTreatmentUse($item, 1);

    $response = $this->delete(route('inventory_items.destroy', $item));

    $response->assertRedirect(route('inventory_items.index'));
    $response->assertSessionHas('error', 'This Inventory Item use in treatment and cannot be deleted.');
    $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
});

it('checks stock before treatment use when both exist', function () {
    $item = InventoryItem::create(['item_name' => 'Stocked And Used']);
    simulateInventoryStock($item, 10);
    simulateTreatmentUse($item, 1);

    $response = $this->delete(route('inventory_items.destroy', $item));

    $response->assertSessionHas('error', 'This Inventory Item has stock and cannot be deleted.');
    $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
});

it('returns 404 when deleting an item that does not exist', function () {
    $this->delete(route('inventory_items.destroy', 999999))->assertNotFound();
});