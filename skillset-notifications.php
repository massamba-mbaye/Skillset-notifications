<?php
/**
 * Plugin Name: SkillSet Notifications
 * Plugin URI: https://polaris-asso.org/plugins/skillset-notifications
 * Description: Gère les notifications pour les utilisateurs de la plateforme SkillSet. S'intègre avec SkillSet AI et SkillSet Competency Manager.
 * Version: 1.0.0
 * Author: Polaris Asso
 * Author URI: https://polaris-asso.org
 * Text Domain: skillset-notifications
 * Domain Path: /languages
 */

// Si ce fichier est appelé directement, on bloque.
if (!defined('WPINC')) {
    die;
}

// Définition des constantes
define('SKILLSET_NOTIFICATIONS_VERSION', '1.0.0');
define('SKILLSET_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKILLSET_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SKILLSET_NOTIFICATIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Fonction exécutée lors de l'activation du plugin
 */
function activate_skillset_notifications() {
    require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-activator.php';
    SkillSet_Notifications_Activator::activate();
}

/**
 * Fonction exécutée lors de la désactivation du plugin
 */
function deactivate_skillset_notifications() {
    require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-deactivator.php';
    SkillSet_Notifications_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_skillset_notifications');
register_deactivation_hook(__FILE__, 'deactivate_skillset_notifications');

/**
 * Inclut les fichiers principaux du plugin
 */
require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications.php';

/**
 * Démarre l'exécution du plugin
 */
function run_skillset_notifications() {
    $plugin = new SkillSet_Notifications();
    $plugin->run();
}

/**
 * Shortcode pour afficher les notifications d'un utilisateur
 */
function skillset_notifications_panel_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'limit' => 10, // Nombre maximal de notifications à afficher
        ),
        $atts,
        'skillset_notifications'
    );
    
    // Si l'utilisateur n'est pas connecté et qu'aucun ID utilisateur spécifique n'est fourni
    if (!is_user_logged_in()) {
        return '<div class="skillset-notifications-error">Veuillez vous connecter pour voir vos notifications.</div>';
    }
    
    // Obtenir l'instance du manager
    require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-display.php';
    $display = new SkillSet_Notifications_Display();
    return $display->render_notifications_panel($atts['limit']);
}
add_shortcode('skillset_notifications', 'skillset_notifications_panel_shortcode');

/**
 * Shortcode pour afficher un compteur de notifications
 * Utilisation: [skillset_notifications_count]
 * Options: 
 *   - icon: "true" (défaut) ou "false" pour afficher ou masquer l'icône
 *   - label: "true" (défaut) ou "false" pour afficher ou masquer le texte "Notifications"
 *   - link: "true" (défaut) ou "false" pour rendre le compteur cliquable vers la page des notifications
 *   - class: classes CSS personnalisées à ajouter
 */
function skillset_notifications_count_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'icon' => 'true',
            'label' => 'true',
            'link' => 'true',
            'class' => '',
        ),
        $atts,
        'skillset_notifications_count'
    );
    
    // Si l'utilisateur n'est pas connecté
    if (!is_user_logged_in()) {
        return '';
    }
    
    // Récupérer l'ID utilisateur actuel
    $user_id = get_current_user_id();
    
    // Initialiser le gestionnaire de notifications
    require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-handler.php';
    $handler = new SkillSet_Notifications_Handler();
    
    // Récupérer le nombre de notifications non lues
    $unread_count = $handler->count_unread_notifications($user_id);
    
    // URL de la page des notifications (si définie)
    $notifications_page_id = get_option('skillset_notifications_page_id', 0);
    $notifications_url = $notifications_page_id > 0 ? get_permalink($notifications_page_id) : '#';
    
    // Construire le HTML
    $output = '<span class="skillset-notifications-counter ' . esc_attr($atts['class']) . '">';
    
    // Envelopper dans un lien si demandé
    if ($atts['link'] === 'true' && $notifications_page_id > 0) {
        $output .= '<a href="' . esc_url($notifications_url) . '">';
    }
    
    // Ajouter le libellé si demandé
    if ($atts['label'] === 'true') {
        $output .= '<span class="skillset-notifications-label">Notifications</span> ';
    }
    
    // Ajouter l'icône si demandé
    if ($atts['icon'] === 'true') {
        $output .= '<span class="skillset-notifications-icon dashicons dashicons-bell"></span>';
    }
    
    // Ajouter le nombre
    $output .= '<span class="skillset-notifications-number' . ($unread_count > 0 ? ' has-unread' : '') . '">' . $unread_count . '</span>';
    
    // Fermer le lien si nécessaire
    if ($atts['link'] === 'true' && $notifications_page_id > 0) {
        $output .= '</a>';
    }
    
    $output .= '</span>';
    
    // Ajouter le CSS inline (vous pouvez aussi le mettre dans le fichier CSS principal)
    $output .= '
    <style>
        .skillset-notifications-counter {
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }
        .skillset-notifications-counter a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        .skillset-notifications-label {
            margin-right: 5px;
        }
        .skillset-notifications-icon {
            font-size: 18px;
            height: 18px;
            width: 18px;
            margin-right: 2px;
        }
        .skillset-notifications-number {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 18px;
            height: 18px;
            background-color: #ddd;
            color: #333;
            border-radius: 50%;
            padding: 0 4px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
        }
        .skillset-notifications-number.has-unread {
            background-color: #d32f2f;
            color: white;
        }
    </style>';
    
    return $output;
}
add_shortcode('skillset_notifications_count', 'skillset_notifications_count_shortcode');

/**
 * Enregistre les styles pour le compteur de notifications
 */
function skillset_notifications_counter_styles() {
    // Si l'utilisateur n'est pas connecté, ne rien charger
    if (!is_user_logged_in()) {
        return;
    }
    
    // Enregistrer les dashicons (pour l'icône de cloche)
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'skillset_notifications_counter_styles');

// Exécuter le plugin
run_skillset_notifications();