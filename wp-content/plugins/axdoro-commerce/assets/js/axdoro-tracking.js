/**
 * AXDORO Order Tracking Frontend AJAX Script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $form    = $('#axdoro-track-form');
        var $results = $('#axdoro-tracking-results');
        var $error   = $('#axdoro-track-error');
        var $btn     = $('#axdoro-track-submit-btn');

        if (!$form.length) {
            return;
        }

        $form.on('submit', function(e) {
            e.preventDefault();

            var orderId = $('#track_order_id').val().trim();
            var contact = $('#track_billing_contact').val().trim();

            if (!orderId || !contact) {
                $error.text('Please enter both Order ID and Email/Phone.').show();
                return;
            }

            $error.hide();
            $btn.prop('disabled', true).text('Searching Order...');

            $.ajax({
                url: axdoroTracking.ajax_url,
                type: 'POST',
                data: {
                    action: 'axdoro_ajax_track_order',
                    nonce: axdoroTracking.nonce,
                    order_id: orderId,
                    contact: contact
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Track Order Status →');
                    if (response.success && response.data.html) {
                        $results.html(response.data.html).slideDown();
                    } else {
                        var msg = (response.data && response.data.message) ? response.data.message : 'Order not found. Please verify details.';
                        $error.text(msg).show();
                        $results.hide();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Track Order Status →');
                    $error.text('Network error. Please try again or WhatsApp us directly.').show();
                    $results.hide();
                }
            });
        });
    });

})(jQuery);
