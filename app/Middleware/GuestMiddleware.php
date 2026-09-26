<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Réserve une route aux visiteurs non connectés.
 *
 *   - visiteur non connecté     → poursuite normale ;
 *   - utilisateur connecté      → redirection HTTP 302 vers la page
 *                                 d'accueil, exécution interrompue.
 *
 * Utilisé sur les formulaires de connexion et d'inscription : un
 * utilisateur déjà connecté n'a plus besoin d'y accéder (et ne peut
 * donc pas créer un second compte depuis l'interface).
 *
 * @package App\Middleware
 */
class GuestMiddleware extends Middleware
{
    /**
     * @return void
     */
    public function handle(): void
    {
        // Visiteur anonyme : accès autorisé
        if (!$this->isAuthenticated()) {
            return;
        }

        // Utilisateur déjà connecté : retour à l'accueil
        $this->redirect(base_path('/'));
    }
}
