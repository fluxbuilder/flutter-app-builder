jQuery(document).ready(function ($) {
    $(document).on('click', '.mstore-delete-json-file', function () {
        var id = $(this).data('id');
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_delete_json_file',
                id: id
            },
            success: function (result) {
                if (result == 'success') {
                    location.reload();
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-limit-product', function () {
        var limit = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_limit_product',
                limit: limit
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-firebase-server-key', function () {
        var serverKey = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_firebase_server_key',
                serverKey: serverKey
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-new-order-title', function () {
        var title = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_new_order_title',
                title: title
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-new-order-message', function () {
        var message = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_new_order_message',
                message: message
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-status-order-title', function () {
        var title = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_status_order_title',
                title: title
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $(document).on('blur', '.mstore-update-status-order-message', function () {
        var message = $(this).val();
        $.ajax({
            type: 'post',
            url: MyAjax.ajaxurl,
            data: {
                action: 'mstore_update_status_order_message',
                message: message
            },
            success: function (result) {
                if (result == 'success') {
                }
            }
        })
        return false;
    })

    $('#mstore-api-builder-container').on('click', '.mstore_button', function () {
        $('#mstore-api-builder-container').attr('hidden', true);
        $('#mstore-api-settings-container').attr('hidden', false);
    });
    $('#mstore-api-settings-container').on('click', '.mstore_button', function () {
        $('#mstore-api-builder-container').attr('hidden', false);
        $('#mstore-api-settings-container').attr('hidden', true);
    });
})