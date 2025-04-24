<?php
/**
 * Activateur du plugin
 */
class SkillSet_Notifications_Activator {

    /**
     * Fonction d'activation
     */
    public static function activate() {
        self::create_tables();
        self::ensure_metadata_column();  // Nouvelle fonction pour garantir l'existence de la colonne
        self::setup_default_settings();
        
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }
    
    /**
     * Crée les tables nécessaires
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des notifications
        $table_name = $wpdb->prefix . 'skillset_notifications';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            content text NOT NULL,
            related_id bigint(20) DEFAULT NULL,
            related_type varchar(50) DEFAULT NULL,
            metadata longtext DEFAULT NULL,  /* Champ pour les métadonnées en JSON */
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Vérifie et ajoute la colonne metadata si nécessaire
     * Cette fonction garantit la compatibilité avec les installations existantes
     */
    private static function ensure_metadata_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillset_notifications';
        
        // Vérifier si la colonne metadata existe
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'metadata'",
            DB_NAME,
            $table_name
        ));
        
        // Si la colonne n'existe pas, l'ajouter
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE `$table_name` 
                         ADD COLUMN `metadata` LONGTEXT NULL DEFAULT NULL AFTER `related_type`");
            
            // Journaliser l'opération pour débogage
            error_log('Colonne metadata ajoutée à la table ' . $table_name);
        }
    }
    
    /**
     * Configure les réglages par défaut
     */
    private static function setup_default_settings() {
        add_option('skillset_notifications_enable_email', 'off');
        add_option('skillset_notifications_enable_push', 'off');
        add_option('skillset_notifications_per_page', 10);
        add_option('skillset_notifications_auto_delete_days', 30);
        
        // Paramètres d'email par défaut
        add_option('skillset_notifications_email_from', get_option('admin_email'));
        add_option('skillset_notifications_email_name', get_bloginfo('name'));
        add_option('skillset_notifications_email_html', 'on');
        
        // Modèles d'emails par défaut
        add_option('skillset_notifications_subject_competency', 'Félicitations! Nouvelle compétence validée sur %site_name%');
        add_option('skillset_notifications_template_competency', "Bonjour %user_name%,\n\nFélicitations! Vous avez acquis une nouvelle compétence dans votre parcours %parcours%.\n\nCompétence: %competency_name%\nNiveau: %competency_level%\nDescription: %competency_description%\n\nContinuez votre apprentissage pour développer davantage vos compétences!");
        
        add_option('skillset_notifications_subject_badge', 'Vous avez obtenu un nouveau badge sur %site_name%!');
        add_option('skillset_notifications_template_badge', "Bonjour %user_name%,\n\nFélicitations! Vous avez obtenu un nouveau badge dans votre parcours %parcours%.\n\nBadge: %badge_name%\nDescription: %badge_description%\n\nPartagez cette réussite avec vos amis et collègues!");
    }
}
