<?php
/**
 * Classe principale du plugin de notifications
 */
class SkillSet_Notifications {

    /**
     * Instance du gestionnaire de notifications
     */
    protected $handler;
    
    /**
     * Instance de la classe admin
     */
    protected $admin;
    
    /**
     * Instance de la classe d'affichage
     */
    protected $display;
    
    /**
     * Initialise le plugin
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    /**
     * Charge les dépendances nécessaires
     */
    private function load_dependencies() {
        require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-handler.php';
        require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-admin.php';
        require_once SKILLSET_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-skillset-notifications-display.php';
        
        $this->handler = new SkillSet_Notifications_Handler();
        $this->admin = new SkillSet_Notifications_Admin();
        $this->display = new SkillSet_Notifications_Display();
    }
    
    /**
     * Définit les hooks du plugin
     */
    private function define_hooks() {
        // Hooks administratifs
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        
        // Hooks publics
        add_action('wp_enqueue_scripts', array($this->display, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this->display, 'enqueue_scripts'));
        
        // Hook pour la barre admin
        add_action('admin_bar_menu', array($this->display, 'add_notifications_to_admin_bar'), 90);
        
        // Hooks AJAX
        add_action('wp_ajax_skillset_mark_notification_read', array($this->handler, 'mark_notification_read'));
        add_action('wp_ajax_skillset_get_unread_count', array($this->handler, 'get_unread_count'));
        add_action('wp_ajax_skillset_get_recent_notifications', array($this->handler, 'get_recent_notifications'));
        
        // Hooks pour l'intégration avec d'autres plugins
        if (function_exists('do_action')) {
            // Hook pour les compétences validées
            add_action('skillset_competency_validated', array($this->handler, 'handle_competency_validated'), 10, 3);
            
            // Hook pour les badges obtenus
            add_action('skillset_badge_awarded', array($this->handler, 'handle_badge_awarded'), 10, 2);
            
            // Hook pour les conversations IA
            add_action('skillset_ai_conversation_processed', array($this->handler, 'handle_conversation_processed'), 10, 3);
        }
    }
    
    /**
     * Exécute le plugin
     */
    public function run() {
        // Le plugin est déjà initialisé par les hooks, rien à faire ici
    }
}