(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Prévisualisation du message de test
        $('#test_message').on('input', function() {
            const message = $(this).val();
            updateTestPreview(message);
        });
        
        // Initialiser la prévisualisation
        updateTestPreview($('#test_message').val());
        
        // Actualisation automatique des statistiques
        setupStatsRefresh();
    });
    
    /**
     * Met à jour la prévisualisation de la notification de test
     */
    function updateTestPreview(message) {
        // Vérifier si l'élément de prévisualisation existe
        let preview = $('.skillset-notification-test-preview');
        
        if (preview.length === 0) {
            // Créer l'élément s'il n'existe pas
            preview = $('<div class="skillset-notification-test-preview"></div>');
            $('#test_message').after(preview);
        }
        
        // Mettre à jour le contenu
        preview.html('<strong>Prévisualisation:</strong><br>' + message);
    }
    
    /**
     * Configure l'actualisation automatique des statistiques
     */
    function setupStatsRefresh() {
        // Actualiser toutes les 5 minutes (ajustable selon vos besoins)
        setInterval(function() {
            if ($('#notifications').is(':visible')) {
                refreshNotificationsList();
            }
        }, 300000);
    }
    
    /**
     * Actualise la liste des notifications récentes
     */
    function refreshNotificationsList() {
        // Vous pourriez implémenter une requête AJAX ici pour actualiser la liste sans recharger la page
        // Cette fonctionnalité pourrait être ajoutée ultérieurement
    }
    
})(jQuery);