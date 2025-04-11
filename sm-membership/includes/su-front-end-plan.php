<?php
function custom_pmpro_membership_group_shortcode($atts) {
    global $wpdb;
    $atts = shortcode_atts(['group_id' => 0], $atts, 'pmpro_membership_group');

    $group_id = intval($atts['group_id']);
    if (!$group_id) {
        return '<p class="pmpro-error">Invalid group ID.</p>';
    }

    $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pmpro_groups WHERE id = %d", $group_id));

    if (!$group) {
        return '<p class="pmpro-error">Group not found.</p>';
    }

    $description = custom_pmpro_get_group_meta($group_id, 'description');

    // Fetch levels with allow_signups = 1
    $levels = $wpdb->get_results($wpdb->prepare(
        "SELECT l.id, l.name, l.initial_payment, l.billing_amount, l.cycle_number, l.cycle_period, l.description
         FROM {$wpdb->prefix}pmpro_membership_levels_groups lg
         JOIN {$wpdb->prefix}pmpro_membership_levels l ON lg.level = l.id
         WHERE lg.group = %d AND l.allow_signups = 1",
        $group_id
    ));

    if (!$levels) {
        return '<p class="pmpro-error">No membership levels available for this group.</p>';
    }

    ob_start();
    ?>
    <div class="pmpro-membership-group">

        <?php if (count($levels) === 1) : ?>
            <!-- Single Plan: Show in one row with description -->
            <div class="pmpro-group-single">
                <div style="width: 50%">
                    <div class="pmpro-group-description">
                        <p><?php echo $description; ?></p>
                    </div>
                </div>
                <div class="pmpro-membership-box">
                    <?php
                    $first_level = $levels[0];
                    $checkout_url = esc_url(pmpro_url("checkout", "?pmpro_level=" . $first_level->id));
                    ?>
                    <h5><?php echo esc_html($first_level->name); ?></h5>
                    <p class="pmpro-price">
                        <?php echo pmpro_formatPrice($first_level->initial_payment); ?>
                        <?php if ($first_level->billing_amount > 0) {
                            echo ' / ' . esc_html($first_level->cycle_number . ' ' . $first_level->cycle_period);
                        } ?>
                    </p>
                    <p class="pmpro-price">
                        <?php echo $first_level->description; ?>
                    </p>
                    <a href="<?php echo $checkout_url; ?>" class="pmpro-btn">
                        <?php esc_html_e('Subscribe', 'pmpro'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Multiple Plans: Show below description -->
            <div class="pmpro-group-description">
                <p><?php echo $description; ?></p>
            </div>
            <div class="pmpro-membership-levels">
                <?php foreach ($levels as $level) : ?>
                    <div class="pmpro-membership-box">
                        <h5><?php echo esc_html($level->name); ?></h5>
                        <p class="pmpro-price">
                            <?php echo pmpro_formatPrice($level->initial_payment); ?>
                            <?php if ($level->billing_amount > 0) {
                                echo ' / ' . esc_html($level->cycle_number . ' ' . $level->cycle_period);
                            } ?>
                        </p>
                        <p class="pmpro-price">
                            <?php echo $level->description; ?>
                        </p>
                        <a href="<?php echo esc_url(pmpro_url("checkout", "?pmpro_level=" . $level->id)); ?>" class="pmpro-btn">
                            <?php esc_html_e('Subscribe', 'pmpro'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('pmpro_membership_group', 'custom_pmpro_membership_group_shortcode');
