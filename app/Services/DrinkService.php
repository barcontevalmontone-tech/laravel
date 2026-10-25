<?php

namespace App\Services;

use App\Http\Requests\DrinkRequest;
use App\Models\Drink;

class DrinkService
{
    public function store(DrinkRequest $request): Drink
    {
        return Drink::create($request->validated());
    }

    public function update(DrinkRequest $request, Drink $drink): bool
    {
        return $drink->update($request->validated());
    }

    public function destroy(Drink $drink): ?bool
    {
        return $drink->delete();
    }
}
