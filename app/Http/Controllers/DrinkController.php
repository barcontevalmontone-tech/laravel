<?php

namespace App\Http\Controllers;

use App\Enum\DrinkType;
use App\Http\Requests\DrinkRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Drink;
use App\Services\DrinkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DrinkController extends Controller
{
    public function __construct(private readonly DrinkService $drinkService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(SearchRequest $request): View
    {
        return view('drink.index', [
            'drinks' => Drink::query()
                ->when(! blank(Str::trim($request->string('q'))),
                    fn (Builder $q) => $q->whereLike('name', "%{$request->string('q')}%")
                )
                ->paginate(7)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('drink.create', [
            'types' => DrinkType::cases(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DrinkRequest $request): RedirectResponse
    {
        $this->drinkService->store($request);

        return redirect()->route('drink.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Drink $drink): View
    {
        return view('drink.edit', [
            'types' => DrinkType::cases(),
            'drink' => $drink,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DrinkRequest $request, Drink $drink): RedirectResponse
    {
        $this->drinkService->update($request, $drink);

        return redirect()->route('drink.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drink $drink): RedirectResponse
    {
        $this->drinkService->destroy($drink);

        return redirect()->route('drink.index');
    }
}
