/* ============================================
   PETITES ANNONCES .SN — INTERACTIONS ESPACE CONNECTÉ
   ============================================
   Comportements purement présentationnels du layout connecté :

     - ouverture/fermeture de la sidebar en mobile ;
     - ouverture/fermeture du menu utilisateur ;
     - synchronisation des attributs ARIA.

   Règles :
     - aucune logique de rôle ni d'autorisation (le cloisonnement RBAC
       est assuré exclusivement côté serveur par AuthMiddleware et
       RoleMiddleware) ;
     - aucune requête réseau, aucun framework ;
     - le fonctionnement de base (navigation, déconnexion) ne dépend
       PAS de ce script : la sidebar utilise un <input type="checkbox">
       avec un <label> de recouvrement et le menu utilisateur un
       élément <details> natif. Ce fichier n'ajoute que du confort.
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    // ---------- Sidebar (mobile) ----------
    const sidebarToggle = document.getElementById('dash-sidebar-toggle');
    const burger = document.getElementById('dash-burger');
    const sidebarLinks = document.querySelectorAll('.dash-sidebar .dash-nav-link');

    // Reflète l'état de la sidebar dans l'attribut aria-expanded du bouton
    function syncSidebarAria() {
        if (burger && sidebarToggle) {
            burger.setAttribute('aria-expanded', sidebarToggle.checked ? 'true' : 'false');
        }
    }

    if (sidebarToggle) {
        // État initial : sidebar fermée en mobile (le CSS la masque)
        sidebarToggle.checked = false;
        syncSidebarAria();
        sidebarToggle.addEventListener('change', syncSidebarAria);
    }

    // Ferme la sidebar après un clic sur un lien (mobile)
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (sidebarToggle && sidebarToggle.checked) {
                sidebarToggle.checked = false;
                syncSidebarAria();
            }
        });
    });

    // Le déclencheur est un <label> : Enter/Espace doivent aussi le piloter
    if (burger && sidebarToggle) {
        burger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                sidebarToggle.checked = !sidebarToggle.checked;
                syncSidebarAria();
            }
        });
    }

    // ---------- Menu utilisateur ----------
    const userMenu = document.getElementById('dash-user-menu');

    if (userMenu) {
        const userMenuSummary = userMenu.querySelector('summary');

        // Reflète l'état du menu dans aria-expanded
        function syncUserMenuAria() {
            if (userMenuSummary) {
                userMenuSummary.setAttribute('aria-expanded', userMenu.open ? 'true' : 'false');
            }
        }

        // <details> émet un événement « toggle » à chaque changement d'état
        userMenu.addEventListener('toggle', syncUserMenuAria);
        syncUserMenuAria();

        // Fermeture au clic en dehors du menu
        document.addEventListener('click', function (event) {
            if (userMenu.open && !userMenu.contains(event.target)) {
                userMenu.open = false;
            }
        });
    }

    // ---------- Fermeture au clavier (Échap) ----------
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (userMenu && userMenu.open) {
            userMenu.open = false;
        }

        if (sidebarToggle && sidebarToggle.checked) {
            sidebarToggle.checked = false;
            syncSidebarAria();
        }
    });
});
