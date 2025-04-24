<?php
/**
 * Gère l'interface d'administration du plugin
 */
class SkillSet_Notifications_Admin {

    /**
     * Constructeur
     */
    public function __construct() {
        // Rien à initialiser pour l'instant
    }
    
    /**
     * Enregistre les styles CSS pour l'admin
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'skillset-notifications-admin',
            SKILLSET_NOTIFICATIONS_PLUGIN_URL . 'assets/css/skillset-notifications-admin.css',
            array(),
            SKILLSET_NOTIFICATIONS_VERSION,
            'all'
        );
    }
    
    /**
     * Enregistre les scripts JS pour l'admin
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'skillset-notifications-admin',
            SKILLSET_NOTIFICATIONS_PLUGIN_URL . 'assets/js/skillset-notifications-admin.js',
            array('jquery'),
            SKILLSET_NOTIFICATIONS_VERSION,
            true
        );
        
        wp_localize_script(
            'skillset-notifications-admin',
            'skillset_notifications_admin_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skillset_notifications_admin_nonce')
            )
        );
    }
    
    /**
     * Ajoute le menu d'administration
     */
    public function add_admin_menu() {
        // Menu principal sous SkillSet
        add_submenu_page(
            'skillset-competency',
            'Notifications',
            'Notifications',
            'manage_options',
            'skillset-notifications',
            array($this, 'display_admin_page')
        );
        
        // Si le plugin de compétences n'est pas actif, créer un menu principal
        if (!class_exists('SkillSet_Competency_Manager')) {
            add_menu_page(
                'SkillSet Notifications',
                'SkillSet Notifications',
                'manage_options',
                'skillset-notifications',
                array($this, 'display_admin_page'),
                'dashicons-bell',
                32
            );
        }
    }
    
