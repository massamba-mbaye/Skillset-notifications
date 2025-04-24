<?php
/**
 * Désactivateur du plugin
 */
class SkillSet_Notifications_Deactivator {

    /**
     * Fonction de désactivation
     */
    public static function deactivate() {
        // Vider le cache des permaliens
        flush_rewrite_rules();
        
        // Ne pas supprimer les données lors de la désactivation
    }
}