<?php

namespace Tests\Unit\Services;

use App\Enum\DrinkType;
use App\Http\Requests\DrinkRequest;
use App\Models\Drink;
use App\Services\DrinkService;

it('create drink', function () {

    $request = mock(DrinkRequest::class);

    $request->shouldReceive('validated')
        ->withNoArgs()
        ->once()
        ->andReturn([
            'name' => 'test name',
            'brand' => 'test brand',
            'alcoholic' => true,
            'type' => DrinkType::APERITIFS,
            'sold_to_minor' => false,
            'sugar_free' => true,
            'gluten_free' => true,
        ]);

    $service = new DrinkService;

    $service->store($request);

    $this->assertDatabaseCount('drinks', 1);

    $this->assertDatabaseHas('drinks', [
        'name' => 'test name',
        'brand' => 'test brand',
        'alcoholic' => true,
        'sold_to_minor' => false,
        'sugar_free' => true,
        'gluten_free' => true,
    ]);

});

it('update drink', function () {

    $drink = Drink::factory()->create();

    $request = mock(DrinkRequest::class);
    $request->shouldReceive('validated')
        ->withNoArgs()
        ->once()
        ->andReturn([
            'name' => 'test name',
            'brand' => 'test brand',
            'alcoholic' => true,
            'type' => DrinkType::APERITIFS,
            'sold_to_minor' => false,
            'sugar_free' => true,
            'gluten_free' => true,
        ]);

    $service = new DrinkService;
    $service->update($request, $drink);

    $this->assertDatabaseCount('drinks', 1);

    $this->assertDatabaseHas('drinks', [
        'name' => 'test name',
        'brand' => 'test brand',
        'alcoholic' => true,
        'type' => DrinkType::APERITIFS,
        'sold_to_minor' => false,
        'sugar_free' => true,
        'gluten_free' => true,
    ]);

});

it('delete drink', function () {

    $drink = Drink::factory()->create();

    $service = new DrinkService;
    $service->destroy($drink);

    $this->assertDatabaseCount('drinks', 0);

});
