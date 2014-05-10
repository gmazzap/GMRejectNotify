(function($, script_data) {


    // Namespace
    GMRejectNotify = {};


    /**
     * Append debug info to output message on modal
     * @param {object} data
     * @param {object} container
     * @param {string} errMessage
     * @returns {void}
     */
    GMRejectNotify.debug_info = function(data, container, errMessage) {
        container.append('<h3>' + script_data.debug_info + ':</h3><ul></ul>');
        $ul = container.children('ul').eq(0);
        if (errMessage)
            $ul.append('<li>' + errMessage + '</li>').show();
        if (data) {
            if (data.sender_mail)
                $ul.append('<li><em>' + script_data.sender + '</em>: ' + data.sender_mail + '</li>');
            if (data.recipient)
                $ul.append('<li><em>' + script_data.recipient + '</em>: ' + data.recipient + '</li>');
            if (data.reason)
                $ul.append('<li><em>' + script_data.email_content + '</em>: ' + data.reason + '</li>');
            if (data.subject)
                $ul.append('<li><em>' + script_data.email_subject + '</em>: ' + data.subject + '</li>');
        }
    };


    /**
     * Output to modal
     * @param {object} data
     * @param {object} error
     * @returns {void}
     */
    GMRejectNotify.output = function(data, error) {
        $('#send_rejected_form_wrap .loading').remove();
        $container = $('#GMRejectNotifyMessage');
        if (data && !error) {
            if (data.message && data.class) {
                $container.addClass(data.class).html('<strong>' + data.message + '</strong>');
                if (data.class === 'error' && script_data.debug === '1')
                    GMRejectNotify.debug_info(data, $container, false);
            } else {
                $container.addClass('error').html('<strong>' + script_data.def_mail_error + '</strong>');
                if (script_data.debug === '1')
                    GMRejectNotify.debug_info(data, $container, script_data.ajax_wrong_data);
            }
        }
        if (!data || error) {
            $container.addClass('error').html('<strong>' + script_data.def_mail_error + '</strong>');
            if (script_data.debug === '1')
                GMRejectNotify.debug_info(false, $container, script_data.ajax_fails);
        }
        $container.show();
    };

    $(document).ready(function() {

        // Open modal on button click
        $(document).on('click', '#send_reject_mail_box', function(e) {
            e.preventDefault();
            var postid = $(this).data('post');
            if (!postid)
                return false;
            var tb_show_url = ajaxurl + '?action=' + script_data.action + '&postid=' + postid;
            tb_show('', tb_show_url);
        });

        // Ajax send email on form submit
        $(document).on('submit', '#send_rejected_form_form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize();
            $form.parent().append('<p class="loading">' + script_data.please_wait + '</p>');
            $form.remove();
            $.ajax(
                    {
                        type: "POST",
                        url: ajaxurl,
                        data: formData,
                        dataType: "json"
                    }
            ).done(function(data) {
                GMRejectNotify.output(data, false);
            }).fail(function() {
                GMRejectNotify.output(false, true);
            });
        });
    });



}
)(jQuery, gm_reject_notify_data = null);