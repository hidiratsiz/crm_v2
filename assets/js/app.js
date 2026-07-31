// JobPro - global JS
// Phase 2'de dinamik teklif formu ve canli fiyat hesaplama burada Fetch API ile eklenecek.

document.addEventListener('DOMContentLoaded', function () {
    console.log('JobPro yuklendi.');

    // Mobil hamburger menu: sidebar varsayilan olarak gizli, buton/backdrop ile ac-kapat.
    var toggleBtn = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');

    if (toggleBtn && sidebar && backdrop) {
        var closeSidebar = function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            toggleBtn.setAttribute('aria-expanded', 'false');
        };

        toggleBtn.addEventListener('click', function () {
            var isOpen = sidebar.classList.toggle('show');
            backdrop.classList.toggle('show', isOpen);
            toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        backdrop.addEventListener('click', closeSidebar);

        // Mobilde bir menu linkine tiklaninca sayfa zaten yenilenecek, ama
        // gecis sirasinda menu acik kalmasin diye kapatiyoruz.
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    }
});
