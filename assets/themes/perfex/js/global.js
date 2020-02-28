$.fn.appFormValidator.internal_options.on_required_add_symbol = false;

$(function() {

    // Fix for dropdown in tables
    $('body').on('shown.bs.dropdown', '.btn-group', function() {
        $(this).closest('.table-responsive').css("overflow", "inherit");
    });

    $('body').on('hidden.bs.dropdown', '.btn-group', function() {
        $(this).closest('.table-responsive').css("overflow", "auto");
    });

    appSelectPicker($('body').find('select'));
    appProgressBar();
    appLightbox();
    appColorPicker();
    appDatepicker();

    // Lightbox for knowledge base images
    $.each($('.kb-article').find('img'), function() {
        $(this).wrap('<a href="' + $(this).attr('src') + '" data-lightbox="kb-attachment"></a>');
    });

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    // Init popovers
    $('body').popover({
        selector: '[data-toggle="popover"]'
    });

    $('.article_useful_buttons button').on('click', function(e) {
        e.preventDefault();
        var data = {};
        data.answer = $(this).data('answer');
        data.articleid = $('input[name="articleid"]').val();
        $.post(site_url + 'knowledge_base/add_kb_answer', data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                $(this).focusout();
            }
            $('.answer_response').html(response.message);
        });
    });

    $('#identityConfirmationForm').appFormValidator({
        rules: {
            acceptance_firstname: 'required',
            acceptance_lastname: 'required',
            signature: 'required',
            acceptance_email: {
                email: true,
                required: true
            }
        },
        messages: {
            signature: {
                required: app.lang.sign_document_validation,
            },
        },
    });

    $('body.identity-confirmation #accept_action').on('click', function() {
        var $submitForm = $('#identityConfirmationForm');
        if ($submitForm.length && !$submitForm.validate().checkForm()) {
            $('#identityConfirmationModal').modal({ show: true, backdrop: 'static', keyboard: false });
        } else {
            $(this).prop('disabled', true);
            $submitForm.submit();
        }
        return false;
    });
});

function createDropzone(elementId, options) {
    var defaults = appCreateDropzoneOptions({
        paramName: "file",
        uploadMultiple: true,
        parallelUploads: 20,
        maxFiles: 20,
        accept: function(file, done) {
            done();
        },
        success: function(file, response) {
            if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                window.location.reload();
            }
        },
    });

    var settings = $.extend({}, defaults, options);
    new Dropzone(elementId, settings);
}
