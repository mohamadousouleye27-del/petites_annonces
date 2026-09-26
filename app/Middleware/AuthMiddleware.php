<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Exige une session authentifiée.
 *
 *   - utilisateur connecté      → poursuite normale ;
 *   - visiteur non connecté     → redirection HTTP 302 vers la page
 *                                 de connexion, exécution interrompue.
 *
 * @package App\Middleware
 */
class AuthMiddleware extends Middleware
{
    /**
     * @return void
     */
    public function handle(): void
    {
        // Utilisateur authentifié : le reste de la chaîne peut s'exécuter
        if ($this->isAuthenticated()) {
            return;
        }

        // Visiteur anonyme : redirection vers la page de connexion
        $this->redirect(base_path('auth/login'));
    }
}
