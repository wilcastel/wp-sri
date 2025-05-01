jQuery(document).ready(function($) {
    // Confirmación antes de eliminar 
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('¿Estás seguro de eliminar este recurso?')) {
            e.preventDefault();
        }
    });
});