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
        
        // Ajouter Select2 pour les sélecteurs améliorés
        wp_enqueue_style(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
            array(),
            '4.0.13'
        );
    }
    
    /**
     * Enregistre les scripts JS pour l'admin
     */
    public function enqueue_scripts() {
        // Ajouter Select2 pour les sélecteurs améliorés
        wp_enqueue_script(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
            array('jquery'),
            '4.0.13',
            true
        );
        
        wp_enqueue_script(
            'skillset-notifications-admin',
            SKILLSET_NOTIFICATIONS_PLUGIN_URL . 'assets/js/skillset-notifications-admin.js',
            array('jquery', 'select2'),
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
        // Créer un menu principal pour les notifications
        add_menu_page(
            'SkillSet Notifications',
            'SkillSet Notifications',
            'manage_options',
            'skillset-notifications',
            array($this, 'display_admin_page'),
            'dashicons-bell',
            32
        );
        
        // Ajouter les sous-menus
        add_submenu_page(
            'skillset-notifications',
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            'skillset-notifications',
            array($this, 'display_admin_page')
        );
        
        add_submenu_page(
            'skillset-notifications',
            'Notifications par lots',
            'Notifications par lots',
            'manage_options',
            'skillset-notifications-bulk',
            array($this, 'display_bulk_notifications_page')
        );
        
        add_submenu_page(
            'skillset-notifications',
            'Top Bar',
            'Top Bar',
            'manage_options',
            'skillset-notifications-topbar',
            array($this, 'display_topbar_page')
        );
        
        add_submenu_page(
            'skillset-notifications',
            'Réglages',
            'Réglages',
            'manage_options',
            'skillset-notifications-settings',
            array($this, 'display_settings_page')
        );
        
        // Suppression de la partie concernant le menu sous SkillSet Competency
    }
    
    /**
     * Affiche la page d'administration principale (tableau de bord)
     */
    public function display_admin_page() {
        // Récupérer les statistiques
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        $logs_table = $wpdb->prefix . 'skillset_notification_logs';
        
        // Statistiques des notifications
        $total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $unread_notifications = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0");
        $total_users_with_notifications = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name");
        
        // Statistiques des logs
        $total_emails_sent = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE type = 'email'");
        $total_push_sent = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE type = 'push'");
        $total_success = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE status = 'success'");
        $total_failures = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE status = 'failure'");
        
        // Récupérer les dernières notifications
        $recent_notifications = $wpdb->get_results("
            SELECT n.*, u.display_name
            FROM $table_name n
            JOIN {$wpdb->users} u ON n.user_id = u.ID
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        
        // Récupérer les derniers logs
        $recent_logs = $wpdb->get_results("
            SELECT l.*, u.display_name
            FROM $logs_table l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            ORDER BY l.created_at DESC
            LIMIT 10
        ");
        
        // Statistiques par type de notification
        $notification_types = $wpdb->get_results("
            SELECT type, COUNT(*) as count
            FROM $table_name
            GROUP BY type
            ORDER BY count DESC
        ");
        
        ?>
        <div class="wrap">
            <h1>Tableau de bord SkillSet Notifications</h1>
            
            <div class="skillset-dashboard-stats">
                <div class="skillset-stat-box">
                    <h2>Notifications</h2>
                    <div class="skillset-stat-grid">
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $total_notifications; ?></span>
                            <span class="skillset-stat-label">Total</span>
                        </div>
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $unread_notifications; ?></span>
                            <span class="skillset-stat-label">Non lues</span>
                        </div>
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $total_users_with_notifications; ?></span>
                            <span class="skillset-stat-label">Utilisateurs</span>
                        </div>
                    </div>
                </div>
                
                <div class="skillset-stat-box">
                    <h2>Envois</h2>
                    <div class="skillset-stat-grid">
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $total_emails_sent; ?></span>
                            <span class="skillset-stat-label">Emails</span>
                        </div>
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $total_push_sent; ?></span>
                            <span class="skillset-stat-label">Push</span>
                        </div>
                        <div class="skillset-stat-item">
                            <span class="skillset-stat-value"><?php echo $total_success; ?></span>
                            <span class="skillset-stat-label">Réussis</span>
                        </div>
                        <div class="skillset-stat-item failure">
                            <span class="skillset-stat-value"><?php echo $total_failures; ?></span>
                            <span class="skillset-stat-label">Échoués</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="skillset-dashboard-sections">
                <div class="skillset-section">
                    <h2>Notifications récentes</h2>
                    <?php if (empty($recent_notifications)): ?>
                        <p>Aucune notification récente.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Contenu</th>
                                    <th>Lu</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_notifications as $notification): ?>
                                <tr>
                                    <td><?php echo esc_html($notification->display_name); ?></td>
                                    <td><?php echo esc_html($notification->type); ?></td>
                                    <td><?php echo esc_html(substr($notification->content, 0, 80)) . (strlen($notification->content) > 80 ? '...' : ''); ?></td>
                                    <td><?php echo $notification->is_read ? 'Oui' : 'Non'; ?></td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="skillset-view-all">
                            <a href="<?php echo admin_url('admin.php?page=skillset-notifications-logs'); ?>" class="button">Voir toutes les notifications</a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="skillset-section">
                    <h2>Derniers envois</h2>
                    <?php if (empty($recent_logs)): ?>
                        <p>Aucun log d'envoi récent.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th>Détail</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($log->type)); ?></td>
                                    <td><?php echo $log->display_name ? esc_html($log->display_name) : 'Multiple'; ?></td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($log->status); ?>">
                                            <?php echo $log->status === 'success' ? 'Réussi' : 'Échec'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(substr($log->message, 0, 80)) . (strlen($log->message) > 80 ? '...' : ''); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="skillset-view-all">
                            <a href="<?php echo admin_url('admin.php?page=skillset-notifications-logs'); ?>" class="button">Voir tous les logs</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="skillset-dashboard-charts">
                <div class="skillset-chart-box">
                    <h2>Types de notifications</h2>
                    <?php if (empty($notification_types)): ?>
                        <p>Aucune donnée disponible.</p>
                    <?php else: ?>
                        <ul class="skillset-chart-list">
                            <?php foreach ($notification_types as $type): ?>
                                <li>
                                    <span class="skillset-chart-label"><?php echo esc_html($type->type); ?></span>
                                    <span class="skillset-chart-bar" style="width: <?php echo min(100, ($type->count / $total_notifications) * 100); ?>%"></span>
                                    <span class="skillset-chart-value"><?php echo $type->count; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="skillset-dashboard-actions">
                <h2>Actions rapides</h2>
                <div class="skillset-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=skillset-notifications-broadcast'); ?>" class="button button-primary">Envoyer une notification massive</a>
                    <a href="<?php echo admin_url('admin.php?page=skillset-notifications-settings'); ?>" class="button">Configurer les notifications</a>
                    <a href="<?php echo admin_url('admin.php?page=skillset-notifications-email-templates'); ?>" class="button">Gérer les modèles d'emails</a>
                </div>
            </div>
        </div>
        <style>
            .skillset-dashboard-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 20px;
            }
            .skillset-stat-box {
                flex: 1;
                min-width: 300px;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                padding: 15px;
            }
            .skillset-stat-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
            }
            .skillset-stat-item {
                flex: 1;
                min-width: 80px;
                background: #f8f9fa;
                padding: 10px;
                text-align: center;
                border-radius: 3px;
            }
            .skillset-stat-value {
                display: block;
                font-size: 22px;
                font-weight: bold;
                color: #1e88e5;
            }
            .skillset-stat-item.failure .skillset-stat-value {
                color: #e53935;
            }
            .skillset-stat-label {
                display: block;
                font-size: 13px;
                color: #666;
            }
            .skillset-dashboard-sections {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 20px;
            }
            .skillset-section {
                flex: 1;
                min-width: 45%;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                padding: 15px;
            }
            .skillset-view-all {
                text-align: right;
                margin-top: 10px;
            }
            .status-success {
                color: #388e3c;
                font-weight: bold;
            }
            .status-failure {
                color: #d32f2f;
                font-weight: bold;
            }
            .skillset-dashboard-charts {
                margin-bottom: 20px;
            }
            .skillset-chart-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                padding: 15px;
            }
            .skillset-chart-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .skillset-chart-list li {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
            }
            .skillset-chart-label {
                width: 150px;
                padding-right: 10px;
            }
            .skillset-chart-bar {
                height: 20px;
                background: #1e88e5;
                flex-grow: 1;
                margin-right: 10px;
                border-radius: 2px;
            }
            .skillset-chart-value {
                width: 50px;
                text-align: right;
                font-weight: bold;
            }
            .skillset-dashboard-actions {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                padding: 15px;
            }
            .skillset-action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * Affiche la page des paramètres
     */
    public function display_settings_page() {
        // Traitement des actions
        if (isset($_POST['action']) && isset($_POST['skillset_notifications_nonce']) && wp_verify_nonce($_POST['skillset_notifications_nonce'], 'skillset_notifications_settings')) {
            if ($_POST['action'] === 'update_settings') {
                $this->handle_settings_update();
            }
        }
        
        // Récupérer les réglages
        $enable_email = get_option('skillset_notifications_enable_email', 'on');
        $enable_push = get_option('skillset_notifications_enable_push', 'off');
        $per_page = get_option('skillset_notifications_per_page', 10);
        $auto_delete_days = get_option('skillset_notifications_auto_delete_days', 30);
        $page_id = get_option('skillset_notifications_page_id', 0);
        $onesignal_app_id = get_option('skillset_notifications_onesignal_app_id', '');
        $onesignal_api_key = get_option('skillset_notifications_onesignal_api_key', '');
        
        ?>
        <div class="wrap">
            <h1>Configuration des notifications</h1>
            
            <?php settings_errors('skillset_notifications'); ?>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="update_settings">
                <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                
                <div class="metabox-holder">
                    <!-- Réglages généraux -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Réglages généraux</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="page_id">Page des notifications</label>
                                    </th>
                                    <td>
                                        <select name="page_id" id="page_id" class="regular-text">
                                            <option value="0">-- Sélectionner une page --</option>
                                            <?php 
                                            $pages = get_pages();
                                            foreach ($pages as $page) {
                                                echo '<option value="' . $page->ID . '" ' . selected($page_id, $page->ID, false) . '>' . $page->post_title . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <p class="description">Sélectionnez la page où le shortcode [skillset_notifications] est utilisé.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="per_page">Notifications par page</label>
                                    </th>
                                    <td>
                                        <input type="number" name="per_page" id="per_page" value="<?php echo esc_attr($per_page); ?>" class="small-text" min="5" max="100">
                                        <p class="description">Nombre de notifications affichées par page sur le front-end.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="auto_delete_days">Suppression automatique</label>
                                    </th>
                                    <td>
                                        <input type="number" name="auto_delete_days" id="auto_delete_days" value="<?php echo esc_attr($auto_delete_days); ?>" class="small-text" min="1" max="365">
                                        <p class="description">Nombre de jours après lesquels les notifications lues sont supprimées automatiquement. Mettre 0 pour désactiver.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Configuration Email -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Notifications par email</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_email">Activer les emails</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enable_email" id="enable_email" <?php checked($enable_email, 'on'); ?>>
                                            Envoyer automatiquement des emails pour les notifications
                                        </label>
                                        <p class="description">Si activé, un email sera envoyé pour chaque nouvelle compétence et badge obtenu.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Configuration Push -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Notifications push (OneSignal)</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_push">Activer les notifications push</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enable_push" id="enable_push" <?php checked($enable_push, 'on'); ?>>
                                            Envoyer automatiquement des notifications push
                                        </label>
                                        <p class="description">Si activé, une notification push sera envoyée pour chaque nouvelle compétence et badge obtenu.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="onesignal_app_id">App ID OneSignal</label>
                                    </th>
                                    <td>
                                        <input type="text" name="onesignal_app_id" id="onesignal_app_id" value="<?php echo esc_attr($onesignal_app_id); ?>" class="regular-text">
                                        <p class="description">Vous trouverez cet identifiant dans les paramètres de votre application OneSignal.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="onesignal_api_key">REST API Key OneSignal</label>
                                    </th>
                                    <td>
                                        <input type="password" name="onesignal_api_key" id="onesignal_api_key" value="<?php echo esc_attr($onesignal_api_key); ?>" class="regular-text">
                                        <p class="description">Cette clé API se trouve dans les paramètres de votre application OneSignal, sous "Keys & IDs".</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modifications">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Affiche la page des modèles d'emails
     */
    public function display_email_templates_page() {
        // Traitement des actions
        if (isset($_POST['action']) && isset($_POST['skillset_notifications_nonce']) && wp_verify_nonce($_POST['skillset_notifications_nonce'], 'skillset_notifications_settings')) {
            if ($_POST['action'] === 'update_email_templates') {
                $this->handle_update_email_templates();
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Modèles d'emails</h1>
            
            <?php settings_errors('skillset_notifications'); ?>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="update_email_templates">
                <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                
                <div class="metabox-holder">
                    <!-- Configuration générale des emails -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Configuration générale des emails</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="email_from">Adresse email d'expédition</label>
                                    </th>
                                    <td>
                                        <input type="email" name="email_from" id="email_from" value="<?php echo esc_attr(get_option('skillset_notifications_email_from', get_option('admin_email'))); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="email_name">Nom d'expéditeur</label>
                                    </th>
                                    <td>
                                        <input type="text" name="email_name" id="email_name" value="<?php echo esc_attr(get_option('skillset_notifications_email_name', get_bloginfo('name'))); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="email_html">Format HTML</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="email_html" id="email_html" <?php checked(get_option('skillset_notifications_email_html', 'on'), 'on'); ?>>
                                            Activer le format HTML pour les emails
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Shortcodes disponibles -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Shortcodes disponibles</span></h2>
                        <div class="inside">
                            <p>Vous pouvez utiliser les shortcodes suivants dans vos modèles:</p>
                            <ul class="skillset-shortcodes-list" style="columns: 2;">
                                <li><code>%user_name%</code> - Nom complet de l'utilisateur</li>
                                <li><code>%user_first_name%</code> - Prénom de l'utilisateur</li>
                                <li><code>%user_last_name%</code> - Nom de famille de l'utilisateur</li>
                                <li><code>%competency_name%</code> - Nom de la compétence</li>
                                <li><code>%competency_description%</code> - Description de la compétence</li>
                                <li><code>%competency_level%</code> - Niveau de la compétence</li>
                                <li><code>%badge_name%</code> - Nom du badge</li>
                                <li><code>%badge_description%</code> - Description du badge</li>
                                <li><code>%parcours%</code> - Nom du parcours concerné</li>
                                <li><code>%site_name%</code> - Nom du site</li>
                                <li><code>%site_url%</code> - URL du site</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Modèle pour compétence validée -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Compétence validée</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="subject_competency">Objet de l'email</label>
                                    </th>
                                    <td>
                                        <input type="text" name="subject_competency" id="subject_competency" value="<?php echo esc_attr(get_option('skillset_notifications_subject_competency', 'Félicitations! Nouvelle compétence validée sur %site_name%')); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="template_competency">Contenu de l'email</label>
                                    </th>
                                    <td>
                                        <textarea name="template_competency" id="template_competency" rows="8" class="large-text"><?php echo esc_textarea(get_option('skillset_notifications_template_competency', "Bonjour %user_name%,\n\nFélicitations! Vous avez acquis une nouvelle compétence dans votre parcours %parcours%.\n\nCompétence: %competency_name%\nNiveau: %competency_level%\nDescription: %competency_description%\n\nContinuez votre apprentissage pour développer davantage vos compétences!")); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Modèle pour badge obtenu -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Badge obtenu</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="subject_badge">Objet de l'email</label>
                                    </th>
                                    <td>
                                        <input type="text" name="subject_badge" id="subject_badge" value="<?php echo esc_attr(get_option('skillset_notifications_subject_badge', 'Vous avez obtenu un nouveau badge sur %site_name%!')); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="template_badge">Contenu de l'email</label>
                                    </th>
                                    <td>
                                        <textarea name="template_badge" id="template_badge" rows="8" class="large-text"><?php echo esc_textarea(get_option('skillset_notifications_template_badge', "Bonjour %user_name%,\n\nFélicitations! Vous avez obtenu un nouveau badge dans votre parcours %parcours%.\n\nBadge: %badge_name%\nDescription: %badge_description%\n\nPartagez cette réussite avec vos amis et collègues!")); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modèles">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Affiche la page de suivi des notifications
     */
    public function display_logs_page() {
        global $wpdb;
        
        // Gestion de la pagination
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        // Filtres
        $notification_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'logs'; // logs ou notifications
        
        // Table et conditions de requête en fonction de la vue
        if ($view === 'notifications') {
            $table_name = $wpdb->prefix . 'skillset_notifications';
            $count_sql = "SELECT COUNT(*) FROM $table_name";
            $sql = "SELECT n.*, u.display_name FROM $table_name n LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID";
            
            $where_clauses = array();
            $where_values = array();
            
            if (!empty($notification_type)) {
                $where_clauses[] = "n.type = %s";
                $where_values[] = $notification_type;
            }
            
            if (!empty($date_from)) {
                $where_clauses[] = "n.created_at >= %s";
                $where_values[] = $date_from . ' 00:00:00';
            }
            
            if (!empty($date_to)) {
                $where_clauses[] = "n.created_at <= %s";
                $where_values[] = $date_to . ' 23:59:59';
            }
            
            if ($user_id > 0) {
                $where_clauses[] = "n.user_id = %d";
                $where_values[] = $user_id;
            }
            
            // Finalize SQL
            if (!empty($where_clauses)) {
                $sql .= " WHERE " . implode(' AND ', $where_clauses);
                $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT %d OFFSET %d";
            $where_values[] = $per_page;
            $where_values[] = $offset;
            
            // Préparer les requêtes
            if (!empty($where_values)) {
                $sql = $wpdb->prepare($sql, $where_values);
                $count_sql = $wpdb->prepare($count_sql, array_slice($where_values, 0, -2));
            }
            
            // Exécuter les requêtes
            $total_items = $wpdb->get_var($count_sql);
            $items = $wpdb->get_results($sql);
            
        } else {
            // Logs
            $logs_table = $wpdb->prefix . 'skillset_notification_logs';
            $count_sql = "SELECT COUNT(*) FROM $logs_table";
            $sql = "SELECT l.*, u.display_name FROM $logs_table l LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID";
            
            $where_clauses = array();
            $where_values = array();
            
            if (!empty($notification_type)) {
                $where_clauses[] = "l.type = %s";
                $where_values[] = $notification_type;
            }
            
            if (!empty($status)) {
                $where_clauses[] = "l.status = %s";
                $where_values[] = $status;
            }
            
            if (!empty($date_from)) {
                $where_clauses[] = "l.created_at >= %s";
                $where_values[] = $date_from . ' 00:00:00';
            }
            
            if (!empty($date_to)) {
                $where_clauses[] = "l.created_at <= %s";
                $where_values[] = $date_to . ' 23:59:59';
            }
            
            if ($user_id > 0) {
                $where_clauses[] = "l.user_id = %d";
                $where_values[] = $user_id;
            }
            
            // Finalize SQL
            if (!empty($where_clauses)) {
                $sql .= " WHERE " . implode(' AND ', $where_clauses);
                $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $sql .= " ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
            $where_values[] = $per_page;
            $where_values[] = $offset;
            
            // Préparer les requêtes
            if (!empty($where_values)) {
                $sql = $wpdb->prepare($sql, $where_values);
                $count_sql = $wpdb->prepare($count_sql, array_slice($where_values, 0, -2));
            }
            
            // Exécuter les requêtes
            $total_items = $wpdb->get_var($count_sql);
            $items = $wpdb->get_results($sql);
        }
        
        // Pagination
        $total_pages = ceil($total_items / $per_page);
        
        // Récupérer les types possibles pour les filtres
        $notification_types = $wpdb->get_col("SELECT DISTINCT type FROM " . $wpdb->prefix . 'skillset_notifications');
        $log_types = $wpdb->get_col("SELECT DISTINCT type FROM " . $wpdb->prefix . 'skillset_notification_logs');
        $log_statuses = $wpdb->get_col("SELECT DISTINCT status FROM " . $wpdb->prefix . 'skillset_notification_logs');
        
        ?>
        <div class="wrap">
            <h1>Suivi des notifications</h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo add_query_arg('view', 'logs', remove_query_arg(array('paged', 'type', 'status', 'date_from', 'date_to', 'user_id'))); ?>" class="<?php echo $view === 'logs' ? 'current' : ''; ?>">
                        Logs d'envoi <span class="count">(<?php echo $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . 'skillset_notification_logs'); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo add_query_arg('view', 'notifications', remove_query_arg(array('paged', 'type', 'status', 'date_from', 'date_to', 'user_id'))); ?>" class="<?php echo $view === 'notifications' ? 'current' : ''; ?>">
                        Notifications <span class="count">(<?php echo $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . 'skillset_notifications'); ?>)</span>
                    </a>
                </li>
            </ul>
            
            <form method="get">
                <input type="hidden" name="page" value="skillset-notifications-logs">
                <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
                
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <?php if ($view === 'logs'): ?>
                            <select name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($log_types as $type): ?>
                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($notification_type, $type); ?>><?php echo esc_html(ucfirst($type)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($log_statuses as $stat): ?>
                                    <option value="<?php echo esc_attr($stat); ?>" <?php selected($status, $stat); ?>><?php echo esc_html(ucfirst($stat)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <select name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($notification_types as $type): ?>
                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($notification_type, $type); ?>><?php echo esc_html(ucfirst($type)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        
                        <select name="user_id">
                            <option value="0">Tous les utilisateurs</option>
                            <?php 
                            $users = get_users(array('fields' => array('ID', 'display_name')));
                            foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="date_from">Du</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                        
                        <label for="date_to">Au</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                        
                        <input type="submit" class="button" value="Filtrer">
                        <a href="<?php echo add_query_arg('view', $view, remove_query_arg(array('type', 'status', 'date_from', 'date_to', 'user_id', 'paged'))); ?>" class="button">Réinitialiser</a>
                    </div>
                    
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_items; ?> éléments</span>
                        <?php if ($total_pages > 1): ?>
                            <span class="pagination-links">
                                <?php if ($current_page > 1): ?>
                                    <a class="first-page button" href="<?php echo add_query_arg('paged', 1); ?>">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" href="<?php echo add_query_arg('paged', $current_page - 1); ?>">
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                <?php else: ?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                <?php endif; ?>
                                
                                <span class="paging-input">
                                    <span class="current-page"><?php echo $current_page; ?></span>
                                    <span class="tablenav-paging-text"> sur <span class="total-pages"><?php echo $total_pages; ?></span></span>
                                </span>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a class="next-page button" href="<?php echo add_query_arg('paged', $current_page + 1); ?>">
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" href="<?php echo add_query_arg('paged', $total_pages); ?>">
                                        <span aria-hidden="true">»</span>
                                    </a>
                                <?php else: ?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <?php if ($view === 'logs'): ?>
                <!-- Affichage des logs d'envoi -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Utilisateur</th>
                            <th>Statut</th>
                            <th>Message</th>
                            <th>Détails</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7">Aucun log trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <td><?php echo esc_html(ucfirst($item->type)); ?></td>
                                    <td>
                                        <?php if ($item->user_id > 0): ?>
                                            <?php echo esc_html($item->display_name); ?>
                                        <?php else: ?>
                                            <em>Multiple / Groupe</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($item->status); ?>">
                                            <?php echo $item->status === 'success' ? 'Réussi' : 'Échec'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($item->message); ?></td>
                                    <td>
                                        <?php if (!empty($item->details)): ?>
                                            <button type="button" class="button-link toggle-details" data-id="<?php echo $item->id; ?>">
                                                Afficher les détails
                                            </button>
                                            <div id="details-<?php echo $item->id; ?>" class="details-content" style="display:none;">
                                                <pre><?php echo esc_html($item->details); ?></pre>
                                            </div>
                                        <?php else: ?>
                                            <em>Aucun détail</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Affichage des notifications -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Contenu</th>
                            <th>Lu</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6">Aucune notification trouvée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <td><?php echo esc_html($item->display_name); ?></td>
                                    <td><?php echo esc_html($item->type); ?></td>
                                    <td><?php echo esc_html($item->content); ?></td>
                                    <td><?php echo $item->is_read ? 'Oui' : 'Non'; ?></td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php if ($current_page > 1): ?>
                                <a class="first-page button" href="<?php echo add_query_arg('paged', 1); ?>">
                                    <span aria-hidden="true">«</span>
                                </a>
                                <a class="prev-page button" href="<?php echo add_query_arg('paged', $current_page - 1); ?>">
                                    <span aria-hidden="true">‹</span>
                                </a>
                            <?php else: ?>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                            <?php endif; ?>
                            
                            <span class="paging-input">
                                <span class="current-page"><?php echo $current_page; ?></span>
                                <span class="tablenav-paging-text"> sur <span class="total-pages"><?php echo $total_pages; ?></span></span>
                            </span>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a class="next-page button" href="<?php echo add_query_arg('paged', $current_page + 1); ?>">
                                    <span aria-hidden="true">›</span>
                                </a>
                                <a class="last-page button" href="<?php echo add_query_arg('paged', $total_pages); ?>">
                                    <span aria-hidden="true">»</span>
                                </a>
                            <?php else: ?>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.toggle-details').on('click', function() {
                var id = $(this).data('id');
                var $details = $('#details-' + id);
                
                if ($details.is(':visible')) {
                    $details.hide();
                    $(this).text('Afficher les détails');
                } else {
                    $details.show();
                    $(this).text('Masquer les détails');
                }
            });
        });
        </script>
        
        <style>
            .status-success {
                color: #388e3c;
                font-weight: bold;
            }
            .status-failure {
                color: #d32f2f;
                font-weight: bold;
            }
            .details-content {
                margin-top: 5px;
                padding: 8px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                max-height: 200px;
                overflow-y: auto;
            }
            .details-content pre {
                margin: 0;
                white-space: pre-wrap;
            }
        </style>
        <?php
    }
    
    /**
     * Affiche la page de diffusion manuelle
     */
    public function display_broadcast_page() {
        // Traitement des actions
        if (isset($_POST['action']) && isset($_POST['skillset_notifications_nonce']) && wp_verify_nonce($_POST['skillset_notifications_nonce'], 'skillset_notifications_broadcast')) {
            if ($_POST['action'] === 'send_broadcast') {
                $this->handle_send_broadcast();
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Diffusion manuelle de notifications</h1>
            
            <?php settings_errors('skillset_notifications'); ?>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="send_broadcast">
                <?php wp_nonce_field('skillset_notifications_broadcast', 'skillset_notifications_nonce'); ?>
                
                <div class="metabox-holder">
                    <div class="postbox">
                        <h2 class="hndle"><span>Créer une notification</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="notification_title">Titre de la notification</label>
                                    </th>
                                    <td>
                                        <input type="text" name="notification_title" id="notification_title" class="large-text" required>
                                        <p class="description">Ce titre sera l'objet de l'email et le titre de la notification push.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="notification_content">Contenu de la notification</label>
                                    </th>
                                    <td>
                                        <textarea name="notification_content" id="notification_content" rows="5" class="large-text" required></textarea>
                                        <p class="description">Le contenu principal de la notification.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="notification_type">Type de notification</label>
                                    </th>
                                    <td>
                                        <select name="notification_type" id="notification_type">
                                            <option value="announcement">Annonce</option>
                                            <option value="reminder">Rappel</option>
                                            <option value="news">Actualités</option>
                                            <option value="update">Mise à jour</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><span>Canaux de diffusion</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Canaux</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="send_notification" value="1" checked>
                                            Notification sur le site
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="send_email" value="1">
                                            Email
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="send_push" value="1">
                                            Notification push
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><span>Destinataires</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="recipient_type">Type de destinataires</label>
                                    </th>
                                    <td>
                                        <select name="recipient_type" id="recipient_type" class="recipient-type-selector">
                                            <option value="all">Tous les utilisateurs</option>
                                            <option value="parcours">Par parcours</option>
                                            <option value="specific">Utilisateurs spécifiques</option>
                                            <option value="role">Par rôle</option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <!-- Filtre par parcours -->
                                <tr class="recipient-parcours recipient-option" style="display:none;">
                                    <th scope="row">
                                        <label for="recipient_parcours">Parcours</label>
                                    </th>
                                    <td>
                                        <select name="recipient_parcours[]" id="recipient_parcours" multiple class="select2-multiple" style="width: 400px;">
                                            <option value="cybersage">CyberSage</option>
                                            <option value="citizenpro">CitizenPro</option>
                                            <option value="careerboost">CareerBoost</option>
                                            <option value="mastermind">MasterMind</option>
                                        </select>
                                        <p class="description">Sélectionnez un ou plusieurs parcours. Tous les utilisateurs inscrits dans ces parcours recevront la notification.</p>
                                    </td>
                                </tr>
                                
                                <!-- Utilisateurs spécifiques -->
                                <tr class="recipient-specific recipient-option" style="display:none;">
                                    <th scope="row">
                                        <label for="recipient_users">Utilisateurs</label>
                                    </th>
                                    <td>
                                        <select name="recipient_users[]" id="recipient_users" multiple class="select2-multiple" style="width: 400px;">
                                            <?php 
                                            $users = get_users(array('fields' => array('ID', 'display_name')));
                                            foreach ($users as $user): ?>
                                                <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">Sélectionnez les utilisateurs spécifiques à qui envoyer la notification.</p>
                                    </td>
                                </tr>
                                
                                <!-- Filtre par rôle -->
                                <tr class="recipient-role recipient-option" style="display:none;">
                                    <th scope="row">
                                        <label for="recipient_roles">Rôles</label>
                                    </th>
                                    <td>
                                        <select name="recipient_roles[]" id="recipient_roles" multiple class="select2-multiple" style="width: 400px;">
                                            <?php 
                                            $roles = wp_roles()->get_names();
                                            foreach ($roles as $role_value => $role_name): ?>
                                                <option value="<?php echo esc_attr($role_value); ?>"><?php echo esc_html($role_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">Sélectionnez les rôles d'utilisateurs à qui envoyer la notification.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Envoyer la notification">
                </p>
            </form>
        </div>
        
       <script>
        jQuery(document).ready(function($) {
            // Initialiser Select2
            $('.select2-multiple').select2({
                placeholder: 'Sélectionnez les destinataires',
                allowClear: true
            });
            
            // Gestion des options de destinataires
            $('#recipient_type').on('change', function() {
                var selectedType = $(this).val();
                $('.recipient-option').hide();
                $('.recipient-' + selectedType).show();
            });
            
            // Déclencher le changement au chargement
            $('#recipient_type').trigger('change');
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
        $onesignal_app_id = isset($_POST['onesignal_app_id']) ? sanitize_text_field($_POST['onesignal_app_id']) : '';
        $onesignal_api_key = isset($_POST['onesignal_api_key']) ? sanitize_text_field($_POST['onesignal_api_key']) : '';
        
        update_option('skillset_notifications_enable_email', $enable_email);
        update_option('skillset_notifications_enable_push', $enable_push);
        update_option('skillset_notifications_per_page', $per_page);
        update_option('skillset_notifications_auto_delete_days', $auto_delete_days);
        update_option('skillset_notifications_page_id', $page_id);
        update_option('skillset_notifications_onesignal_app_id', $onesignal_app_id);
        update_option('skillset_notifications_onesignal_api_key', $onesignal_api_key);
        
        add_settings_error('skillset_notifications', 'settings-updated', 'Réglages mis à jour avec succès', 'success');
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
        $notification_type = isset($_POST['notification_type']) ? sanitize_text_field($_POST['notification_type']) : 'test';
        
        // Préparer des métadonnées pour le test
        $metadata = array(
            'test_data' => true,
            'parcours' => 'Test'
        );
        
        // Ajouter des métadonnées spécifiques selon le type
        if ($notification_type === 'competency_validated') {
            $metadata['competency_name'] = 'Compétence de test';
            $metadata['competency_description'] = 'Ceci est une compétence de test';
            $metadata['competency_level'] = 'Débutant';
        } elseif ($notification_type === 'badge_awarded') {
            $metadata['badge_name'] = 'Badge de test';
            $metadata['badge_description'] = 'Ceci est un badge de test';
        }
        
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id'      => $user_id,
                'type'         => $notification_type,
                'content'      => $message,
                'related_id'   => null,
                'related_type' => null,
                'metadata'     => $metadata_json,
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
            $notification_id = $wpdb->insert_id;
            
            // Créer une instance du gestionnaire de notifications
            require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-handler.php';
            $handler = new SkillSet_Notifications_Handler();
            
            // Forcer l'envoi d'un email et notification push
            $handler->send_email_notification($user_id, $notification_type, $message, $notification_id, $metadata);
            
            if (get_option('skillset_notifications_enable_push', 'off') === 'on') {
                $handler->send_push_notification($user_id, $notification_type, $message, $notification_id, $metadata);
            }
            
            add_settings_error(
                'skillset_notifications', 
                'test-success', 
                'Notification de test envoyée avec succès', 
                'success'
            );
            
            // Déclencher une action pour d'éventuelles intégrations
            do_action('skillset_notification_sent', $user_id, $notification_type, $message, $metadata);
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
     * Gère l'envoi d'une notification à plusieurs utilisateurs
     */
    private function handle_send_broadcast() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        
        // Récupérer les données du formulaire
        $notification_title = sanitize_text_field($_POST['notification_title']);
        $notification_content = sanitize_textarea_field($_POST['notification_content']);
        $notification_type = sanitize_text_field($_POST['notification_type']);
        
        // Canaux
        $send_notification = isset($_POST['send_notification']) && $_POST['send_notification'] == '1';
        $send_email = isset($_POST['send_email']) && $_POST['send_email'] == '1';
        $send_push = isset($_POST['send_push']) && $_POST['send_push'] == '1';
        
        // Type de destinataires
        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        
        // Récupérer les utilisateurs ciblés
        $target_users = array();
        
        switch ($recipient_type) {
            case 'all':
                // Tous les utilisateurs
                $users = get_users(array('fields' => 'ID'));
                $target_users = array_map('intval', $users);
                break;
                
            case 'parcours':
                if (isset($_POST['recipient_parcours']) && is_array($_POST['recipient_parcours'])) {
                    $parcours_list = array_map('sanitize_text_field', $_POST['recipient_parcours']);
                    
                    // Obtenir les utilisateurs par parcours
                    // Note: Ceci est un exemple - la logique exacte dépendra de la façon dont vous stockez les parcours
                    foreach ($parcours_list as $parcours) {
                        $users_in_parcours = get_users(array(
                            'meta_key' => 'skillset_parcours',
                            'meta_value' => $parcours,
                            'fields' => 'ID'
                        ));
                        
                        if (!empty($users_in_parcours)) {
                            $target_users = array_merge($target_users, array_map('intval', $users_in_parcours));
                        }
                    }
                    
                    // Éliminer les doublons
                    $target_users = array_unique($target_users);
                }
                break;
                
            case 'specific':
                if (isset($_POST['recipient_users']) && is_array($_POST['recipient_users'])) {
                    $target_users = array_map('intval', $_POST['recipient_users']);
                }
                break;
                
            case 'role':
                if (isset($_POST['recipient_roles']) && is_array($_POST['recipient_roles'])) {
                    $roles = array_map('sanitize_text_field', $_POST['recipient_roles']);
                    
                    foreach ($roles as $role) {
                        $users_with_role = get_users(array(
                            'role' => $role,
                            'fields' => 'ID'
                        ));
                        
                        if (!empty($users_with_role)) {
                            $target_users = array_merge($target_users, array_map('intval', $users_with_role));
                        }
                    }
                    
                    // Éliminer les doublons
                    $target_users = array_unique($target_users);
                }
                break;
        }
        
        // Si aucun utilisateur trouvé, afficher un message d'erreur
        if (empty($target_users)) {
            add_settings_error('skillset_notifications', 'broadcast-error', 'Aucun utilisateur trouvé avec les critères sélectionnés.', 'error');
            return;
        }
        
        // Créer une instance du gestionnaire de notifications
        require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-handler.php';
        $handler = new SkillSet_Notifications_Handler();
        
        // Métadonnées pour la notification
        $metadata = array(
            'broadcast' => true,
            'title' => $notification_title,
            'sender_id' => get_current_user_id(),
            'sender_name' => wp_get_current_user()->display_name
        );
        
        // Compteurs pour les logs
        $success_count = 0;
        $error_count = 0;
        
        // Log de diffusion globale
        global $wpdb;
        $logs_table = $wpdb->prefix . 'skillset_notification_logs';
        
        // Enregistrer un log de diffusion globale
        $wpdb->insert(
            $logs_table,
            array(
                'user_id' => 0, // 0 pour indiquer un envoi groupé
                'type' => 'broadcast',
                'status' => 'info',
                'message' => "Diffusion démarrée: $notification_title",
                'details' => json_encode(array(
                    'target_type' => $recipient_type,
                    'user_count' => count($target_users),
                    'channels' => array(
                        'notification' => $send_notification,
                        'email' => $send_email,
                        'push' => $send_push
                    )
                )),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        $broadcast_log_id = $wpdb->insert_id;
        
        // Traiter chaque utilisateur
        foreach ($target_users as $user_id) {
            $notification_id = null;
            
            // Envoyer la notification sur le site
            if ($send_notification) {
                $notification_id = $handler->add_notification(
                    $user_id,
                    $notification_type,
                    $notification_content,
                    null,
                    'broadcast',
                    $metadata
                );
                
                if ($notification_id) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            // Envoyer l'email
            if ($send_email) {
                $email_sent = $handler->send_email_notification(
                    $user_id,
                    $notification_type,
                    $notification_content,
                    $notification_id,
                    $metadata,
                    $notification_title // Sujet personnalisé
                );
                
                // Enregistrer le log d'envoi d'email
                $this->log_notification_attempt(
                    'email',
                    $email_sent ? 'success' : 'failure',
                    $user_id,
                    "Email de diffusion: $notification_title",
                    $email_sent ? null : "Échec d'envoi d'email à l'utilisateur ID: $user_id"
                );
            }
            
            // Envoyer la notification push
            if ($send_push && get_option('skillset_notifications_enable_push', 'off') === 'on') {
                $push_sent = $handler->send_push_notification(
                    $user_id,
                    $notification_type,
                    $notification_content,
                    $notification_id,
                    $metadata,
                    $notification_title // Titre personnalisé
                );
                
                // Enregistrer le log d'envoi push
                $this->log_notification_attempt(
                    'push',
                    $push_sent ? 'success' : 'failure',
                    $user_id,
                    "Notification push de diffusion: $notification_title",
                    $push_sent ? null : "Échec d'envoi push à l'utilisateur ID: $user_id"
                );
            }
        }
        
        // Mettre à jour le log de diffusion avec les résultats
        $wpdb->update(
            $logs_table,
            array(
                'details' => json_encode(array(
                    'target_type' => $recipient_type,
                    'user_count' => count($target_users),
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'channels' => array(
                        'notification' => $send_notification,
                        'email' => $send_email,
                        'push' => $send_push
                    )
                )),
                'message' => "Diffusion terminée: $notification_title ($success_count réussies, $error_count échecs)"
            ),
            array('id' => $broadcast_log_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // Afficher un message de succès
        add_settings_error(
            'skillset_notifications', 
            'broadcast-success', 
            "Diffusion effectuée avec succès auprès de $success_count utilisateurs." . ($error_count > 0 ? " $error_count échecs." : ""), 
            $error_count > 0 ? 'warning' : 'success'
        );
    }
    
    /**
     * Enregistre une tentative d'envoi de notification dans les logs
     */
    private function log_notification_attempt($type, $status, $user_id, $message, $details = null) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'skillset_notification_logs';
        
        $wpdb->insert(
            $logs_table,
            array(
                'user_id' => $user_id,
                'type' => $type,
                'status' => $status,
                'message' => $message,
                'details' => $details,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
}