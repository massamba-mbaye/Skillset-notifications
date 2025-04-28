<?php
/**
 * Gestionnaire de notifications
 */
class SkillSet_Notifications_Handler {

    /**
     * Table des notifications
     */
    private $table_name;
    
    /**
     * Constructeur
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'skillset_notifications';
    }
    
    /**
     * Ajoute une notification
     */
    public function add_notification($user_id, $type, $content, $related_id = null, $related_type = null, $metadata = array()) {
        global $wpdb;
        
        // Encoder les métadonnées en JSON
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'type' => $type,
                'content' => $content,
                'related_id' => $related_id,
                'related_type' => $related_type,
                'metadata' => $metadata_json,
                'is_read' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $notification_id = $wpdb->insert_id;
            
            // Toujours envoyer un email, quel que soit le réglage
            $this->send_email_notification($user_id, $type, $content, $notification_id, $metadata);
            
            // Envoyer une notification push si activée
            if (get_option('skillset_notifications_enable_push', 'off') === 'on') {
                $this->send_push_notification($user_id, $type, $content, $notification_id, $metadata);
            }
            
            return $notification_id;
        }
        
        return false;
    }
    
    /**
     * Récupère les notifications d'un utilisateur
     */
    public function get_user_notifications($user_id, $limit = 10, $offset = 0, $include_read = true) {
        global $wpdb;
        
        $where = "user_id = %d";
        $params = array($user_id);
        
        if (!$include_read) {
            $where .= " AND is_read = 0";
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE $where 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            array_merge($params, array($limit, $offset))
        );
        
        $notifications = $wpdb->get_results($query);
        
        // Décoder les métadonnées JSON pour chaque notification
        foreach ($notifications as $notification) {
            if (!empty($notification->metadata)) {
                $notification->metadata_array = json_decode($notification->metadata, true);
            } else {
                $notification->metadata_array = array();
            }
        }
        
        return $notifications;
    }
    
    /**
     * Compte les notifications non lues
     */
    public function count_unread_notifications($user_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE user_id = %d AND is_read = 0",
            $user_id
        );
        
        return $wpdb->get_var($query);
    }
    
    /**
     * Marque une notification comme lue (AJAX)
     */
    public function mark_notification_read() {
        // Vérifier le nonce
        check_ajax_referer('skillset_notifications_nonce', 'nonce');
        
        if (!isset($_POST['notification_id'])) {
            wp_send_json_error(array('message' => 'ID de notification manquant'));
            return;
        }
        
        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'Utilisateur non connecté'));
            return;
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('is_read' => 1),
            array('id' => $notification_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Notification marquée comme lue',
                'unread_count' => $this->count_unread_notifications($user_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la mise à jour'));
        }
    }
    
    /**
     * Récupère le nombre de notifications non lues (AJAX)
     */
    public function get_unread_count() {
        // Vérifier le nonce
        check_ajax_referer('skillset_notifications_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'Utilisateur non connecté'));
            return;
        }
        
        $count = $this->count_unread_notifications($user_id);
        
        wp_send_json_success(array('count' => $count));
    }
    
    /**
     * Récupère les notifications récentes (AJAX)
     */
    public function get_recent_notifications() {
        // Vérifier le nonce
        check_ajax_referer('skillset_notifications_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'Utilisateur non connecté'));
            return;
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        $notifications = $this->get_user_notifications($user_id, $limit, 0, true);
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => $this->count_unread_notifications($user_id)
        ));
    }
    
    /**
     * Envoie des notifications supplémentaires (email, push)
     */
    private function send_additional_notifications($user_id, $type, $content, $notification_id, $metadata = array()) {
        // Vérifier si les notifications par email sont activées
        if (get_option('skillset_notifications_enable_email', 'off') === 'on') {
            $this->send_email_notification($user_id, $type, $content, $notification_id, $metadata);
        }
        
        // Vérifier si les notifications push sont activées
        if (get_option('skillset_notifications_enable_push', 'off') === 'on') {
            $this->send_push_notification($user_id, $type, $content, $notification_id, $metadata);
        }
    }
    
    /**
     * Envoie une notification par email
     */
    private function send_email_notification($user_id, $type, $content, $notification_id, $metadata = array()) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        // Récupérer les paramètres d'email personnalisés
        $from_email = get_option('skillset_notifications_email_from', get_option('admin_email'));
        $from_name = get_option('skillset_notifications_email_name', get_bloginfo('name'));
        $use_html = get_option('skillset_notifications_email_html', 'on') === 'on';
        
        // Personnaliser l'objet selon le type de notification
        $subject_templates = array(
            'competency_validated' => get_option('skillset_notifications_subject_competency', 'Félicitations! Nouvelle compétence validée sur %site_name%'),
            'badge_awarded' => get_option('skillset_notifications_subject_badge', 'Vous avez obtenu un nouveau badge sur %site_name%!'),
            'default' => 'Nouvelle notification sur %site_name%'
        );
        
        $subject_template = isset($subject_templates[$type]) ? $subject_templates[$type] : $subject_templates['default'];
        $subject = $this->process_email_shortcodes($subject_template, $user, $type, $metadata);
        
        // Headers pour l'email
        $headers = array();
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        
        if ($use_html) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            
            // Récupérer le modèle HTML selon le type
            $template_keys = array(
                'competency_validated' => 'skillset_notifications_template_competency',
                'badge_awarded' => 'skillset_notifications_template_badge'
            );
            
            $template_key = isset($template_keys[$type]) ? $template_keys[$type] : '';
            
            if (!empty($template_key)) {
                $email_content = get_option($template_key, $content);
                $message = $this->get_email_html_template($email_content, $user, $type, $metadata);
            } else {
                // Modèle par défaut
                $message = $this->get_email_html_template($content, $user, $type, $metadata);
            }
        } else {
            // Message en texte brut
            $template_keys = array(
                'competency_validated' => 'skillset_notifications_template_competency',
                'badge_awarded' => 'skillset_notifications_template_badge'
            );
            
            $template_key = isset($template_keys[$type]) ? $template_keys[$type] : '';
            
            if (!empty($template_key)) {
                $message = get_option($template_key, $content);
            } else {
                $message = "Bonjour " . $user->display_name . ",\n\n";
                $message .= "Vous avez reçu une nouvelle notification :\n\n";
                $message .= $content . "\n\n";
                $message .= "Connectez-vous pour en savoir plus : " . get_site_url() . "\n\n";
                $message .= "L'équipe " . get_bloginfo('name') . "\n";
            }
            
            // Traiter les shortcodes
            $message = $this->process_email_shortcodes($message, $user, $type, $metadata);
        }
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Génère un template HTML pour l'email
     */
    private function get_email_html_template($content, $user, $type, $metadata) {
        // URL de la page des notifications (si définie)
        $notifications_page_id = get_option('skillset_notifications_page_id', 0);
        $notifications_url = $notifications_page_id > 0 ? get_permalink($notifications_page_id) : get_site_url();
        
        // Récupérer le logo du site
        $logo_url = get_option('skillset_notifications_email_logo', '');
        if (empty($logo_url)) {
            // Utiliser le logo personnalisé du site si disponible
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo_url = wp_get_attachment_image_src($custom_logo_id, 'full')[0];
            }
        }
        
        // Couleur principale (personnalisable)
        $primary_color = get_option('skillset_notifications_email_color', '#4e2a84');
        
        // Contenu spécifique selon le type
        $button_text = 'Voir les détails';
        
        switch ($type) {
            case 'competency_validated':
                $button_text = 'Voir votre progression';
                break;
                
            case 'badge_awarded':
                $button_text = 'Voir vos badges';
                break;
        }
        
        // Traiter les shortcodes dans le contenu
        $formatted_content = $this->process_email_shortcodes($content, $user, $type, $metadata);
        
        // Construction du template HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; }
                .header { background-color: ' . $primary_color . '; padding: 20px; text-align: center; }
                .header img { max-height: 60px; }
                .content { padding: 20px; }
                .notification { background-color: #f9f9f9; border-left: 4px solid ' . $primary_color . '; padding: 15px; margin: 15px 0; }
                .button { display: inline-block; background-color: ' . $primary_color . '; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 4px; margin-top: 15px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    ' . ($logo_url ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '">' : '<h1 style="color:#fff;margin:0;">' . get_bloginfo('name') . '</h1>') . '
                </div>
                <div class="content">
                    ' . nl2br($formatted_content) . '
                    <center>
                        <a href="' . esc_url($notifications_url) . '" class="button">' . $button_text . '</a>
                    </center>
                </div>
                <div class="footer">
                    <p>Cet email a été envoyé depuis <a href="' . get_site_url() . '">' . get_bloginfo('name') . '</a></p>
                    <p>Si vous ne souhaitez plus recevoir ces notifications, veuillez mettre à jour vos <a href="' . get_site_url() . '/mon-compte">préférences</a>.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Traite les shortcodes dans le contenu des emails
     */
    private function process_email_shortcodes($content, $user, $notification_type, $metadata) {
        // Shortcodes de base (toujours disponibles)
        $shortcodes = array(
            '%user_name%' => $user->display_name,
            '%user_email%' => $user->user_email,
            '%user_first_name%' => $user->first_name,
            '%user_last_name%' => $user->last_name,
            '%site_name%' => get_bloginfo('name'),
            '%site_url%' => get_site_url(),
            '%date%' => date_i18n(get_option('date_format')),
            '%time%' => date_i18n(get_option('time_format')),
            '%notification_type%' => $notification_type
        );
        
        // Ajouter les métadonnées comme shortcodes
        if (!empty($metadata) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $shortcodes['%' . $key . '%'] = $value;
            }
        }
        
        // Remplacer tous les shortcodes
        foreach ($shortcodes as $code => $value) {
            $content = str_replace($code, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Envoie une notification push via OneSignal
     */
    private function send_push_notification($user_id, $type, $content, $notification_id, $metadata = array()) {
        // Vérifier si OneSignal est configuré
        $app_id = get_option('skillset_notifications_onesignal_app_id', '');
        $api_key = get_option('skillset_notifications_onesignal_api_key', '');
        
        if (empty($app_id) || empty($api_key)) {
            return false;
        }
        
        // Récupérer l'identifiant OneSignal de l'utilisateur (stocké en métadonnée utilisateur)
        $player_id = get_user_meta($user_id, 'onesignal_player_id', true);
        
        if (empty($player_id)) {
            return false;
        }
        
        // Déterminer le titre et l'URL en fonction du type de notification
        $title = get_bloginfo('name');
        $url = get_site_url();
        
        switch ($type) {
            case 'competency_validated':
                $title = 'Nouvelle compétence acquise !';
                if (!empty($metadata['competency_name'])) {
                    $content = 'Vous avez acquis la compétence : ' . $metadata['competency_name'];
                }
                break;
                
            case 'badge_awarded':
                $title = 'Nouveau badge obtenu !';
                if (!empty($metadata['badge_name'])) {
                    $content = 'Vous avez obtenu le badge : ' . $metadata['badge_name'];
                }
                break;
        }
        
        // Préparer les données pour OneSignal
        $fields = array(
            'app_id' => $app_id,
            'include_player_ids' => array($player_id),
            'headings' => array('en' => $title),
            'contents' => array('en' => $content),
            'url' => $url,
            'data' => array(
                'notification_id' => $notification_id,
                'type' => $type
            )
        );
        
        // Convertir en JSON
        $fields = json_encode($fields);
        
        // Envoyer la requête à l'API OneSignal
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $api_key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Logger la réponse pour débogage
        error_log('OneSignal response: ' . $response);
        
        return $response;
    }
    
    /**
     * Supprime les anciennes notifications
     */
    public function delete_old_notifications() {
        global $wpdb;
        
        $days = get_option('skillset_notifications_auto_delete_days', 30);
        
        if ($days <= 0) {
            return; // Ne pas supprimer si désactivé
        }
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $query = $wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE created_at < %s AND is_read = 1",
            $date
        );
        
        $wpdb->query($query);
    }
    
    /**
     * Gère la notification pour une compétence validée
     */
    public function handle_competency_validated($user_id, $competency_id, $validated_by) {
        // Si le plugin de compétences n'est pas actif, ne rien faire
        if (!class_exists('SkillSet_Competency_Model')) {
            return;
        }
        
        // Récupérer les détails complets de la compétence
        $model = new SkillSet_Competency_Model();
        $competency = $model->get_competency($competency_id);
        
        if (!$competency) {
            return;
        }
        
        $content = "Félicitations ! Vous avez acquis la compétence \"" . $competency->name . "\".";
        
        // Stocker les données complètes pour pouvoir les utiliser dans les emails
        $metadata = array(
            'competency_name' => $competency->name,
            'competency_description' => $competency->description,
            'competency_level' => $competency->level,
            'parcours' => $competency->parcours,
            'validated_by' => $validated_by
        );
        
        $this->add_notification(
            $user_id,
            'competency_validated',
            $content,
            $competency_id,
            'competency',
            $metadata  // Nouveau paramètre pour stocker les métadonnées
        );
    }
    
    /**
     * Gère la notification pour un badge obtenu
     */
    public function handle_badge_awarded($user_id, $badge_id) {
        // Si le plugin de compétences n'est pas actif, ne rien faire
        if (!class_exists('SkillSet_Competency_Model')) {
            return;
        }
        
        // Récupérer les détails du badge
        $model = new SkillSet_Competency_Model();
        $badge = $model->get_badge($badge_id);
        
        if (!$badge) {
            return;
        }
        
        $content = "Félicitations ! Vous avez obtenu le badge \"" . $badge->name . "\".";
        
        // Stocker les données complètes
        $metadata = array(
            'badge_name' => $badge->name,
            'badge_description' => $badge->description,
            'badge_image' => $badge->image_url,
            'parcours' => $badge->parcours
        );
        
        $this->add_notification(
            $user_id,
            'badge_awarded',
            $content,
            $badge_id,
            'badge',
            $metadata
        );
    }
    
    /**
     * Gère la notification pour une conversation IA
     */

    public function handle_conversation_processed($content, $user_id, $parcours) {
        // Ne rien faire - nous ne voulons plus générer de notifications pour les conversations
        return;
    }
}