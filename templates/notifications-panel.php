<div class="skillset-notifications-panel">
    <div class="skillset-notifications-header">
        <h3>Vos notifications</h3>
        <div class="skillset-notifications-count">
            <?php if ($unread_count > 0) : ?>
                <span class="unread-count"><?php echo $unread_count; ?> non-lue<?php echo $unread_count > 1 ? 's' : ''; ?></span>
            <?php else : ?>
                <span class="all-read">Tout est lu</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="skillset-notifications-list">
        <?php if (empty($notifications)) : ?>
            <div class="skillset-notification-empty">
                Vous n'avez aucune notification pour le moment.
            </div>
        <?php else : ?>
            <?php foreach ($notifications as $notification) : ?>
                <?php echo $this->format_notification($notification); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (count($notifications) >= $limit) : ?>
        <div class="skillset-notifications-footer">
            <a href="<?php echo esc_url(get_permalink(get_option('skillset_notifications_page_id', 0))); ?>" class="skillset-view-all-link">
                Voir toutes les notifications
            </a>
        </div>
    <?php endif; ?>
</div>