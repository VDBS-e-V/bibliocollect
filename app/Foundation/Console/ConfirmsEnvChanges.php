<?php

declare(strict_types=1);

namespace App\Foundation\Console;

/** Bestätigung für ändernde env:*-Befehle mit der Option --yes. */
trait ConfirmsEnvChanges
{
    /** Bestätigung einholen: interaktiv per Frage, ohne Rückfrage-Möglichkeit nur mit --yes. */
    protected function confirmed(string $question): bool
    {
        if ($this->option('yes')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Das ist eine ändernde Aktion und braucht eine Bestätigung. Ohne Rückfrage-Möglichkeit bitte --yes angeben.');

            return false;
        }

        return $this->confirm($question, false);
    }
}
