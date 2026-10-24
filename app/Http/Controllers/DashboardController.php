<?php

namespace App\Http\Controllers;

use App\Models\Movimento;
use App\Models\ProdottoBar;
use App\Models\Scontrino;
use App\Services\ReportRiordini;
use Illuminate\View\View;

/**
 * Dashboard operativa unificata: integra i KPI del modulo Bar e del
 * magazzino Tabacchi. Il modulo Tabacchi è letto solo tramite i suoi
 * scope/servizi pubblici (nessuna alterazione della logica interna).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ReportRiordini $report) {}

    /**
     * Riepilogo operativo di giornata: alert critici e KPI.
     */
    public function index(): View
    {
        // --- Modulo Bar ---------------------------------------------------
        $prodottiSottoSoglia = ProdottoBar::query()
            ->attivi()
            ->sottoSoglia()
            ->orderBy('giacenza_attuale')
            ->get();

        $incassoOggi = (float) (Scontrino::query()
            ->whereDate('data_vendita', today())
            ->sum('totale') ?? 0);

        $scontriniOggi = Scontrino::query()
            ->whereDate('data_vendita', today())
            ->count();

        // --- Magazzino Tabacchi (sola lettura) ----------------------------
        $riordini = $this->report->articoliDaOrdinare();
        $riepilogo = $this->report->riepilogo();

        $movimentiOggi = Movimento::query()
            ->whereDate('created_at', today())
            ->count();

        $scarichiOggi = Movimento::query()
            ->whereDate('created_at', today())
            ->where('tipo', 'scarico')
            ->count();

        return view('dashboard.index', [
            // Bar
            'alertBar' => $prodottiSottoSoglia,
            'incassoOggi' => $incassoOggi,
            'scontriniOggi' => $scontriniOggi,
            // Tabacchi
            'riordiniTabacchi' => $riordini,
            'riepilogoTabacchi' => $riepilogo,
            'movimentiOggi' => $movimentiOggi,
            'scarichiOggi' => $scarichiOggi,
            // Contesto generale
            'totaliAlert' => $prodottiSottoSoglia->count() + $riordini->count(),
        ]);
    }
}
