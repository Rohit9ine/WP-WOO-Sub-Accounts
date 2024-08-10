<?php

// Add the form and list shortcodes with membership checks
function wp_sub_accounts_form_shortcode() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $user_membership_ids = wp_sub_accounts_get_user_membership_ids($user_id);

    // Define restricted and unrestricted membership plans
    $restricted_memberships = array(1571838, 1571983, 1571984, 1571987); // Pro and Free plan IDs
    $unrestricted_memberships = array(1571985, 1571986); // No restriction

    // Check if the user has any restricted membership plans
    $has_restricted_membership = false;
    foreach ($user_membership_ids as $membership_id) {
        if (in_array($membership_id, $restricted_memberships)) {
            $has_restricted_membership = true;
            break;
        }
    }

    // Display warning message and hide form if the user has a restricted membership plan
    if ($has_restricted_membership) {
        ob_start();
        ?>
        <center><div style="color: red; font-weight: bold;">
            Please upgrade Plan to add Team Members.
        </div></center>
        <?php
        return ob_get_clean();
    }

    ob_start();
    ?>
    <form id="wp-sub-accounts-form">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <label>Select Pages:</label>
        <select multiple name="pages[]">
            <?php
            $pages = array(
                1564666 => 'Dashboard',
                1564708 => 'Create Server',
                1564675 => 'Show Server',
                1564787 => 'Server Created',
                1566363 => 'Support Portal'
            );
            foreach ($pages as $page_id => $page_name) {
                echo '<option value="' . $page_id . '">' . esc_html($page_name) . '</option>';
            }
            ?>
        </select>
        <button type="submit">Add Team Members</button>
    </form>
    <div id="wp-sub-accounts-result"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_sub_accounts_form', 'wp_sub_accounts_form_shortcode');

function wp_sub_accounts_list_shortcode() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $user_membership_ids = wp_sub_accounts_get_user_membership_ids($user_id);

    // Define restricted and unrestricted membership plans
    $restricted_memberships = array(1571838, 1571983, 1571984, 1571987); // Pro and Free plan IDs
    $unrestricted_memberships = array(1571985, 1571986); // No restriction

    // Check if the user has any restricted membership plans
    $has_restricted_membership = false;
    foreach ($user_membership_ids as $membership_id) {
        if (in_array($membership_id, $restricted_memberships)) {
            $has_restricted_membership = true;
            break;
        }
    }

    // Display warning message and hide list if the user has a restricted membership plan
    if ($has_restricted_membership) {
        ob_start();

        return ob_get_clean();
    }

    $sub_accounts_query = new WP_User_Query(array(
        'meta_key' => 'main_user_id',
        'meta_value' => $user_id,
        'meta_compare' => '='
    ));

    $sub_accounts = $sub_accounts_query->get_results();

    ob_start();
    ?>
    <h2>Your Team Members</h2>
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
                    <td colspan="5">No Team Member found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
}
add_shortcode('wp_sub_accounts_list', 'wp_sub_accounts_list_shortcode');

// Function to get user's membership plan IDs
function wp_sub_accounts_get_user_membership_ids($user_id) {
    $memberships = wc_memberships_get_user_memberships($user_id);
    $membership_ids = array();

    if (!empty($memberships)) {
        foreach ($memberships as $membership) {
            $membership_ids[] = $membership->get_plan_id();
        }
    }

    return $membership_ids;
}