<?php

namespace App\Enums;

enum CausaleMagazzino: string
{
    // Carico
    case Acquisto = 'acquisto';
    case ResoCliente = 'reso-cliente';
    case Riaprovvigionamento = 'riaprovvigionamento';
    case SpostamentoInEntrata = 'spostamento-in-entrata';
    case RettificaInventario = 'rettifica-inventario';

    // Scarico
    case Vendita = 'vendita';
    case ResoFornitore = 'reso-fornitore';
    case SpostamentoInUscita = 'spostamento-in-uscita';
    case Campione = 'campione';
    case Deteriorato = 'deteriorato';
    case RubatooSmarrito = 'rubato-o-smarrito';

    // Movimenti riservati agli amministratori (regolamento tabacchi)
    case Distruzione = 'distruzione';
    case Sversamento = 'sversamento';
    case FurtoDenunciato = 'furto-denunciato';
    case AmministrazioneControllata = 'amministrazione-controllata';

    /**
     * The movement types this causale is allowed to be registered with.
     *
     * @return list<TipoMovimento>
     */
    public function tipiConsentiti(): array
    {
        return match ($this) {
            self::Acquisto,
                self::ResoCliente,
                self::Riaprovvigionamento,
                self::SpostamentoInEntrata => [TipoMovimento::Carico],

            self::Vendita,
                self::ResoFornitore,
                self::SpostamentoInUscita,
                self::Campione,
                self::Deteriorato,
                self::RubatooSmarrito => [TipoMovimento::Scarico],

            self::Distruzione,
                self::Sversamento,
                self::FurtoDenunciato,
                self::AmministrazioneControllata => [TipoMovimento::Scarico, TipoMovimento::Rettifica],

            self::RettificaInventario => [TipoMovimento::Carico, TipoMovimento::Scarico, TipoMovimento::Rettifica],
        };
    }

    /**
     * Whether only administrators may register movements with this causale.
     */
    public function riservataAdmin(): bool
    {
        return match ($this) {
            self::Distruzione,
                self::Sversamento,
                self::FurtoDenunciato,
                self::AmministrazioneControllata,
                self::RettificaInventario => true,
            default => false,
        };
    }

    /**
     * A human readable label in Italian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Acquisto => 'Acquisto da fornitore',
            self::ResoCliente => 'Reso cliente',
            self::Riaprovvigionamento => 'Riaprovvigionamento',
            self::SpostamentoInEntrata => 'Spostamento in entrata',
            self::RettificaInventario => 'Rettifica inventario',
            self::Vendita => 'Vendita al banco',
            self::ResoFornitore => 'Reso a fornitore',
            self::SpostamentoInUscita => 'Spostamento in uscita',
            self::Campione => 'Campionatura / omaggio',
            self::Deteriorato => 'Prodotto deteriorato',
            self::RubatooSmarrito => 'Rubato o smarrito',
            self::Distruzione => 'Distruzione',
            self::Sversamento => 'Sversamento',
            self::FurtoDenunciato => 'Furto denunciato',
            self::AmministrazioneControllata => 'Amministrazione controllata',
        };
    }

    /**
     * The valid causalae for the given movement type.
     *
     * @return list<string>
     */
    public static function valoriPerTipo(TipoMovimento $tipo): array
    {
        return array_values(array_map(
            static fn (self $causale): string => $causale->value,
            array_filter(
                self::cases(),
                static fn (self $causale): bool => in_array($tipo, $causale->tipiConsentiti(), true),
            ),
        ));
    }
}
