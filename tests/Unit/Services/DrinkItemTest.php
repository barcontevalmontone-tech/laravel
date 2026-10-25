<?php

namespace Tests\Unit\Services;

use App\Enum\DrinkPackageType;
use App\Enum\DrinkType;
use App\Http\Requests\DrinkItemRequest;
use App\Models\Drink;
use App\Models\DrinkItem;
use App\Services\DrinkItemService;

it('create drink item if request have not drink_id', function () {

    $request = mock(DrinkItemRequest::class);

    $request->shouldReceive('filled')
        ->with('drink_id')
        ->once()
        ->andReturn(false);

    $request->shouldReceive('input')
        ->with('drink_id')
        ->once()
        ->andReturn(1);

    $request->shouldReceive('validated')
        ->withNoArgs()
        ->times(2)
        ->andReturn([
            'name' => 'test name',
            'brand' => 'test brand',
            'alcoholic' => true,
            'type' => DrinkType::APERITIFS,
            'sold_to_minor' => false,
            'sugar_free' => true,
            'gluten_free' => true,

            'cl' => 50,
            'package_type' => DrinkPackageType::GLASS,
            'barcode' => '1234567890123',
            'price' => 1000,
        ]);

    $service = new DrinkItemService;

    $service->store($request);

    $this->assertDatabaseCount('drinks', 1);
    $this->assertDatabaseCount('drink_items', 1);

    $this->assertDatabaseHas('drinks', [
        'name' => 'test name',
        'brand' => 'test brand',
        'alcoholic' => true,
        'sold_to_minor' => false,
        'sugar_free' => true,
        'gluten_free' => true,
    ]);

    $this->assertDatabaseHas('drink_items', [
        'cl' => 50,
        'package_type' => DrinkPackageType::GLASS,
        'barcode' => '1234567890123',
        'price' => 1000,
    ]);

});

it('create drink item if request have drink_id', function () {

    Drink::factory()->create();

    $request = mock(DrinkItemRequest::class);

    $request->shouldReceive('filled')
        ->with('drink_id')
        ->once()
        ->andReturn(true);

    $request->shouldReceive('input')
        ->with('drink_id')
        ->once()
        ->andReturn(1);

    $request->shouldReceive('validated')
        ->withNoArgs()
        ->once()
        ->andReturn([
            'cl' => 50,
            'package_type' => DrinkPackageType::GLASS,
            'barcode' => '1234567890123',
            'price' => 1000,
        ]);

    $service = new DrinkItemService;

    $service->store($request);

    $this->assertDatabaseCount('drinks', 1);
    $this->assertDatabaseCount('drink_items', 1);

    $this->assertDatabaseHas('drink_items', [
        'cl' => 50,
        'package_type' => DrinkPackageType::GLASS,
        'barcode' => '1234567890123',
        'price' => 1000,
    ]);

});

it('update drink item', function () {

    $drinkItem = DrinkItem::factory()
        ->for(Drink::factory(), 'drink')
        ->create();

    $request = mock(DrinkItemRequest::class);
    $request->shouldReceive('validated')
        ->withNoArgs()
        ->once()
        ->andReturn([
            'cl' => 50,
            'package_type' => DrinkPackageType::GLASS,
            'barcode' => '1234567890123',
            'price' => 1000,
        ]);

    $service = new DrinkItemService;
    $service->update($request, $drinkItem);

    $this->assertDatabaseCount('drink_items', 1);

    $this->assertDatabaseHas('drink_items', [
        'cl' => 50,
        'package_type' => DrinkPackageType::GLASS,
        'barcode' => '1234567890123',
        'price' => 1000,
    ]);

});

it('delete drink item', function () {

    $drinkItem = DrinkItem::factory()
        ->for(Drink::factory(), 'drink')
        ->create();

    $service = new DrinkItemService;
    $service->destroy($drinkItem);

    $this->assertDatabaseCount('drink_items', 0);
    $this->assertDatabaseCount('drinks', 1);

});
