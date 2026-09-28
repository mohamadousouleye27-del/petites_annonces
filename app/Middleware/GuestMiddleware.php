<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

/**
 * Réserve une route aux visiteurs non connectés.
 *
 *   - visiteur non connecté     → poursuite normale ;
 *   - utilisateur connecté      → redirection HTTP 302 vers l'espace de son
 *                                 rôle (membre, modérateur ou administrateur),
 *                                 exécution interrompue.
 *
 * Utilisé sur les formulaires de connexion et d'inscription : un
 * utilisateur déjà connecté n'a plus besoin d'y accéder (et ne peut
 * donc pas créer un second compte depuis l'interface).
 *
 * Portée de la décision : ce middleware ne contrôle QUE l'authentification.
 * La destination est calculée par App\Core\Auth (utilitaire de rôle) et le
 * cloisonnement des espaces reste assuré par RoleMiddleware — jamais ici.
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

        // Utilisateur déjà connecté : renvoi vers l'espace de son rôle.
        // Le rôle est lu exclusivement en session (currentRole()) ;
        // Auth::dashboardPath() ne fait que construire l'URL de destination.
        // Un rôle inconnu retombe sur l'espace public, sans donner d'accès.
        $this->redirect(Auth::dashboardPath($this->currentRole()));
    }
}
