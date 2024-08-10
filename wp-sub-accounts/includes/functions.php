<?php

// Register the new endpoint
function wp_sub_accounts_add_my_account_endpoint() {
    add_rewrite_endpoint('sub-accounts', EP_ROOT | EP_PAGES);
}
add_action('init', 'wp_sub_accounts_add_my_account_endpoint');

// Add query vars
function wp_sub_accounts_query_vars($vars) {
    $vars[] = 'sub-accounts';
    return $vars;
}
add_filter('query_vars', 'wp_sub_accounts_query_vars');

// Handle endpoint content
function wp_sub_accounts_endpoint_content() {
    echo do_shortcode('[wp_sub_accounts_form]');
    echo do_shortcode('[wp_sub_accounts_list]');
}
add_action('woocommerce_account_sub-accounts_endpoint', 'wp_sub_accounts_endpoint_content');

// Add the new endpoint to the My Account menu
function wp_sub_accounts_my_account_menu_items($items) {
    $items['sub-accounts'] = 'Teams';
    return $items;
}
add_filter('woocommerce_account_menu_items', 'wp_sub_accounts_my_account_menu_items');

// Function for sub-account creation
function wp_sub_accounts_create($user_id, $sub_account_data) {
    $user_info = get_userdata($user_id);
    $user_role = !empty($user_info->roles) ? $user_info->roles[0] : 'subscriber'; // Default to 'subscriber' if no role is found

    // Generate username from first name and last name
    $first_name = sanitize_text_field($sub_account_data['first_name']);
    $last_name = sanitize_text_field($sub_account_data['last_name']);
    $base_username = strtolower($first_name . '.' . $last_name);
    $username = $base_username;

    // Ensure the username is unique
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }

    $sub_user_id = wp_create_user($username, $sub_account_data['password'], $sub_account_data['email']);
    
    if (is_wp_error($sub_user_id)) {
        return $sub_user_id;
    }

    // Update first name and last name
    update_user_meta($sub_user_id, 'first_name', $first_name);
    update_user_meta($sub_user_id, 'last_name', $last_name);

    // Remove all roles first
    $sub_user = new WP_User($sub_user_id);
    $sub_user->set_role(''); // Remove all roles

    // Assign the main user's role
    $sub_user->add_role($user_role);

    // Copy membership, subscription, and user meta
    wp_sub_accounts_copy_membership($user_id, $sub_user_id);
    wp_sub_accounts_copy_user_meta($user_id, $sub_user_id);

    // Save assigned pages to user meta
    if (isset($sub_account_data['pages']) && is_array($sub_account_data['pages'])) {
        update_user_meta($sub_user_id, '_wp_sub_accounts_pages', $sub_account_data['pages']);
    } else {
        update_user_meta($sub_user_id, '_wp_sub_accounts_pages', []);
    }

    return $sub_user_id;
}

// Copy user meta data from the main user to the sub-account
function wp_sub_accounts_copy_user_meta($main_user_id, $sub_user_id) {
    $meta_keys = array('Servers', 'Server3', 'Server2', 'Server1', 'Server4', 'Server5', 'Server6');
    foreach ($meta_keys as $meta_key) {
        $meta_value = get_user_meta($main_user_id, $meta_key, true);
        if ($meta_value !== '') { // Strict comparison to check for empty value
            update_user_meta($sub_user_id, $meta_key, $meta_value);
        }
    }
}

// Copy membership and subscription from the main user to the sub-account
function wp_sub_accounts_copy_membership($main_user_id, $sub_user_id) {
    // Copy WooCommerce Memberships
    if (class_exists('WC_Memberships_User_Memberships')) {
        $main_user_memberships = wc_memberships_get_user_memberships($main_user_id);
        foreach ($main_user_memberships as $membership) {
            wc_memberships_create_user_membership(array(
                'user_id'    => $sub_user_id,
                'plan_id'    => $membership->get_plan_id(),
                'start_date' => $membership->get_start_date(),
                'end_date'   => $membership->get_end_date()
            ));
        }
    }

    // Copy WooCommerce Subscriptions
    if (class_exists('WC_Subscriptions')) {
        $subscriptions = wcs_get_users_subscriptions($main_user_id);
        foreach ($subscriptions as $subscription) {
            wcs_create_subscription(array(
                'customer_id'       => $sub_user_id,
                'status'            => 'active',
                'start_date'        => $subscription->get_date('start'),
                'next_payment_date' => $subscription->get_date('next_payment'),
                'end_date'          => $subscription->get_date('end')
            ));
        }
    }
}

