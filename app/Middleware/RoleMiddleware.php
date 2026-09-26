<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Exige que l'utilisateur connecté possède l'un des rôles autorisés.
 *
 * À placer APRÈS AuthMiddleware dans la chaîne de middlewares :
 *
 *   'middleware' => [
 *       AuthMiddleware::class,
 *       [RoleMiddleware::class, 'admin'],
 *   ]
 *
 * Comportement :
 *   - le rôle courant est lu exclusivement en session (currentRole()) ;
 *   - l'accès est autorisé si le rôle figure dans la liste attendue
 *     (comparaison stricte) ;
 *   - sinon, réponse HTTP 403 et arrêt de l'exécution.
 *
 * Un rôle unique aussi bien qu'un tableau de rôles est accepté.
 * Un tableau vide n'autorise personne (comportement « fail-safe »).
 *
 * @package App\Middleware
 */
class RoleMiddleware extends Middleware
{
    /**
     * Liste normalisée des rôles autorisés.
     *
     * @var array<int, string>
     */
    private array $allowedRoles = [];

    /**
     * @param string|array<int, string> $roles Rôle unique ou liste de rôles autorisés
     */
    public function __construct(string|array $roles = [])
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            // Seules les chaînes non vides sont exploitables : toute
            // valeur inattendue est ignorée et ne peut donc jamais
            // élargir les droits accordés.
            if (!is_string($role)) {
                continue;
            }

            $role = trim($role);

            if ($role !== '') {
                $this->allowedRoles[] = $role;
            }
        }
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $currentRole = $this->currentRole();

        if ($currentRole !== '' && in_array($currentRole, $this->allowedRoles, true)) {
            return;
        }

        $this->forbidden();
    }
}
