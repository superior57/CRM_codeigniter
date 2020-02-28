/*!
 * Internal App Plugin for validation that extends jQuery Validation plugin.
 *
 * https://perfexcrm.com/
 *
 * Copyright (c) 2019 Marjan Stojanov
 */

if (typeof($.validator) == 'undefined') {
    throw new Error('jQuery Validation plugin not found. "appFormValidator" requires jQuery Validation >= v1.17.0');
}

(function($) {

    var configuredjQueryValidation = false;

    $.fn.appFormValidator = function(options) {
        var self = this;

        var defaultMessages = {
            email: {
                remote: $.fn.appFormValidator.internal_options.localization.email_exists,
            },
        }

        var defaults = {
            rules: [],
            messages: [],
            ignore: [],
            onSubmit: false,
            submitHandler: function(form) {
                var $form = $(form);

                if ($form.hasClass('disable-on-submit')) {
                    $form.find('[type="submit"]').prop('disabled', true);
                }

                var loadingBtn = $form.find('[data-loading-text]');

                if (loadingBtn.length > 0) {
                    loadingBtn.button('loading');
                }

                if (settings.onSubmit) {
                    settings.onSubmit(form);
                } else {
                    return true;
                }
            }
        };

        var settings = $.extend({}, defaults, options);

        // Just make sure that this is always configured
        if (typeof(settings.messages.email) == 'undefined') {
            settings.messages.email = defaultMessages.email;
        }


        self.configureJqueryValidationDefaults = function() {

            // Set this only 1 time before the first validation happens
            if (!configuredjQueryValidation) {
                configuredjQueryValidation = true;
            } else {
                return true;
            }

            // Jquery validate set default options
            $.validator.setDefaults({
                highlight: $.fn.appFormValidator.internal_options.error_highlight,
                unhighlight: $.fn.appFormValidator.internal_options.error_unhighlight,
                errorElement: $.fn.appFormValidator.internal_options.error_element,
                errorClass: $.fn.appFormValidator.internal_options.error_class,
                errorPlacement: $.fn.appFormValidator.internal_options.error_placement,
            });

            self.addMethodFileSize();
            self.addMethodExtension();
        }

        self.addMethodFileSize = function() {
            // New validation method filesize
            $.validator.addMethod('filesize', function(value, element, param) {
                return this.optional(element) || (element.files[0].size <= param);
            }, $.fn.appFormValidator.internal_options.localization.file_exceeds_max_filesize);
        }

        self.addMethodExtension = function() {
            // New validation method extension based on app extensions
            $.validator.addMethod("extension", function(value, element, param) {
                param = typeof param === "string" ? param.replace(/,/g, "|") : "png|jpe?g|gif";
                return this.optional(element) || value.match(new RegExp("\\.(" + param + ")$", "i"));
            }, $.fn.appFormValidator.internal_options.localization.validation_extension_not_allowed);
        }

        self.validateCustomFields = function($form) {

            $.each($form.find($.fn.appFormValidator.internal_options.required_custom_fields_selector), function() {

                // for custom fields in tr.main, do not validate those
                if (!$(this).parents('tr.main').length) {

                    $(this).rules("add", { required: true });
                    if ($.fn.appFormValidator.internal_options.on_required_add_symbol) {
                        var label = $(this).parents('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).find('[for="' + $(this).attr('name') + '"]');
                        if (label.length > 0 && label.find('.req').length === 0) {
                            label.prepend('<small class="req text-danger">* </small>');
                        }
                    }
                }
            });
        }

        self.addRequiredFieldSymbol = function($form) {
            if ($.fn.appFormValidator.internal_options.on_required_add_symbol) {
                $.each(settings.rules, function(name, rule) {
                    if ((rule == 'required' && !jQuery.isPlainObject(rule)) ||
                        (jQuery.isPlainObject(rule) && rule.hasOwnProperty('required'))) {
                        var label = $form.find('[for="' + name + '"]');
                        if (label.length > 0 && label.find('.req').length === 0) {
                            label.prepend(' <small class="req text-danger">* </small>');
                        }
                    }
                });
            }
        }

        self.configureJqueryValidationDefaults();

        return self.each(function() {

            var $form = $(this);

            // If already validated, destroy to free up memory
            if ($form.data('validator')) {
                $form.data('validator').destroy();
            }

            $form.validate(settings);
            self.validateCustomFields($form);
            self.addRequiredFieldSymbol($form);

            $(document).trigger('app.form-validate', $form);
        });
    }
})(jQuery);

$.fn.appFormValidator.internal_options = {
    localization: {
        email_exists: typeof(app) != 'undefined' ? app.lang.email_exists : 'Please fix this field',
        file_exceeds_max_filesize: typeof(app) != 'undefined' ? app.lang.file_exceeds_max_filesize : 'File Exceeds Max Filesize',
        validation_extension_not_allowed: typeof(app) != 'undefined' ? $.validator.format(app.lang.validation_extension_not_allowed) : $.validator.format('Extension not allowed'),
    },
    on_required_add_symbol: true,
    error_class: 'text-danger',
    error_element: 'p',
    required_custom_fields_selector: '[data-custom-field-required]',
    field_wrapper_class: 'form-group',
    field_wrapper_error_class: 'has-error',
    tab_panel_wrapper: 'tab-pane',
    validated_tab_class: 'tab-validated',
    error_placement: function(error, element) {
        if (element.parent('.input-group').length || element.parents('.chk').length) {
            if (!element.parents('.chk').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element.parents('.chk'));
            }
        } else if (element.is('select') && (element.hasClass('selectpicker') || element.hasClass('ajax-search'))) {
            error.insertAfter(element.parents('.' + $.fn.appFormValidator.internal_options.field_wrapper_class + ' *').last());
        } else {
            error.insertAfter(element);
        }
    },
    error_highlight: function(element) {
        var $child_tab_in_form = $(element).parents('.' + $.fn.appFormValidator.internal_options.tab_panel_wrapper);
        if ($child_tab_in_form.length && !$child_tab_in_form.is(':visible')) {
            $('a[href="#' + $child_tab_in_form.attr('id') + '"]')
                .css('border-bottom', '1px solid red').css('color', 'red')
                .addClass($.fn.appFormValidator.internal_options.validated_tab_class);
        }

        if ($(element).is('select')) {
            // Having some issues with select, it's not aways highlighting good or too fast doing unhighlight
            delay(function() {
                $(element).closest('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).addClass($.fn.appFormValidator.internal_options.field_wrapper_error_class);
            }, 400);
        } else {
            $(element).closest('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).addClass($.fn.appFormValidator.internal_options.field_wrapper_error_class);
        }
    },
    error_unhighlight: function(element) {
        element = $(element);
        var $child_tab_in_form = element.parents('.' + $.fn.appFormValidator.internal_options.tab_panel_wrapper);
        if ($child_tab_in_form.length) {
            $('a[href="#' + $child_tab_in_form.attr('id') + '"]').removeAttr('style').removeClass($.fn.appFormValidator.internal_options.validated_tab_class);
        }
        element.closest('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).removeClass($.fn.appFormValidator.internal_options.field_wrapper_error_class);
    },
}
