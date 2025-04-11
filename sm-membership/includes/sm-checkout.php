<?php
defined( 'ABSPATH' ) or exit;

// add pricing box
function customtax_pmpro_checkout_boxes($pmpro_level) {
    // Determine the correct price to display
    $billing_amount = ($pmpro_level->billing_amount > 0) ? $pmpro_level->billing_amount : $pmpro_level->initial_payment;
    ?>
    <div class="pricing-container pmpro_checkout" id="pmpro_pricing_fields">
        <div class="pricing-header">Pricing</div>
        <div class="pricing-body">
            <div id="tax_section_message"></div>
            <p>Membership Fee: <span>$<span id="subscription_price"><?php echo number_format($billing_amount, 2); ?></span></span></p>
            <p >Tax: <span>$<span id="tax_amount">0.00</span></span></p>
            <h6 >Total: <span>$<span id="total_price"><?php echo number_format($billing_amount, 2); ?></span></span></h6>
        </div>
    </div>
    <?php
}

add_action('pmpro_checkout_boxes', 'customtax_pmpro_checkout_boxes', 19);

//ajax handler
add_action('wp_ajax_calculate_membership_tax', 'calculate_membership_tax_callback');
add_action('wp_ajax_nopriv_calculate_membership_tax', 'calculate_membership_tax_callback');

function calculate_membership_tax_callback() {
    $country = strtoupper(trim($_POST['country']));
    $state = strtoupper(trim($_POST['state']));
    $level_id = intval($_POST['level_id']);

    if (!$level_id) {
        wp_send_json_error(['message' => 'Invalid membership level.']);
    }

    // Get membership plan price
    $level = pmpro_getLevel($level_id);
    if ($level->billing_amount > 0 ) {
        $base_price = floatval($level->billing_amount);
    } else {
        $base_price = floatval($level->initial_payment);
    }
    $tax = 0.00;

    // Apply tax based on country and state
    if ($country === 'CA') {
        $tax = $base_price * 0.05;

        if ($state === 'BC') {
            $tax = $base_price * 0.12;
        } elseif ($state === 'NB' || $state === 'NL'  || $state == 'PE') {
            $tax = $base_price * 0.15;
        } elseif ($state == 'ON') {
            $tax = $base_price * 0.13;
        }
    }

    $total = $base_price + $tax;

    wp_send_json_success([
        'tax' => number_format($tax, 2),
        'total' => number_format($total, 2)
    ]);
}

//apply tax after checkout submit
function customtax_pmpro_tax( $tax, $values, $order ) {
    if (empty($_REQUEST['bcountry'])) {
        return $tax;
    }

    $country = sanitize_text_field($_REQUEST['bcountry']);
    $state = isset($_REQUEST['bstate']) ? sanitize_text_field($_REQUEST['bstate']) : '';
    $base_price = (float) $values['price'];
    $tax = 0; // Default tax

    // Canada-wide base tax
    if ($country === 'CA') {
        $tax = $base_price * 0.05;

        // Province-specific taxes
        $province_taxes = [
            'BC' => 0.12,
            'NB' => 0.15,
            'NL' => 0.15,
            'NS' => 0.15,
            'PE' => 0.15,
            'ON' => 0.13,
        ];

        // Apply province-specific tax rate
        if (!empty($state) && isset($province_taxes[$state])) {
            $tax = $base_price * $province_taxes[$state];
        }
    }

    return round($tax, 2);
}

add_filter('pmpro_tax', 'customtax_pmpro_tax', 10, 3);


function remove_pmpro_level_description_on_checkout($description, $pmpro_level) {
    if (is_page(pmpro_getOption('checkout_page_id'))) {
        return '';
    }
    return $description;
}
add_filter('pmpro_level_description', 'remove_pmpro_level_description_on_checkout', 10, 2);
