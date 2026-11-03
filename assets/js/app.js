document.addEventListener('DOMContentLoaded', () => {
    // Tutup menu navigasi mobile setelah salah satu tautan dipilih.
    const navigation = document.getElementById('mainNavigation');
    if (!navigation) return;

    navigation.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992 && navigation.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(navigation).hide();
            }
        });
    });
});
