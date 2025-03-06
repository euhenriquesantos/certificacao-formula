document.addEventListener('DOMContentLoaded', function() {
    // Confirmação para logout
    const logoutLink = document.querySelector('a[href="logout.php"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            if (!confirm('Deseja realmente sair?')) {
                e.preventDefault();
            }
        });
    }
});