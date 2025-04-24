<div class="wrap">
    <h1>SkillSet Notifications</h1>
    
    <?php settings_errors('skillset_notifications'); ?>
    
    <div class="skillset-admin-dashboard">
        <div class="skillset-dashboard-row">
            <div class="skillset-dashboard-card">
                <h2>Statistiques</h2>
                <div class="skillset-dashboard-stats">
                    <div class="skillset-stat-item">
                        <span class="stat-value"><?php echo esc_html($total_notifications); ?></span>
                        <span class="stat-label">Notifications totales</span>
                    </div>
                    <div class="skillset-stat-item">
                        <span class="stat-value"><?php echo esc_html($unread_notifications); ?></span>
                        <span class="stat-label">Non lues</span>
                    </div>
                    <div class="skillset-stat-item">
                        <span class="stat-value"><?php echo esc_html($total_users_with_notifications); ?></span>
                        <span class="stat-label">Utilisateurs</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="skillset-admin-tabs-container">
            <nav class="nav-tab-wrapper">
                <a href="#settings" class="nav-tab nav-tab-active">Réglages</a>
                <a href="#notifications" class="nav-tab">Notifications récentes</a>
                <a href="#maintenance" class="nav-tab">Maintenance</a>
                <a href="#test" class="nav-tab">Test</a>
                <a href="#email_templates" class="nav-tab">Modèles d'emails</a>
            </nav>
            
            <div id="settings" class="skillset-admin-tab-content" style="display: block;">
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_settings">
                    <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="enable_email">Notifications par email</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_email" id="enable_email" <?php checked($enable_email, 'on'); ?>>
                                    Activer les notifications par email
                                </label>
                                <p class="description">Envoie un email aux utilisateurs lorsqu'ils reçoivent une notification importante.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="enable_push">Notifications push</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_push" id="enable_push" <?php checked($enable_push, 'on'); ?> disabled>
                                    Activer les notifications push (bientôt disponible)
                                </label>
                                <p class="description">Envoie des notifications push aux utilisateurs sur leurs appareils mobiles.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="per_page">Notifications par page</label></th>
                            <td>
                                <input type="number" name="per_page" id="per_page" min="5" max="100" value="<?php echo esc_attr($per_page); ?>" class="small-text">
                                <p class="description">Nombre de notifications à afficher par page.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auto_delete_days">Suppression automatique</label></th>
                            <td>
                                <input type="number" name="auto_delete_days" id="auto_delete_days" min="0" value="<?php echo esc_attr($auto_delete_days); ?>" class="small-text">
                                <p class="description">Nombre de jours après lesquels les notifications lues sont automatiquement supprimées. Mettre 0 pour désactiver.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="page_id">Page des notifications</label></th>
                            <td>
                                <?php
                                wp_dropdown_pages(array(
                                    'name' => 'page_id',
                                    'id' => 'page_id',
                                    'selected' => $page_id,
                                    'show_option_none' => 'Sélectionner une page',
                                    'option_none_value' => '0'
                                ));
                                ?>
                                <p class="description">Page où le shortcode [skillset_notifications] est utilisé. Cette page sera liée depuis la barre d'administration.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Enregistrer les réglages'); ?>
                </form>
            </div>
            
            <div id="notifications" class="skillset-admin-tab-content" style="display: none;">
                <h2>Notifications récentes</h2>
                
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
                        <?php if (empty($recent_notifications)) : ?>
                            <tr>
                                <td colspan="6">Aucune notification trouvée.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($recent_notifications as $notification) : ?>
                                <tr>
                                    <td><?php echo esc_html($notification->id); ?></td>
                                    <td><?php echo esc_html($notification->display_name); ?></td>
                                    <td><?php echo esc_html($notification->type); ?></td>
                                    <td><?php echo esc_html($notification->content); ?></td>
                                    <td><?php echo $notification->is_read ? 'Oui' : 'Non'; ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="maintenance" class="skillset-admin-tab-content" style="display: none;">
                <h2>Maintenance</h2>
                
                <form method="post" action="" class="skillset-form-section">
                    <input type="hidden" name="action" value="clear_old_notifications">
                    <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                    
                    <h3>Supprimer les anciennes notifications</h3>
                    <p>Cette action supprimera les notifications plus anciennes qu'un certain nombre de jours.</p>
                    
                    <div class="skillset-form-row">
                        <label for="days">Supprimer les notifications plus anciennes que</label>
                        <input type="number" name="days" id="days" min="1" value="30" class="small-text"> jours
                    </div>
                    
                    <div class="skillset-form-row">
                        <label>
                            <input type="checkbox" name="read_only" checked="checked">
                            Supprimer uniquement les notifications lues
                        </label>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Supprimer les notifications" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces notifications ? Cette action est irréversible.');">
                    </p>
                </form>
            </div>
            
            <div id="test" class="skillset-admin-tab-content" style="display: none;">
                <h2>Envoyer une notification de test</h2>
                
                <form method="post" action="" class="skillset-form-section">
                    <input type="hidden" name="action" value="send_test_notification">
                    <?php wp_nonce_field('skillset_notifications_settings', 'skillset_notifications_nonce'); ?>
                    
                    <div class="skillset-form-row">
                        <label for="user_id">Utilisateur</label>
                        <?php
                        wp_dropdown_users(array(
                            'name' => 'user_id',
                            'id' => 'user_id',
                            'show_option_none' => 'Sélectionner un utilisateur',
                            'option_none_value' => ''
                        ));
                        ?>
                    </div>
                    
                    <div class="skillset-form-row">
                        <label for="test_message">Message</label>
                        <input type="text" name="test_message" id="test_message" class="regular-text" value="Ceci est une notification de test.">
                    </div>
                    
                    <?php submit_button('Envoyer la notification de test'); ?>
                </form>
            </div>
            
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
                            <li><code>%date%</code> - Date actuelle</li>
                            <li><code>%time%</code> - Heure actuelle</li>
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
    
    // Actualisation de la prévisualisation du message de test
    $('#test_message').on('input', function() {
        const message = $(this).val();
        updateTestPreview(message);
    });
    
    // Initialiser la prévisualisation
    updateTestPreview($('#test_message').val());
    
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
});
</script>