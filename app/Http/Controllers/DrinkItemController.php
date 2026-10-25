<?php

namespace App\Http\Controllers;

use App\Http\Requests\DrinkItemRequest;
use App\Models\DrinkItem;
use App\Services\DrinkItemService;
use Illuminate\Http\Request;

class DrinkItemController extends Controller
{
    public function __construct(private readonly DrinkItemService $drinkItemService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DrinkItemRequest $request)
    {
        $this->drinkItemService->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(DrinkItem $drinkItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DrinkItem $drinkItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DrinkItem $drinkItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DrinkItem $drinkItem)
    {
        //
    }
}
