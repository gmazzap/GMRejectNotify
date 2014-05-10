(function($, i18nData) {


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
        container.append('<h3>' + i18nData.debug_info + ':</h3><ul></ul>');
        $ul = container.children('ul').eq(0);
        if (errMessage)
            $ul.append('<li>' + errMessage + '</li>').show();
        if (data) {
            if (data.sender_mail)
                $ul.append('<li><em>' + i18nData.sender + '</em>: ' + data.sender_mail + '</li>');
            if (data.recipient)
                $ul.append('<li><em>' + i18nData.recipient + '</em>: ' + data.recipient + '</li>');
            if (data.reason)
                $ul.append('<li><em>' + i18nData.email_content + '</em>: ' + data.reason + '</li>');
            if (data.subject)
                $ul.append('<li><em>' + i18nData.email_subject + '</em>: ' + data.subject + '</li>');
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
                if (data.class === 'error' && i18nData.debug === '1')
                    GMRejectNotify.debug_info(data, $container, false);
            } else {
                $container.addClass('error').html('<strong>' + i18nData.def_mail_error + '</strong>');
                if (i18nData.debug === '1')
                    GMRejectNotify.debug_info(data, $container, i18nData.ajax_wrong_data);
            }
        }
        if (!data || error) {
            $container.addClass('error').html('<strong>' + i18nData.def_mail_error + '</strong>');
            if (i18nData.debug === '1')
                GMRejectNotify.debug_info(false, $container, i18nData.ajax_fails);
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
            var tb_show_url = ajaxurl + '?action=' + i18nData.action + '&postid=' + postid;
            tb_show('', tb_show_url);
        });

        // Ajax send email on form submit
        $(document).on('submit', '#send_rejected_form_form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize();
            $form.parent().append('<p class="loading">' + i18nData.please_wait + '</p>');
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
                var already = '<strong>' + i18nData.already_rejected + '</strong>';
                $('#send_reject_mail_box').parent().empty().html(already);
            }).fail(function() {
                GMRejectNotify.output(false, true);
            });
        });
    });



}
)(jQuery, gm_reject_notify_data);