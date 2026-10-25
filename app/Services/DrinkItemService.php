<?php

namespace App\Services;

use App\Http\Requests\DrinkItemRequest;
use App\Models\Drink;
use App\Models\DrinkItem;

class DrinkItemService
{
    public function store(DrinkItemRequest $request): DrinkItem
    {
        if (! $request->filled('drink_id')) {
            Drink::create($request->validated());
        }

        /** @var Drink $drink */
        $drink = Drink::findOrFail($request->input('drink_id'));

        return $drink->items()->create($request->validated());
    }

    public function update(DrinkItemRequest $request, DrinkItem $drinkItem): bool
    {
        return $drinkItem->update($request->validated());
    }

    public function destroy(DrinkItem $drinkItem): ?bool
    {
        return $drinkItem->delete();
    }
}
