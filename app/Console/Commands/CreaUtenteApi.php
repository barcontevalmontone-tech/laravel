<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreaUtenteApi extends Command
{
    protected $signature = 'utente:crea-api
                            {email : Email dell\'utente}
                            {--nome= : Nome visualizzato}
                            {--ruolo=operatore : Ruolo (operatore|admin)}
                            {--scadenza= : Scadenza del token in giorni (es. 365)}';

    protected $description = 'Crea un utente e genera un token API Sanctum (mostrato una sola volta)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $ruolo = $this->option('ruolo');

        if (! in_array($ruolo, ['operatore', 'admin'], true)) {
            $this->error("Ruolo non valido: {$ruolo} (ammessi: operatore, admin)");

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password utente');

        if (strlen($password) < 8) {
            $this->error('La password deve avere almeno 8 caratteri.');

            return self::FAILURE;
        }

        $utente = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('nome') ?? explode('@', $email)[0],
                'password' => $password,
                'ruolo' => $ruolo,
            ]
        );

        if (! $utente->wasRecentlyCreated) {
            $this->warn("Utente {$email} già esistente: verrà generato un nuovo token.");
        }

        $giorni = $this->option('scadenza');
        $scadenza = $giorni !== null ? now()->addDays((int) $giorni) : null;

        $token = $utente->createToken('cli-'.now()->timestamp, ['*'], $scadenza);

        $this->info('=== Token API (mostrato una sola volta, conservarlo con cura) ===');
        $this->line($token->plainTextToken);
        $this->line($scadenza ? "Scadenza: {$scadenza}" : 'Scadenza: nessuna');

        return self::SUCCESS;
    }
}
