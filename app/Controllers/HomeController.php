<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Contrôleur de la page d'accueil.
 *
 * @package App\Controllers
 */
class HomeController extends Controller
{
    /**
     * Affiche la page d'accueil.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('home/index', [
            'title'      => 'PetitesAnnonces.sn — Achetez, vendez, trouvez près de chez vous',
            'categories' => $this->getCategories(),
            'annonces'   => $this->getAnnonces(),
            'stats'      => $this->getStats(),
        ]);
    }

    /**
     * Données temporaires des catégories.
     *
     * @return array<int, array<string, string|int>>
     */
    private function getCategories(): array
    {
        return [
            [
                'nom'    => 'Immobilier',
                'icone'  => 'immeuble',
                'nombre' => 3240,
            ],
            [
                'nom'    => 'Véhicules',
                'icone'  => 'voiture',
                'nombre' => 2875,
            ],
            [
                'nom'    => 'Électronique',
                'icone'  => 'electronique',
                'nombre' => 1980,
            ],
            [
                'nom'    => 'Téléphones',
                'icone'  => 'telephone',
                'nombre' => 1642,
            ],
            [
                'nom'    => 'Mode',
                'icone'  => 'mode',
                'nombre' => 1250,
            ],
            [
                'nom'    => 'Maison',
                'icone'  => 'maison',
                'nombre' => 980,
            ],
            [
                'nom'    => 'Emploi & Services',
                'icone'  => 'emploi',
                'nombre' => 1120,
            ],
            [
                'nom'    => 'Loisirs',
                'icone'  => 'loisirs',
                'nombre' => 760,
            ],
            [
                'nom'    => 'Autres',
                'icone'  => 'autres',
                'nombre' => 771,
            ],
        ];
    }

    /**
     * Données temporaires des annonces récentes.
     *
     * @return array<int, array<string, string|int|bool>>
     */
    private function getAnnonces(): array
    {
        return [
            [
                'titre'        => 'Appartement 3 pièces à Almadies',
                'prix'         => 250000,
                'type'         => 'Location',
                'suffixe'      => '/mois',
                'localisation' => 'Dakar, Almadies',
                'vues'         => 1240,
                'date'         => 'Il y a 2 heures',
                'image'        => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=80',
                'badge'        => 'Urgent',
                'favori'       => false,
            ],
            [
                'titre'        => 'Toyota RAV4 2019 — Très bon état',
                'prix'         => 18500000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Plateau',
                'vues'         => 980,
                'date'         => 'Il y a 5 heures',
                'image'        => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80',
                'badge'        => '',
                'favori'       => true,
            ],
            [
                'titre'        => 'iPhone 13 Pro Max 256 Go',
                'prix'         => 750000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Sacré-Cœur',
                'vues'         => 2100,
                'date'         => 'Il y a 8 heures',
                'image'        => 'https://images.unsplash.com/photo-1603891128711-11b4b03bb6f3?w=600&q=80',
                'badge'        => 'Top vente',
                'favori'       => false,
            ],
            [
                'titre'        => 'Villa 5 chambres avec piscine',
                'prix'         => 95000000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Ngor',
                'vues'         => 1560,
                'date'         => 'Hier',
                'image'        => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=600&q=80',
                'badge'        => '',
                'favori'       => false,
            ],
            [
                'titre'        => 'MacBook Pro 14" M1 Pro',
                'prix'         => 1250000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Mermoz',
                'vues'         => 1870,
                'date'         => 'Hier',
                'image'        => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80',
                'badge'        => 'Comme neuf',
                'favori'       => true,
            ],
            [
                'titre'        => 'Boutique commerciale à vendre',
                'prix'         => 45000000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Sandaga',
                'vues'         => 720,
                'date'         => 'Il y a 2 jours',
                'image'        => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&q=80',
                'badge'        => '',
                'favori'       => false,
            ],
            [
                'titre'        => 'Samsung Galaxy S23 Ultra',
                'prix'         => 850000,
                'type'         => 'Vente',
                'suffixe'      => '',
                'localisation' => 'Dakar, Ouakam',
                'vues'         => 1430,
                'date'         => 'Il y a 2 jours',
                'image'        => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&q=80',
                'badge'        => '',
                'favori'       => false,
            ],
            [
                'titre'        => 'Chambre meublée à louer',
                'prix'         => 75000,
                'type'         => 'Location',
                'suffixe'      => '/mois',
                'localisation' => 'Dakar, Liberté 6',
                'vues'         => 890,
                'date'         => 'Il y a 3 jours',
                'image'        => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&q=80',
                'badge'        => 'Nouveau',
                'favori'       => false,
            ],
        ];
    }

    /**
     * Données temporaires des statistiques.
     *
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        return [
            [
                'valeur' => '14 618',
                'label'  => 'Annonces actives',
            ],
            [
                'valeur' => '38 400',
                'label'  => 'Utilisateurs inscrits',
            ],
            [
                'valeur' => '9 régions',
                'label'  => 'Couverture nationale',
            ],
            [
                'valeur' => '100%',
                'label'  => 'Gratuit pour tous',
            ],
        ];
    }
}