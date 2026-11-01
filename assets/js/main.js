/**
 * Sistema de Gestión Educativa
 * Unidad Educativa Pedro Garcia Leal
 * JavaScript Principal
 */

// Toggle del menú móvil
function toggleMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('active');
    }
}

// Cerrar menú al hacer clic en un enlace
document.addEventListener('DOMContentLoaded', function() {
    const mobileLinks = document.querySelectorAll('.mobile-link');
    mobileLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
            }
        });
    });
});