    /**
     * Affiche la page d'administration principale
     */
    public function display_admin_page() {
        // Traitement des actions
        if (isset($_POST['action']) && isset($_POST['skillset_notifications_nonce']) && wp_verify_nonce($_POST['skillset_notifications_nonce'], 'skillset_notifications_settings')) {
            if ($_POST['action'] === 'update_settings') {
                $this->handle_settings_update();
            } elseif ($_POST['action'] === 'send_test_notification') {
                $this->handle_send_test_notification();
            } elseif ($_POST['action'] === 'clear_old_notifications') {
                $this->handle_clear_old_notifications();
            } elseif ($_POST['action'] === 'update_email_templates') {
                $this->handle_update_email_templates();
            }
        }
        
        // Récupérer les statistiques
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        
        $total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $unread_notifications = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0");
        $total_users_with_notifications = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name");
        
        // Récupérer les notifications récentes
        $recent_notifications = $wpdb->get_results("
            SELECT n.*, u.display_name
            FROM $table_name n
            JOIN {$wpdb->users} u ON n.user_id = u.ID
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        
        // Récupérer les réglages
        $enable_email = get_option('skillset_notifications_enable_email', 'off');
        $enable_push = get_option('skillset_notifications_enable_push', 'off');
        $per_page = get_option('skillset_notifications_per_page', 10);
        $auto_delete_days = get_option('skillset_notifications_auto_delete_days', 30);
        $page_id = get_option('skillset_notifications_page_id', 0);
        
        // Afficher le template
        include SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'templates/admin-settings.php';
        
        // Ajouter l'onglet pour les modèles d'emails
        ?>
        <div class="wrap">
            <h1>SkillSet Notifications</h1>
            
            <?php settings_errors('skillset_notifications'); ?>
            
            <div class="skillset-admin-dashboard">
                <div class="skillset-admin-tabs-container">
                    <nav class="nav-tab-wrapper">
                        <a href="#settings" class="nav-tab">Réglages</a>
                        <a href="#notifications" class="nav-tab">Notifications récentes</a>
                        <a href="#maintenance" class="nav-tab">Maintenance</a>
                        <a href="#test" class="nav-tab">Test</a>
                        <a href="#email_templates" class="nav-tab">Modèles d'emails</a>
                    </nav>
                    
                    <div id="email_templates" class="skillset-admin-tab-content" style="display: none;">
                        <h2>Modèles d'emails</h2>
                        
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_email_templates">
                            <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                            
                            <div class="skillset-form-section">
                                <h3>Configuration générale des emails</h3>
                                
                                <div class="skillset-form-row">
                                    <label for="email_from">Adresse email d'expédition</label>
                                    <input type="email" name="email_from" id="email_from" value="<?php echo esc_attr(get_option('skillset_notifications_email_from', get_option('admin_email'))); ?>" class="regular-text">
                                </div>
                                <div class="skillset-form-row">
                                    <label for="email_name">Nom d'expéditeur</label>
                                    <input type="text" name="email_name" id="email_name" value="<?php echo esc_attr(get_option('skillset_notifications_email_name', get_bloginfo('name'))); ?>" class="regular-text">
                                </div>
                                
                                <div class="skillset-form-row">
                                    <label>
                                        <input type="checkbox" name="email_html" <?php checked(get_option('skillset_notifications_email_html', 'on'), 'on'); ?>>
                                        Activer le format HTML pour les emails
                                    </label>
                                </div>
                            </div>
                            
                            <div class="skillset-form-section">
                                <h3>Modèles pour les types de notifications</h3>
                                <p>Vous pouvez utiliser les shortcodes suivants dans vos modèles:</p>
                                <ul class="skillset-shortcodes-list">
                                    <li><code>%user_name%</code> - Nom complet de l'utilisateur</li>
                                    <li><code>%user_first_name%</code> - Prénom de l'utilisateur</li>
                                    <li><code>%user_last_name%</code> - Nom de famille de l'utilisateur</li>
                                    <li><code>%competency_name%</code> - Nom de la compétence (pour les notifications de type "competency_validated")</li>
                                    <li><code>%competency_description%</code> - Description de la compétence</li>
                                    <li><code>%competency_level%</code> - Niveau de la compétence</li>
                                    <li><code>%badge_name%</code> - Nom du badge (pour les notifications de type "badge_awarded")</li>
                                    <li><code>%badge_description%</code> - Description du badge</li>
                                    <li><code>%parcours%</code> - Nom du parcours concerné</li>
                                    <li><code>%site_name%</code> - Nom du site</li>
                                    <li><code>%site_url%</code> - URL du site</li>
                                </ul>
                                
                                <h4>Compétence validée</h4>
                                <div class="skillset-form-row">
                                    <label for="subject_competency">Objet de l'email</label>
                                    <input type="text" name="subject_competency" id="subject_competency" value="<?php echo esc_attr(get_option('skillset_notifications_subject_competency', 'Félicitations! Nouvelle compétence validée sur %site_name%')); ?>" class="regular-text">
                                </div>
                                
                                <div class="skillset-form-row">
                                    <label for="template_competency">Contenu de l'email</label>
                                    <textarea name="template_competency" id="template_competency" rows="8" class="large-text"><?php echo esc_textarea(get_option('skillset_notifications_template_competency', "Bonjour %user_name%,\n\nFélicitations! Vous avez acquis une nouvelle compétence dans votre parcours %parcours%.\n\nCompétence: %competency_name%\nNiveau: %competency_level%\nDescription: %competency_description%\n\nContinuez votre apprentissage pour développer davantage vos compétences!")); ?></textarea>
                                </div>
                                
                                <h4>Badge obtenu</h4>
                                <div class="skillset-form-row">
                                    <label for="subject_badge">Objet de l'email</label>
                                    <input type="text" name="subject_badge" id="subject_badge" value="<?php echo esc_attr(get_option('skillset_notifications_subject_badge', 'Vous avez obtenu un nouveau badge sur %site_name%!')); ?>" class="regular-text">
                                </div>
                                
                                <div class="skillset-form-row">
                                    <label for="template_badge">Contenu de l'email</label>
                                    <textarea name="template_badge" id="template_badge" rows="8" class="large-text"><?php echo esc_textarea(get_option('skillset_notifications_template_badge', "Bonjour %user_name%,\n\nFélicitations! Vous avez obtenu un nouveau badge dans votre parcours %parcours%.\n\nBadge: %badge_name%\nDescription: %badge_description%\n\nPartagez cette réussite avec vos amis et collègues!")); ?></textarea>
                                </div>
                            </div>
                            
                            <p class="submit">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modèles">
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Gestion des onglets
            $('.skillset-admin-tabs-container .nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Activer l'onglet
                $('.skillset-admin-tabs-container .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Afficher le contenu
                $('.skillset-admin-tab-content').hide();
                $(target).show();
            });
            
            // Activer le premier onglet par défaut
            $('.skillset-admin-tabs-container .nav-tab:first').click();
        });
        </script>
        <?php
    }
    
    /**
     * Gère la mise à jour des réglages
     */
    private function handle_settings_update() {
        $enable_email = isset($_POST['enable_email']) ? 'on' : 'off';
        $enable_push = isset($_POST['enable_push']) ? 'on' : 'off';
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $auto_delete_days = isset($_POST['auto_delete_days']) ? intval($_POST['auto_delete_days']) : 30;
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        update_option('skillset_notifications_enable_email', $enable_email);
        update_option('skillset_notifications_enable_push', $enable_push);
        update_option('skillset_notifications_per_page', $per_page);
        update_option('skillset_notifications_auto_delete_days', $auto_delete_days);
        update_option('skillset_notifications_page_id', $page_id);
        
        add_settings_error('skillset_notifications', 'settings-updated', 'Réglages mis à jour avec succès', 'success');
    }

    /**
     * Gère l'envoi d'une notification de test
     */
    private function handle_send_test_notification() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        
        if (!isset($_POST['user_id']) || empty($_POST['user_id']) || 
            !isset($_POST['test_message']) || empty($_POST['test_message'])) {
            add_settings_error('skillset_notifications', 'test-error', 'Utilisateur ou message manquant');
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $message = sanitize_text_field($_POST['test_message']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id'      => $user_id,
                'type'         => 'test',
                'content'      => $message,
                'related_id'   => null,
                'related_type' => null,
                'metadata'     => null,
                'is_read'      => 0,
                'created_at'   => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if (false === $result) {
            // Journaliser l'erreur en arrière-plan
            error_log('Erreur SQL Notification de test : ' . $wpdb->last_error);
            
            add_settings_error(
                'skillset_notifications', 
                'test-error', 
                'Erreur lors de l\'envoi de la notification de test. Détails: ' . esc_html($wpdb->last_error)
            );
        } else {
            add_settings_error(
                'skillset_notifications', 
                'test-success', 
                'Notification de test envoyée avec succès', 
                'success'
            );
            
            // Déclencher une action pour d'éventuelles intégrations
            do_action('skillset_notification_sent', $user_id, 'test', $message);
        }
    }

    
    /**
     * Gère la suppression des anciennes notifications
     */
    private function handle_clear_old_notifications() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $read_only = isset($_POST['read_only']) && $_POST['read_only'] === 'on';
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $where = "created_at < %s";
        $params = array($date);
        
        if ($read_only) {
            $where .= " AND is_read = 1";
        }
        
        $query = $wpdb->prepare("DELETE FROM $table_name WHERE $where", $params);
        $count = $wpdb->query($query);
        
        if ($count !== false) {
            add_settings_error('skillset_notifications', 'clear-success', $count . ' notifications supprimées avec succès', 'success');
        } else {
            add_settings_error('skillset_notifications', 'clear-error', 'Erreur lors de la suppression des notifications');
        }
    }
    
    /**
     * Gère la mise à jour des modèles d'emails
     */
    private function handle_update_email_templates() {
        $email_from = sanitize_email($_POST['email_from']);
        $email_name = sanitize_text_field($_POST['email_name']);
        $email_html = isset($_POST['email_html']) ? 'on' : 'off';
        $subject_competency = sanitize_text_field($_POST['subject_competency']);
        $template_competency = wp_kses_post($_POST['template_competency']);
        $subject_badge = sanitize_text_field($_POST['subject_badge']);
        $template_badge = wp_kses_post($_POST['template_badge']);
        
        update_option('skillset_notifications_email_from', $email_from);
        update_option('skillset_notifications_email_name', $email_name);
        update_option('skillset_notifications_email_html', $email_html);
        update_option('skillset_notifications_subject_competency', $subject_competency);
        update_option('skillset_notifications_template_competency', $template_competency);
        update_option('skillset_notifications_subject_badge', $subject_badge);
        update_option('skillset_notifications_template_badge', $template_badge);
        
        add_settings_error('skillset_notifications', 'templates-updated', 'Modèles d\'emails mis à jour avec succès', 'success');
    }
}