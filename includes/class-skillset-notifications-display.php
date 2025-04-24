<?php
/**
 * Gère l'affichage des notifications
 */
class SkillSet_Notifications_Display {

    /**
     * Constructeur
     */
    public function __construct() {
        // Rien à initialiser pour l'instant
    }
    
    /**
     * Enregistre les styles CSS
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'skillset-notifications-public',
            SKILLSET_NOTIFICATIONS_PLUGIN_URL . 'assets/css/skillset-notifications-public.css',
            array(),
            SKILLSET_NOTIFICATIONS_VERSION,
            'all'
        );
    }
    
    /**
     * Enregistre les scripts JS
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'skillset-notifications-public',
            SKILLSET_NOTIFICATIONS_PLUGIN_URL . 'assets/js/skillset-notifications-public.js',
            array('jquery'),
            SKILLSET_NOTIFICATIONS_VERSION,
            true
        );
        
        wp_localize_script(
            'skillset-notifications-public',
            'skillset_notifications_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skillset_notifications_nonce')
            )
        );
    }
    
    /**
     * Ajoute les notifications à la barre d'administration
     */
    public function add_notifications_to_admin_bar($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Créer une instance du gestionnaire de notifications
        $handler = new SkillSet_Notifications_Handler();
        
        // Récupérer le nombre de notifications non lues
        $unread_count = $handler->count_unread_notifications($user_id);
        
        // Ajouter le nœud principal
        $wp_admin_bar->add_node(array(
            'id'    => 'skillset-notifications',
            'title' => '<span class="ab-icon dashicons dashicons-bell"></span><span class="skillset-notifications-count' . ($unread_count > 0 ? ' has-unread' : '') . '">' . $unread_count . '</span>',
            'href'  => '#',
            'meta'  => array(
                'class' => 'skillset-notifications-menu',
                'title' => 'Notifications'
            )
        ));
        
        // Récupérer les notifications récentes
        $notifications = $handler->get_user_notifications($user_id, 5, 0, true);
        
        if (empty($notifications)) {
            $wp_admin_bar->add_node(array(
                'id'     => 'skillset-no-notifications',
                'parent' => 'skillset-notifications',
                'title'  => 'Aucune notification',
                'href'   => '#'
            ));
        } else {
            foreach ($notifications as $notification) {
                $wp_admin_bar->add_node(array(
                    'id'     => 'skillset-notification-' . $notification->id,
                    'parent' => 'skillset-notifications',
                    'title'  => '<span class="' . ($notification->is_read ? 'read' : 'unread') . '">' . esc_html($notification->content) . '</span>',
                    'href'   => '#',
                    'meta'   => array(
                        'class' => 'skillset-notification-item',
                        'data-id' => $notification->id,
                        'data-read' => $notification->is_read ? '1' : '0'
                    )
                ));
            }
            
            // Ajouter un lien vers toutes les notifications
            $wp_admin_bar->add_node(array(
                'id'     => 'skillset-all-notifications',
                'parent' => 'skillset-notifications',
                'title'  => 'Voir toutes les notifications',
                'href'   => get_permalink(get_option('skillset_notifications_page_id', 0))
            ));
        }
    }
    
    /**
     * Affiche le panneau de notifications
     */
    public function render_notifications_panel($limit = 10) {
        if (!is_user_logged_in()) {
            return '<div class="skillset-notifications-error">Veuillez vous connecter pour voir vos notifications.</div>';
        }
        
        $user_id = get_current_user_id();
        
        // Créer une instance du gestionnaire de notifications
        $handler = new SkillSet_Notifications_Handler();
        
        // Récupérer les notifications
        $notifications = $handler->get_user_notifications($user_id, $limit, 0, true);
        $unread_count = $handler->count_unread_notifications($user_id);
        
        // Commencer à capturer la sortie
        ob_start();
        
        // Inclure le template
        include SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'templates/notifications-panel.php';
        
        // Retourner la sortie capturée
        return ob_get_clean();
    }
    
    /**
     * Formate une notification pour l'affichage
     */
    public function format_notification($notification) {
        $class = $notification->is_read ? 'skillset-notification-read' : 'skillset-notification-unread';
        $time = human_time_diff(strtotime($notification->created_at), current_time('timestamp'));
        
        $output = '<div class="skillset-notification ' . $class . '" data-id="' . $notification->id . '">';
        $output .= '<div class="skillset-notification-content">' . esc_html($notification->content) . '</div>';
        $output .= '<div class="skillset-notification-meta">';
        $output .= '<span class="skillset-notification-time">Il y a ' . $time . '</span>';
        
        if (!$notification->is_read) {
            $output .= '<button class="skillset-mark-read" data-id="' . $notification->id . '">Marquer comme lu</button>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Formate une notification pour l'email
     */
    public function format_notification_email($notification) {
        $output = '<div style="padding: 15px; border-bottom: 1px solid #eee;">';
        $output .= '<p style="margin-top: 0; margin-bottom: 8px;">' . esc_html($notification->content) . '</p>';
        $output .= '<p style="margin: 0; font-size: 12px; color: #666;">';
        $output .= date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at));
        $output .= '</p>';
        $output .= '</div>';
        
        return $output;
    }
}