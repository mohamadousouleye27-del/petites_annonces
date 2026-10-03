<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Annonce;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Ville;

/**
 * Contrôleur de la page d'accueil (PALIER 7.5 — LECTURE SEULE).
 *
 * L'accueil public est alimenté par les données RÉELLES de MariaDB via les
 * modèles de lecture créés au palier 7.0. Aucune requête SQL n'est écrite
 * ici : le contrôleur se contente d'orchestrer les modèles puis de
 * transmettre leurs résultats à la vue.
 *
 * RÈGLES RESPECTÉES
 *   - AUCUN PDO, prepare(), query(), exec() ni fragment SQL dans ce fichier ;
 *   - AUCUNE écriture : ce palier est strictement en lecture ;
 *   - aucune requête par ligne dans une boucle : chaque besoin est servi par
 *     UNE méthode de modèle (donc une requête), d'où 5 requêtes au total ;
 *   - les annonces affichées sont celles que les modèles déclarent publiques
 *     (statut `active`) : aucun statut n'est inventé, aucun statut modifié ;
 *   - la page reste PUBLIQUE : aucun middleware n'est ajouté (voir
 *     config/routes.php) et la route GET / reste inchangée.
 *
 * @package App\Controllers
 */
class HomeController extends Controller
{
    /**
     * Nombre de catégories affichées dans la grille « Parcourir par catégorie ».
     *
     * Borne de présentation : la grille du design accueille 10 cartes.
     *
     * @var int
     */
    private const CATEGORIES_AFFICHEES = 10;

    /**
     * Nombre d'annonces récentes affichées dans la grille « Annonces récentes ».
     *
     * @var int
     */
    private const ANNONCES_AFFICHEES = 8;

    /**
     * Nombre maximal de villes proposées dans le sélecteur de localisation.
     *
     * Le design prévoyait une liste de régions du Sénégal : seules les
     * divisions administratives de type `region` sont donc proposées.
     *
     * @var int
     */
    private const VILLES_AFFICHEES = 20;

    /**
     * Affiche la page d'accueil.
     *
     * @return void
     */
    public function index(): void
    {
        $annonceModel = new Annonce();
        $categorieModel = new Categorie();
        $villeModel = new Ville();
        $userModel = new User();

        // Catégories publiques, triées par nombre d'annonces (1 requête)
        $categories = $categorieModel->listerActives(self::CATEGORIES_AFFICHEES);

        // Annonces publiques les plus récentes (1 requête)
        $annonces = $annonceModel->listerActivesRecentes(self::ANNONCES_AFFICHEES);

        // Régions disponibles pour le filtre de localisation (1 requête)
        $villes = $villeModel->listerParType('region', self::VILLES_AFFICHEES);

        // Compteurs réels (1 requête chacun) — jamais de chiffres inventés
        $annoncesActives = $annonceModel->compterParStatut('active');
        $nbMembres = $userModel->compter();

        $this->view('home/index', [
            'title'           => 'PetitesAnnonces.sn — Achetez, vendez, trouvez près de chez vous',
            'categories'      => $categories,
            'annonces'        => $annonces,
            'villes'          => $villes,
            'annoncesActives' => $annoncesActives,
            'nbMembres'      => $nbMembres,
            'stats'           => [
                [
                    'valeur' => number_format($annoncesActives, 0, ',', ' '),
                    'label'  => 'Annonces actives',
                ],
                [
                    'valeur' => number_format($nbMembres, 0, ',', ' '),
                    'label'  => 'Utilisateurs inscrits',
                ],
                [
                    'valeur' => count($villes) . (count($villes) > 1 ? ' régions' : ' région'),
                    'label'  => 'Couverture nationale',
                ],
                [
                    // Engagement commercial de la plateforme : ce n'est PAS un
                    // compteur de base et ne doit pas être présenté comme tel.
                    'valeur' => '100%',
                    'label'  => 'Gratuit pour tous',
                ],
            ],
        ]);
    }
}