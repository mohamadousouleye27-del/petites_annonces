/* ============================================
   PETITES ANNONCES .SN — INTERACTIONS HOMEPAGE
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    // ---------- Menu mobile ----------
    const menuButton = document.getElementById('menu-mobile-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuButton && mobileMenu) {
        menuButton.addEventListener('click', function () {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                menuButton.setAttribute('aria-expanded', 'true');
            } else {
                mobileMenu.classList.add('hidden');
                menuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ---------- Boutons favori (visuel uniquement) ----------
    const favoriButtons = document.querySelectorAll('.favori-btn');

    favoriButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            btn.classList.toggle('active');
        });
    });

    // ---------- Animation d'apparition au scroll ----------
    const animatedElements = document.querySelectorAll('.animate-fade-in-up');

    if ('IntersectionObserver' in window && animatedElements.length > 0) {
        const observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1 }
        );

        animatedElements.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // Fallback : affiche tout si pas d'IntersectionObserver
        animatedElements.forEach(function (el) {
            el.style.opacity = '1';
        });
    }
});