jQuery(document).ready(function ($) {
    // Handle the submission of sub-account creation form
    $('#wp-sub-accounts-form').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: wp_sub_accounts_ajax.ajax_url,
            data: {
                action: 'create_sub_account',
                data: formData
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Reload the page to show the updated list
                } else {
                    $('#wp-sub-accounts-result').html('<div class="error">' + response.data + '</div>');
                }
            },
            error: function (xhr, status, error) {
                $('#wp-sub-accounts-result').html('<div class="error">AJAX Error: ' + error + '</div>');
            }
        });
    });

    // Handle delete sub-account
    $('body').on('click', '.delete-sub-account', function () {
        var subAccountId = $(this).closest('tr').data('sub-account-id');
        
        $.ajax({
            type: 'POST',
            url: wp_sub_accounts_ajax.ajax_url,
            data: {
                action: 'delete_sub_account',
                sub_account_id: subAccountId
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Reload the page to show the updated list
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    });

    // Handle update sub-account pages
    $('body').on('click', '.update-sub-account-pages', function () {
        var subAccountId = $(this).closest('tr').data('sub-account-id');
        var subAccountRow = $(this).closest('tr');
        var currentPages = subAccountRow.find('td:eq(3)').text().split(', ').map(function (item) {
            return item.trim();
        });

        // Create and show the checkbox list below the button
        var checkboxListHtml = '<form id="updatePagesForm">';
        var pagesList = {
            1564708: 'Create Server',
            1564675: 'Show Server',
            1564787: 'Server Created',
            1566363: 'Support Portal'
        };

        $.each(pagesList, function (pageId, pageTitle) {
            var checked = currentPages.includes(pageTitle) ? 'checked' : '';
            checkboxListHtml += '<label><input type="checkbox" name="pages[]" value="' + pageId + '" ' + checked + '> ' + pageTitle + '</label><br>';
        });

        checkboxListHtml += '<button type="submit">Update</button></form>';

        // Insert the checkbox list below the button
        subAccountRow.next('tr').remove(); // Remove existing checkbox list if any
        subAccountRow.after('<tr class="update-pages-row"><td colspan="5">' + checkboxListHtml + '</td></tr>');

        // Handle form submission
        $('#updatePagesForm').on('submit', function (e) {
            e.preventDefault();
            var selectedPages = [];
            $('#updatePagesForm input:checked').each(function () {
                selectedPages.push($(this).val());
            });

            $.ajax({
                type: 'POST',
                url: wp_sub_accounts_ajax.ajax_url,
                data: {
                    action: 'update_sub_account_pages',
                    sub_account_id: subAccountId,
                    pages: selectedPages
                },
                success: function (response) {
                    if (response.success) {
                        location.reload(); // Reload the page to show the updated list
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    alert('AJAX Error: ' + error);
                }
            });
        });
    });
});