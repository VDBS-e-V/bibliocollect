<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Console;

use App\Modules\Patrons\Actions\IssuePatronLinkCodeAction;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Console\Command;

final class IssuePatronLinkCodeCommand extends Command
{
    protected $signature = 'patron:issue-link-code {library_number : Bibliotheksnummer} {--minutes= : Gültigkeit in Minuten}';

    protected $description = 'Lokale Entwicklungshilfe: gibt einen einmaligen Onlinekonto-Code aus.';

    public function handle(IssuePatronLinkCodeAction $issue): int
    {
        if (! $this->laravel->environment('local')) {
            $this->error('Dieser Hilfsbefehl ist nur in der lokalen Entwicklungsumgebung verfügbar.');

            return self::FAILURE;
        }

        $patron = Patron::query()
            ->where('library_number', (string) $this->argument('library_number'))
            ->first();

        if ($patron === null) {
            $this->error('Ausleihkonto nicht gefunden.');

            return self::FAILURE;
        }

        $minutes = $this->option('minutes');
        $result = $issue->execute(
            patron: $patron,
            ttlMinutes: is_numeric($minutes) ? (int) $minutes : null,
        );

        $this->line('Einmalcode: <info>'.$result->code.'</info>');
        $this->line('Gültig bis: '.$result->expiresAt->format('d.m.Y H:i'));
        $this->warn('Der Klartext-Code wird nicht gespeichert und sollte nur persönlich ausgegeben werden.');

        return self::SUCCESS;
    }
}
