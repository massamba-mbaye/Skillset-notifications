(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Gestion des notifications dans le panneau
        setupNotificationPanel();
        
        // Gestion des notifications dans la barre d'administration
        setupAdminBarNotifications();
        
        // Vérification périodique des nouvelles notifications
        startPeriodicCheck();
    });
    
    /**
     * Configure les interactions dans le panneau de notifications
     */
    function setupNotificationPanel() {
        // Gestion du bouton "Marquer comme lu"
        $(document).on('click', '.skillset-mark-read', function(e) {
            e.preventDefault();
            
            const notificationId = $(this).data('id');
            const notificationItem = $(this).closest('.skillset-notification');
            
            markAsRead(notificationId, function(response) {
                if (response.success) {
                    // Mettre à jour l'interface
                    notificationItem.removeClass('skillset-notification-unread').addClass('skillset-notification-read');
                    $(e.target).remove();
                    
                    // Mettre à jour le compteur
                    updateUnreadCount(response.data.unread_count);
                }
            });
        });
    }
    
    /**
     * Configure les interactions pour les notifications dans la barre d'administration
     */
    function setupAdminBarNotifications() {
        // Gestion des clics sur les notifications dans la barre d'administration
        $(document).on('click', '.skillset-notification-item', function(e) {
            e.preventDefault();
            
            const notificationId = $(this).data('id');
            const isRead = $(this).data('read') === 1;
            
            if (!isRead) {
                markAsRead(notificationId, function(response) {
                    if (response.success) {
                        // Mettre à jour l'apparence
                        $(e.currentTarget).find('.unread').removeClass('unread').addClass('read');
                        $(e.currentTarget).data('read', 1);
                        
                        // Mettre à jour le compteur
                        updateUnreadCount(response.data.unread_count);
                    }
                });
            }
        });
    }
    
    /**
     * Marque une notification comme lue
     */
    function markAsRead(notificationId, callback) {
        $.ajax({
            url: skillset_notifications_params.ajax_url,
            type: 'POST',
            data: {
                action: 'skillset_mark_notification_read',
                nonce: skillset_notifications_params.nonce,
                notification_id: notificationId
            },
            success: callback,
            error: function() {
                console.error('Erreur lors de la mise à jour de la notification');
            }
        });
    }
    
    /**
     * Met à jour l'affichage du compteur de notifications non lues
     */
    function updateUnreadCount(count) {
        // Mettre à jour le compteur dans le panneau
        const countElement = $('.skillset-notifications-panel .skillset-notifications-count');
        
        if (countElement.length) {
            if (count > 0) {
                countElement.html('<span class="unread-count">' + count + ' non-lue' + (count > 1 ? 's' : '') + '</span>');
            } else {
                countElement.html('<span class="all-read">Tout est lu</span>');
            }
        }
        
        // Mettre à jour le compteur dans la barre d'administration
        const adminBarCount = $('#wp-admin-bar-skillset-notifications .skillset-notifications-count');
        
        if (adminBarCount.length) {
            adminBarCount.text(count);
            
            if (count > 0) {
                adminBarCount.addClass('has-unread');
            } else {
                adminBarCount.removeClass('has-unread');
            }
        }
    }
    
    /**
     * Démarre la vérification périodique des nouvelles notifications
     */
    function startPeriodicCheck() {
        // Vérifier toutes les 2 minutes (ajustable selon vos besoins)
        setInterval(function() {
            checkForNewNotifications();
        }, 120000);
    }
    
    /**
     * Vérifie s'il y a de nouvelles notifications
     */
    function checkForNewNotifications() {
        $.ajax({
            url: skillset_notifications_params.ajax_url,
            type: 'POST',
            data: {
                action: 'skillset_get_unread_count',
                nonce: skillset_notifications_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateUnreadCount(response.data.count);
                    
                    // Si de nouvelles notifications sont arrivées, afficher une popup
                    // Cette fonctionnalité pourrait être ajoutée ultérieurement
                }
            }
        });
    }
    
    /**
     * Affiche une notification popup
     */
    function showNotificationPopup(content) {
        // Créer l'élément de popup
        const popup = $(`
            <div class="skillset-notification-popup">
                <div class="skillset-notification-popup-header">
                    <h4 class="skillset-notification-popup-title">Nouvelle notification</h4>
                    <button class="skillset-notification-popup-close">&times;</button>
                </div>
                <div class="skillset-notification-popup-content">
                    ${content}
                </div>
                <div class="skillset-notification-popup-actions">
                    <button class="skillset-notification-popup-dismiss">Ignorer</button>
                    <button class="skillset-notification-popup-view">Voir toutes</button>
                </div>
            </div>
        `);
        
        // Ajouter au DOM
        $('body').append(popup);
        
        // Gérer les événements
        popup.find('.skillset-notification-popup-close, .skillset-notification-popup-dismiss').on('click', function() {
            popup.remove();
        });
        
        popup.find('.skillset-notification-popup-view').on('click', function() {
            window.location.href = notificationsPageUrl;
        });
        
        // Supprimer automatiquement après 10 secondes
        setTimeout(function() {
            popup.fadeOut(300, function() {
                popup.remove();
            });
        }, 10000);
    }
    
})(jQuery);