// Restrict page access for sub-accounts
function wp_sub_accounts_restrict_access() {
    // Only run if we are on a frontend page (not admin)
    if (!is_admin() && is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        $allowed_pages = get_user_meta($current_user_id, '_wp_sub_accounts_pages', true);

        // Ensure this logic only applies to sub-accounts (users not with manage_options capability)
        if (!$allowed_pages || current_user_can('manage_options')) {
            return; // No need to proceed for admins or users without assigned pages
        }

        $current_page_id = get_the_ID();

        // Check if the current page is not in the allowed pages list
        if (!in_array($current_page_id, $allowed_pages)) {
            $redirect_page_id = reset($allowed_pages);
            $redirect_url = get_permalink($redirect_page_id);

            // Only redirect if not already on the redirect page
            if ($current_page_id !== $redirect_page_id) {
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}

// Hook with a high priority to ensure it fires after most other redirection hooks
add_action('template_redirect', 'wp_sub_accounts_restrict_access', 999);

// Display sub-accounts
function wp_sub_accounts_list() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $sub_accounts_query = new WP_User_Query(array(
        'meta_key' => 'main_user_id',
        'meta_value' => $user_id,
        'meta_compare' => '='
    ));

    $sub_accounts = $sub_accounts_query->get_results();

    ob_start();
    ?>
    <h2>Your Sub Accounts</h2>
    <table id="wp-sub-accounts-table">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Assigned Pages</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sub_accounts)) : ?>
                <?php foreach ($sub_accounts as $sub_account) : ?>
                    <tr data-sub-account-id="<?php echo esc_attr($sub_account->ID); ?>">
                        <td><?php echo esc_html(get_user_meta($sub_account->ID, 'first_name', true)); ?></td>
                        <td><?php echo esc_html(get_user_meta($sub_account->ID, 'last_name', true)); ?></td>
                        <td><?php echo esc_html($sub_account->user_email); ?></td>
                        <td>
                            <?php
                            $assigned_pages = get_user_meta($sub_account->ID, '_wp_sub_accounts_pages', true);
                            if (!empty($assigned_pages)) {
                                $page_titles = array();
                                foreach ($assigned_pages as $page_id) {
                                    $page = get_post($page_id);
                                    if ($page) {
                                        $page_titles[] = $page->post_title;
                                    }
                                }
                                echo esc_html(implode(', ', $page_titles));
                            }
                            ?>
                        </td>
                        <td>
                            <button class="delete-sub-account">Delete</button>
                            <button class="update-sub-account-pages">Update Pages</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No team member found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
}
add_shortcode('wp_sub_accounts_list', 'wp_sub_accounts_list');

// Handle AJAX request for creating sub-account
add_action('wp_ajax_create_sub_account', 'wp_sub_accounts_ajax_create_sub_account');

function wp_sub_accounts_ajax_create_sub_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('You need to be logged in to Add Team Members.');
    }

    parse_str($_POST['data'], $sub_account_data);

    $user_id = get_current_user_id();
    $result = wp_sub_accounts_create($user_id, $sub_account_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    // Save the main user ID to sub-account for reference
    update_user_meta($result, 'main_user_id', $user_id);

    wp_send_json_success('Team Member Added successfully.');
}

// Handle AJAX request for deleting sub-account
add_action('wp_ajax_delete_sub_account', 'wp_sub_accounts_ajax_delete_sub_account');

function wp_sub_accounts_ajax_delete_sub_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('You need to be logged in to delete Team Members.');
    }

    $sub_account_id = intval($_POST['sub_account_id']);

    if ($sub_account_id && !is_wp_error(wp_delete_user($sub_account_id))) {
        wp_send_json_success('Team Members deleted successfully.');
    } else {
        wp_send_json_error('Failed to delete Team Members.');
    }
}

// Handle AJAX request for updating sub-account pages
add_action('wp_ajax_update_sub_account_pages', 'wp_sub_accounts_ajax_update_sub_account_pages');

function wp_sub_accounts_ajax_update_sub_account_pages() {
    if (!is_user_logged_in()) {
        wp_send_json_error('You need to be logged in to update Team Members.');
    }

    $sub_account_id = intval($_POST['sub_account_id']);
    $pages = isset($_POST['pages']) ? array_map('intval', $_POST['pages']) : array();

    if ($sub_account_id) {
        update_user_meta($sub_account_id, '_wp_sub_accounts_pages', $pages);
        wp_send_json_success('Team Members pages updated successfully.');
    } else {
        wp_send_json_error('Failed to update Team Members pages.');
    }
}