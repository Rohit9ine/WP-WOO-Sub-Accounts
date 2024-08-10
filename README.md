# WP Sub Accounts

## Description
The **WP Sub Accounts** plugin allows primary users to add sub-accounts with specific page access. This is ideal for team management and controlling access to certain areas of a website for sub-users.

## Features
- Allows primary users to create sub-accounts.
- Sub-accounts inherit roles and memberships from the primary account.
- Sub-accounts can be assigned specific pages they can access.
- Provides a form for adding sub-accounts and a list to manage them.
- Restricts sub-accounts' access to only the assigned pages.

## Installation
1. Download the plugin files.
2. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage
1. **Create Sub-Accounts**: Use the shortcode `[wp_sub_accounts_form]` to add a form for creating sub-accounts.
2. **List Sub-Accounts**: Use the shortcode `[wp_sub_accounts_list]` to display a list of sub-accounts.
3. **Assign Pages**: Assign specific pages that a sub-account can access when creating or updating a sub-account.

## Shortcodes
- `[wp_sub_accounts_form]`: Displays the form for creating sub-accounts.
- `[wp_sub_accounts_list]`: Displays the list of sub-accounts.

## AJAX Functions
- **Create Sub-Account**: Handles the creation of sub-accounts via AJAX.
- **Delete Sub-Account**: Handles the deletion of sub-accounts via AJAX.
- **Update Sub-Account Pages**: Handles the assignment of pages to sub-accounts via AJAX.

## Files
- `wp-sub-accounts.php`: Main plugin file.
- `includes/functions.php`: Contains functions related to sub-account creation, user meta copying, and page access restriction.
- `includes/shortcodes.php`: Contains the shortcodes for displaying the sub-account form and list.
- `assets/js/scripts.js`: Contains JavaScript for handling AJAX requests.
- `assets/css/style.css`: Contains custom styles for the plugin.

## Author
**Rohit Kumar**
- [Website](https://iamrohit.net/)

## Changelog
### Version 1.0
- Initial release.
