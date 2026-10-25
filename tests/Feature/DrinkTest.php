<?php

namespace Feature;

use App\Enum\DrinkType;
use App\Models\Drink;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

it('index drink', function () {
    $this->actingAs(User::factory()->create());

    Drink::factory()
        ->count(3)
        ->sequence(
            ['name' => 'test first name'],
            ['name' => 'test second name'],
            ['name' => 'test third name']
        )
        ->create();

    $this->get(route('drink.index'))
        ->assertOk()
        ->assertViewHas('drinks', fn (LengthAwarePaginator $drinks) => $drinks->count() === 3);
});

it('index drink with search', function () {

    $this->actingAs(User::factory()->create());

    Drink::factory()
        ->count(3)
        ->sequence(
            ['name' => 'test first name'],
            ['name' => 'test second name'],
            ['name' => 'test third name']
        )
        ->create();

    $this->get(route('drink.index', ['q' => ' first ']))
        ->assertOk()
        ->assertViewHas('drinks', fn (LengthAwarePaginator $drinks) => $drinks->count() === 1);

});

it('create drink', function () {

    $this->actingAs(User::factory()->create());

    $this->get(route('drink.create'))
        ->assertOk()
        ->assertViewHas(['types' => DrinkType::cases()])
        ->assertViewIs('drink.create');
});

it('store Drink', function () {

    $this->actingAs(User::factory()->create());

    $this->post(route('drink.store'), [
        'name' => 'test name',
        'brand' => 'test brand',
        'alcoholic' => true,
        'type' => DrinkType::APERITIFS->value,
        'sold_to_minor' => false,
        'sugar_free' => true,
        'gluten_free' => true,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('drink.index'));

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

it('edit drink', function () {

    $this->actingAs(User::factory()->create());

    $drink = Drink::factory()->create();

    $this->get(route('drink.edit', $drink))
        ->assertOk()
        ->assertViewHas([
            'types' => DrinkType::cases(),
            'drink' => $drink,
        ])
        ->assertViewIs('drink.edit');
});

it('update Drink', function () {

    $this->actingAs(User::factory()->create());

    $drink = Drink::factory()->create();

    $this->patch(route('drink.update', $drink), [
        'name' => 'test name',
        'brand' => 'test brand',
        'alcoholic' => true,
        'type' => DrinkType::APERITIFS->value,
        'sold_to_minor' => false,
        'sugar_free' => true,
        'gluten_free' => true,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('drink.index'));

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

it('delete Drink', function () {

    $this->actingAs(User::factory()->create());

    $drink = Drink::factory()->create();

    $this->delete(route('drink.destroy', $drink));

    $this->assertDatabaseCount('drinks', 0);
});
