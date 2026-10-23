<?php

namespace App\Http\Controllers\Magazzino;

use App\Http\Controllers\Controller;
use App\Models\Articolo;
use App\Services\ReportRiordini;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportRiordini $report) {}

    /**
     * Show the warehouse dashboard with KPIs and reorder alerts.
     */
    public function index(): View
    {
        return view('magazzino.dashboard', [
            'totaleArticoli' => Articolo::count(),
            'articoliAttivi' => Articolo::attivi()->count(),
            'valoreGiacenza' => round(Articolo::attivi()
                ->selectRaw('SUM(giacenza * costo) as totale')
                ->value('totale') ?? 0, 2),
            'riordini' => $this->report->articoliDaOrdinare(),
            'riepilogo' => $this->report->riepilogo(),
        ]);
    }
}
