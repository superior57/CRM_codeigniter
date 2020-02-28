/**
 * @since 2.3.2
 * This file is compiled with assets/js/common.js because most of the functions can be used in admin and clients area
 */

// For manually modals where no close is defined
$(document).keyup(function(e) {
    if (e.keyCode == 27) { // escape key maps to keycode `27`

        // Close modal if only modal is opened and there is no 2 modals opened
        // This will trigger only if there is only 1 modal visible/opened

        if ($('.modal').is(':visible') && $('.modal:visible').length === 1) {
            $("body").find('.modal:visible [onclick^="close_modal_manually"]').eq(0).click();
        }
    }
});

$(function() {

    // + button for adding more attachments
    var addMoreAttachmentsInputKey = 1;
    $("body").on('click', '.add_more_attachments', function() {

        if ($(this).hasClass('disabled')) {
            return false;
        }

        var total_attachments = $('.attachments input[name*="attachments"]').length;
        if ($(this).data('max') && total_attachments >= $(this).data('max')) {
            return false;
        }

        var newattachment = $('.attachments').find('.attachment').eq(0).clone().appendTo('.attachments');
        newattachment.find('input').removeAttr('aria-describedby aria-invalid');
        newattachment.find('input').attr('name', 'attachments[' + addMoreAttachmentsInputKey + ']').val('');
        newattachment.find($.fn.appFormValidator.internal_options.error_element + '[id*="error"]').remove();
        newattachment.find('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).removeClass($.fn.appFormValidator.internal_options.field_wrapper_error_class);
        newattachment.find('i').removeClass('fa-plus').addClass('fa-minus');
        newattachment.find('button').removeClass('add_more_attachments').addClass('remove_attachment').removeClass('btn-success').addClass('btn-danger');
        addMoreAttachmentsInputKey++;
    });

    // Remove attachment
    $("body").on('click', '.remove_attachment', function() {
        $(this).parents('.attachment').remove();
    });

    $("a[href='#top']").on("click", function(e) {
        e.preventDefault();
        $("html,body").animate({ scrollTop: 0 }, 1000);
        e.preventDefault();
    });

    $("a[href='#bot']").on("click", function(e) {
        e.preventDefault();
        $("html,body").animate({ scrollTop: $(document).height() }, 1000);
        e.preventDefault();
    });

    // Jump to page feature
    $(document).on("change", ".dt-page-jump-select", function() {
        $('#' + $(this).attr('data-id')).DataTable().page($(this).val() - 1).draw(false);
    });

    // Remove tooltip fix on body click (in case user clicked link and tooltip stays open)
    $("body").on('click', function() {
        $('.tooltip').remove();
    });

    // Show please wait text on button where data-loading-text is added
    $("body").on('click', '[data-loading-text]', function() {
        var form = $(this).data('form');
        if (form !== null && typeof(form) != 'undefined') {
            // Handled in form submit handler function
            return true;
        } else {
            $(this).button('loading');
        }
    });

    // Close all popovers if user click on body and the click is not inside the popover content area
    $("body").on('click', function(e) {
        $('[data-toggle="popover"],.manual-popover').each(function() {
            //the 'is' for buttons that trigger popups
            //the 'has' for icons within a button that triggers a popup
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                $(this).popover('hide');
            }
        });
    });

    $('body').on('change', 'select[name="range"]', function() {
        var $period = $('.period');
        if ($(this).val() == 'period') {
            $period.removeClass('hide');
        } else {
            $period.addClass('hide');
            $period.find('input').val('');
        }
    });

    // Fix for dropdown overlay in .table-responsive, last rows are overlapping e.q. on tasks table
    $("body").on('click', '.table-responsive .dropdown-toggle', function(event) {
        if ($(this).next().hasClass('dropdown-menu')) {
            var elm = $(this).next(),
                docHeight = $(document).height(),
                docWidth = $(document).width(),
                btn_offset = $(this).offset(),
                btn_width = $(this).outerWidth(),
                btn_height = $(this).outerHeight(),
                elm_width = elm.outerWidth(),
                elm_height = elm.outerHeight(),
                table_offset = $(".table-responsive").offset(),
                table_width = $(".table-responsive").width(),
                table_height = $(".table-responsive").height(),

                tableoffright = table_width + table_offset.left,
                tableoffbottom = table_height + table_offset.top,
                rem_tablewidth = docWidth - tableoffright,
                rem_tableheight = docHeight - tableoffbottom,
                elm_offsetleft = btn_offset.left,
                elm_offsetright = btn_offset.left + btn_width,
                elm_offsettop = btn_offset.top + btn_height,
                btn_offsetbottom = elm_offsettop,

                left_edge = (elm_offsetleft - table_offset.left) < elm_width,
                top_edge = btn_offset.top < elm_height,
                right_edge = (table_width - elm_offsetleft) < elm_width,
                bottom_edge = (tableoffbottom - btn_offsetbottom) < elm_height;

            var table_offset_bottom = docHeight - (table_offset.top + table_height);

            var touchTableBottom = (btn_offset.top + btn_height + (elm_height * 2)) - table_offset.top;

            var bottomedge = touchTableBottom > table_offset_bottom;

            if (left_edge) {
                $(this).addClass('left-edge');
            } else {
                $('.dropdown-menu').removeClass('left-edge');
            }
            if (bottom_edge) {
                $(this).parent().addClass('dropup');
            } else {
                $(this).parent().removeClass('dropup');
            }
        }
    });

    // Add are you sure on all delete links (onclick is not handler here)
    $("body").on('click', '._delete', function(e) {
        if (confirm_delete()) { return true; }
        return false;
    });
});

// Will give alert to confirm delete
function confirm_delete() {
    var message = 'Are you sure you want to perform this action?';

    // Clients area
    if (typeof(app) != 'undefined') {
        message = app.lang.confirm_action_prompt;
    }

    var r = confirm(message);
    if (r == false) { return false; }
    return true;
}

// Delay function
var delay = (function() {
    var timer = 0;
    return function(callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

$.fn.isInViewport = function() {
    var elementTop = $(this).offset().top;
    var elementBottom = elementTop + $(this).outerHeight();
    var viewportTop = $(window).scrollTop();
    var viewportBottom = viewportTop + $(window).height();
    return elementBottom > viewportTop && elementTop < viewportBottom;
};

String.prototype.matchAll = function(regexp) {
    var matches = [];
    this.replace(regexp, function() {
        var arr = ([]).slice.call(arguments, 0);
        var extras = arr.splice(-2);
        arr.index = extras[0];
        arr.input = extras[1];
        matches.push(arr);
    });
    return matches.length ? matches : null;
};

// Function to slug string
function slugify(string) {
    return string
        .toString()
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "-")
        .replace(/[^\w\-]+/g, "")
        .replace(/\-\-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "");
}

// Strip html from string
function stripTags(html) {
    var tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
}

// Check if field is empty
function empty(data) {
    if (typeof(data) == 'number' || typeof(data) == 'boolean') {
        return false;
    }
    if (typeof(data) == 'undefined' || data === null) {
        return true;
    }
    if (typeof(data.length) != 'undefined') {
        return data.length === 0;
    }
    var count = 0;
    for (var i in data) {
        if (data.hasOwnProperty(i)) {
            count++;
        }
    }
    return count === 0;
}

// Attached new hotkey handler
function add_hotkey(key, func) {

    if (typeof($.Shortcuts) == 'undefined') {
        return false;
    }

    $.Shortcuts.add({
        type: 'down',
        mask: key,
        handler: func
    });
}

function _tinymce_mobile_toolbar() {
    return [
        'undo',
        'redo',
        'styleselect',
        'bold',
        'italic',
        'link',
        'image',
        'bullist',
        'numlist',
        'forecolor',
        'fontsizeselect',
    ];
}

// Function that convert decimal logged time to HH:MM format
function decimalToHM(decimal) {
    var hrs = parseInt(Number(decimal));
    var min = Math.round((Number(decimal) - hrs) * 60);
    return (hrs < 10 ? "0" + hrs : hrs) + ':' + (min < 10 ? "0" + min : min);
}

// Generate color rgb
function color(r, g, b) {
    return 'rgb(' + r + ',' + g + ',' + b + ')';
}

// Url builder function with parameteres
function buildUrl(url, parameters) {
    var qs = "";
    for (var key in parameters) {
        var value = parameters[key];
        qs += encodeURIComponent(key) + "=" + encodeURIComponent(value) + "&";
    }
    if (qs.length > 0) {
        qs = qs.substring(0, qs.length - 1); //chop off last "&"
        url = url + "?" + qs;
    }
    return url;
}

// Check if is ios Device
function is_ios() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

// Check if is Microsoft Browser, Internet Explorer 10 od order, Internet Explorer 11 or Edge (any version)
function is_ms_browser() {
    if (/MSIE/i.test(navigator.userAgent) || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
        // this is internet explorer 10
        return true;
    }

    if (/Edge/i.test(navigator.userAgent)) {
        // this is Microsoft Edge
        return true;
    }

    return false;
}

function _simple_editor_config() {
    return {
        height: !is_mobile() ? 100 : 50,
        menubar: false,
        autoresize_bottom_margin: 15,
        plugins: [
            'table advlist codesample autosave' + (!is_mobile() ? ' autoresize ' : ' ') + 'lists link image textcolor media contextmenu paste',
        ],
        toolbar: 'insert formatselect bold forecolor backcolor' + (is_mobile() ? ' | ' : ' ') + 'alignleft aligncenter alignright bullist numlist | restoredraft',
        contextmenu: "link image imagetools table spellchecker inserttable | cell row column deletetable | paste pastetext",
        insert_button_items: 'image media link codesample',
        toolbar1: ''
    };

}

function _create_print_window(name) {

    var params = 'width=' + screen.width;
    params += ', height=' + screen.height;
    params += ', top=0, left=0';
    params += ', fullscreen=yes';

    return window.open('', name, params);
}

function _add_print_window_default_styles(mywindow) {

    mywindow.document.write('<style>');
    mywindow.document.write('.clearfix:after { ' +
        'clear: both;' +
        '}' +
        '.clearfix:before, .clearfix:after { ' +
        'display: table; content: " ";' +
        '}' +
        'body { ' +
        'font-family: Arial, Helvetica, sans-serif;color: #444; font-size:13px;' +
        '}' +
        '.bold { ' +
        'font-weight: bold !important;' +
        '}' +
        '');

    mywindow.document.write('</style>');
}

// Equivalent function like php nl2br
function nl2br(str, is_xhtml) {
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

// Kanban til direction
function tilt_direction(item) {
    setTimeout(function() {
        var left_pos = item.position().left,
            move_handler = function(e) {
                if (e.pageX >= left_pos) {
                    item.addClass("right");
                    item.removeClass("left");
                } else {
                    item.addClass("left");
                    item.removeClass("right");
                }
                left_pos = e.pageX;
            };
        $("html").on("mousemove", move_handler);
        item.data("move_handler", move_handler);
    }, 1000);
}

// Function to close modal manually... needed in some modals where the data is flexible.
function close_modal_manually(modal) {
    modal = $(modal).length === 0 ? $("body").find(modal) : modal = $(modal);
    modal.fadeOut('fast', function() {
        modal.remove();
        if (!$("body").find('.modal').is(':visible')) {
            $('.modal-backdrop').remove();
            $("body").removeClass('modal-open');
        }
    });
}

// Show password on hidden input field
function showPassword(name) {
    var target = $('input[name="' + name + '"]');
    if ($(target).attr('type') == 'password' && $(target).val() !== '') {
        $(target)
            .queue(function() {
                $(target).attr('type', 'text').dequeue();
            });
    } else {
        $(target).queue(function() {
            $(target).attr('type', 'password').dequeue();
        });
    }
}

// Generate hidden input field
function hidden_input(name, val) {
    return '<input type="hidden" name="' + name + '" value="' + val + '">';
}

// Init color pickers
function appColorPicker(element) {
    if (typeof(element) == 'undefined') {
        element = $("body").find('div.colorpicker-input');
    }
    if (element.length) {
        element.colorpicker({
            format: "hex"
        });
    }
}

// Init bootstrap select picker
function appSelectPicker(element) {

    if (typeof(element) == 'undefined') {
        element = $("body").find('select.selectpicker');
    }

    if (element.length) {
        element.selectpicker({
            showSubtext: true
        });
    }
}

// Progress bar animation load
function appProgressBar() {
    var progress_bars = $('body').find('.progress div.progress-bar');
    if (progress_bars.length) {
        progress_bars.each(function() {
            var bar = $(this);
            var perc = bar.attr("data-percent");
            bar.css('width', (perc) + '%');
            if (!bar.hasClass('no-percent-text')) { bar.text((perc) + '%'); }
        });
    }
}

// Lightbox plugins for images
function appLightbox(options) {

    if (typeof(lightbox) == 'undefined') {
        return false;
    }

    var _lightBoxOptions = {
        'showImageNumberLabel': false,
        resizeDuration: 200,
        positionFromTop: 25
    };

    if (typeof(options) != 'undefined') {
        jQuery.extend(_lightBoxOptions, options);
    }

    lightbox.option(_lightBoxOptions);
}


// Datatables inline/offline lazy load images
function DataTablesInlineLazyLoadImages(nRow, aData, iDisplayIndex) {
    var img = $('img.img-table-loading', nRow);
    img.attr('src', img.data('orig'));
    img.prev('div').addClass('hide');
    return nRow;
}

// Datatables custom job to page function
function _table_jump_to_page(table, oSettings) {

    var paginationData = table.DataTable().page.info();
    var previousDtPageJump = $("body").find('#dt-page-jump-' + oSettings.sTableId);

    if (previousDtPageJump.length) {
        previousDtPageJump.remove();
    }

    if (paginationData.pages > 1) {

        var jumpToPageSelect = $("<select></select>", {
            "data-id": oSettings.sTableId,
            "class": "dt-page-jump-select form-control",
            'id': 'dt-page-jump-' + oSettings.sTableId
        });

        var paginationHtml = '';

        for (var i = 1; i <= paginationData.pages; i++) {
            var selectedCurrentPage = ((paginationData.page + 1) === i) ? 'selected' : '';
            paginationHtml += "<option value='" + i + "'" + selectedCurrentPage + ">" + i + "</option>";
        }

        if (paginationHtml != '') {
            jumpToPageSelect.append(paginationHtml);
        }

        $("#" + oSettings.sTableId + "_wrapper .dt-page-jump").append(jumpToPageSelect);
    }
}

// Generate float alert
function alert_float(type, message, timeout) {
    var aId, el;

    aId = $("body").find('float-alert').length;
    aId++;

    aId = 'alert_float_' + aId;

    el = $("<div></div>", {
        "id": aId,
        "class": "float-alert animated fadeInRight col-xs-10 col-sm-3 alert alert-" + type,
    });

    el.append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
    el.append('<span class="fa fa-bell-o" data-notify="icon"></span>');
    el.append("<span class=\"alert-title\">" + message + "</span>");

    $("body").append(el);
    timeout = timeout ? timeout : 3500
    setTimeout(function() {
        $('#' + aId).hide('fast', function() {
            $('#' + aId).remove();
        });
    }, timeout);
}

// Generate random password
function generatePassword(field) {
    var length = 12,
        charset = "abcdefghijklnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
        retVal = "";
    for (var i = 0, n = charset.length; i < length; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    $(field).parents().find('input.password').val(retVal);
}

// Get url params like $_GET
function get_url_param(param) {
    var vars = {};
    window.location.href.replace(location.hash, '').replace(
        /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
        function(m, key, value) { // callback
            vars[key] = value !== undefined ? value : '';
        }
    );
    if (param) {
        return vars[param] ? vars[param] : null;
    }
    return vars;
}

// Is mobile checker javascript
function is_mobile() {
    if (typeof(app) != 'undefined' && typeof(app.is_mobile) != 'undefined') {
        return app.is_mobile;
    }

    try { document.createEvent("TouchEvent"); return true; } catch (e) { return false; }
}

function onGoogleApiLoad() {
    var pickers = $('.gpicker');
    $.each(pickers, function() {
        var that = $(this);
        setTimeout(function() {
            that.googleDrivePicker();
        }, 10)
    });
}

function _get_jquery_comments_default_config(discussions_lang) {
    return {
        roundProfilePictures: true,
        textareaRows: 4,
        textareaRowsOnFocus: 6,
        profilePictureURL: discussion_user_profile_image_url,
        enableUpvoting: false,
        enableDeletingCommentWithReplies: false,
        enableAttachments: true,
        popularText: '',
        enableDeleting: true,
        textareaPlaceholderText: discussions_lang.discussion_add_comment,
        newestText: discussions_lang.discussion_newest,
        oldestText: discussions_lang.discussion_oldest,
        attachmentsText: discussions_lang.discussion_attachments,
        sendText: discussions_lang.discussion_send,
        replyText: discussions_lang.discussion_reply,
        editText: discussions_lang.discussion_edit,
        editedText: discussions_lang.discussion_edited,
        youText: discussions_lang.discussion_you,
        saveText: discussions_lang.discussion_save,
        deleteText: discussions_lang.discussion_delete,
        viewAllRepliesText: discussions_lang.discussion_view_all_replies + ' (__replyCount__)',
        hideRepliesText: discussions_lang.discussion_hide_replies,
        noCommentsText: discussions_lang.discussion_no_comments,
        noAttachmentsText: discussions_lang.discussion_no_attachments,
        attachmentDropText: discussions_lang.discussion_attachments_drop,
        timeFormatter: function(time) {
            return moment(time).fromNow();
        },
    }
}


function appDataTableInline(element, options) {

    var selector = typeof(element) !== 'undefined' ? element : '.dt-table';
    var $tables = $(selector);

    if ($tables.length === 0) {
        return;
    }

    var defaults = {
        scrollResponsive: 0,
        supportsButtons: false,
        supportsLoading: false,
        dtLengthMenuAllText: app.lang.dt_length_menu_all,
        processing: true,
        language: app.lang.datatables,
        paginate: true,
        pageLength: app.options.tables_pagination_limit,
        fnRowCallback: DataTablesInlineLazyLoadImages,
        order: [0, 'asc'],
        dom: "<'row'><'row'<'col-md-6'lB><'col-md-6'f>r>t<'row'<'col-md-4'i>><'row'<'#colvis'><'.dt-page-jump'>p>",
        "fnDrawCallback": function(oSettings) {

            _table_jump_to_page(this, oSettings);

            if (oSettings.aoData.length == 0 || oSettings.aiDisplay.length == 0) {
                $(oSettings.nTableWrapper).addClass('app_dt_empty');
            } else {
                $(oSettings.nTableWrapper).removeClass('app_dt_empty');
            }

            if (typeof(settings.onDrawCallback) == 'function') {
                settings.onDrawCallback(oSettings, this);
            }
        },
        "initComplete": function(oSettings, json) {

            if (this.hasClass('scroll-responsive') || settings.scrollResponsive == 1) {
                this.wrap('<div class="table-responsive"></div>');
            }

            var dtInlineEmpty = this.find('.dataTables_empty');
            if (dtInlineEmpty.length) {
                dtInlineEmpty.attr('colspan', this.find('thead th').length);
            }

            if (settings.supportsLoading) {
                this.parents('.table-loading').removeClass('table-loading');
            }

            if (settings.supportsButtons) {
                var thLastChild = $tables.find('thead th:last-child');

                if (thLastChild.hasClass('options')) {
                    thLastChild.addClass('not-export');
                }

                var thLastChild = $tables.find('thead th:last-child');
                if (typeof(app) != 'undefined' && thLastChild.text().trim() == app.lang.options) {
                    thLastChild.addClass('not-export');
                }

                var thFirstChild = $tables.find('thead th:first-child');
                if (thFirstChild.find('input[type="checkbox"]').length > 0) {
                    thFirstChild.addClass('not-export');
                }

                if (typeof(settings.onInitComplete) == 'function') {
                    settings.onInitComplete(oSettings, json, this);
                }
            }

        },
    }

    var settings = $.extend({}, defaults, options);
    var length_options = [10, 25, 50, 100];
    var length_options_names = [10, 25, 50, 100];

    settings.pageLength = parseFloat(settings.pageLength);

    if ($.inArray(settings.pageLength, length_options) == -1) {
        length_options.push(settings.pageLength)
        length_options_names.push(settings.pageLength)
    }

    length_options.sort(function(a, b) {
        return a - b;
    });

    length_options_names.sort(function(a, b) {
        return a - b;
    });

    length_options.push(-1);
    length_options_names.push(settings.dtLengthMenuAllText);

    var orderCol, orderType, sTypeColumns;
    settings.lengthMenu = [length_options, length_options_names];

    if (!settings.supportsButtons) {
        settings.dom = settings.dom.replace('lB', 'l')
    }

    $.each($tables, function() {

        $(this).addClass('dt-inline');

        if ($(this).hasClass('scroll-responsive') || settings.scrollResponsive == 1) {
            settings.responsive = false;
        }

        orderCol = $(this).attr('data-order-col');
        orderType = $(this).attr('data-order-type');
        sTypeColumns = $(this).attr('data-s-type');

        if (orderCol && orderType) {
            settings.order = [
                [orderCol, orderType]
            ];
        }

        if (sTypeColumns) {
            sTypeColumns = JSON.parse(sTypeColumns);
            var columns = $(this).find('thead th');
            var totalColumns = columns.length;
            settings.aoColumns = [];
            for (var i = 0; i < totalColumns; i++) {
                var column = $(columns[i]);
                var sTypeColumnOption = sTypeColumns.find(function(v) {
                    return v['column'] === column.index();
                });
                settings.aoColumns.push(sTypeColumnOption ? { sType: sTypeColumnOption.type } : null);
            }
        }

        if (settings.supportsButtons) {
            settings.buttons = get_datatable_buttons(this);
        }

        $(this).DataTable(settings);
    });
}

// Returns datatbles export button array based on settings
// Admin area only
function get_datatable_buttons(table) {
    // pdfmake arabic fonts support
    if (app.user_language.toLowerCase() == 'persian' || app.user_language.toLowerCase() == 'arabic') {
        if ($('body').find('#amiri').length === 0) {
            var mainjs = document.createElement('script');
            mainjs.setAttribute('src', 'https://rawgit.com/xErik/pdfmake-fonts-google/master/build/script/ofl/amiri.js');
            mainjs.setAttribute('id', 'amiri');
            document.head.appendChild(mainjs);

            var mapjs = document.createElement('script');
            mapjs.setAttribute('src', 'https://rawgit.com/xErik/pdfmake-fonts-google/master/build/script/ofl/amiri.map.js');
            document.head.appendChild(mapjs);
        }
    }

    var formatExport = {
        body: function(data, row, column, node) {

            // Fix for notes inline datatables
            // Causing issues because of the hidden textarea for edit and the content is duplicating
            // This logic may be extended in future for other similar fixes
            var newTmpRow = $('<div></div>', data);
            newTmpRow.append(data);

            if (newTmpRow.find('[data-note-edit-textarea]').length > 0) {
                newTmpRow.find('[data-note-edit-textarea]').remove();
                data = newTmpRow.html().trim();
            }
            // Convert e.q. two months ago to actual date
            var exportTextHasActionDate = newTmpRow.find('.text-has-action.is-date');

            if(exportTextHasActionDate.length) {
               data = exportTextHasActionDate.attr('data-title');
            }

            if (newTmpRow.find('.row-options').length > 0) {
                newTmpRow.find('.row-options').remove();
                data = newTmpRow.html().trim();
            }

            if (newTmpRow.find('.table-export-exclude').length > 0) {
                newTmpRow.find('.table-export-exclude').remove();
                data = newTmpRow.html().trim();
            }

            if (data) {
         /*       // 300,00 becomes 300.00 because excel does not support decimal as coma
                var regexFixExcelExport = new RegExp("([0-9]{1,3})(,)([0-9]{" + app.options.decimal_places + ',' + app.options.decimal_places + "})", "gm");
                // Convert to string because matchAll won't work on integers in case datatables convert the text to integer
                var _stringData = data.toString();
                var found = _stringData.matchAll(regexFixExcelExport);
                if (found) {
                    data = data.replace(regexFixExcelExport, "$1.$3");
                }*/
            }

            // Datatables use the same implementation to strip the html.
            var div = document.createElement("div");
            div.innerHTML = data;
            var text = div.textContent || div.innerText || "";

            return text.trim();
        }
    };
    var table_buttons_options = [];

    if (typeof(table_export_button_is_hidden) != 'function' || !table_export_button_is_hidden()) {
        table_buttons_options.push({
            extend: 'collection',
            text: app.lang.dt_button_export,
            className: 'btn btn-default-dt-options',
            buttons: [{
                extend: 'excel',
                text: app.lang.dt_button_excel,
                footer: true,
                exportOptions: {
                    columns: [':not(.not-export)'],
                    rows: function(index) {
                        return _dt_maybe_export_only_selected_rows(index, table);
                    },
                    format: formatExport,
                },
            }, {
                extend: 'csvHtml5',
                text: app.lang.dt_button_csv,
                footer: true,
                exportOptions: {
                    columns: [':not(.not-export)'],
                    rows: function(index) {
                        return _dt_maybe_export_only_selected_rows(index, table);
                    },
                    format: formatExport,
                }
            }, {
                extend: 'pdfHtml5',
                text: app.lang.dt_button_pdf,
                footer: true,
                exportOptions: {
                    columns: [':not(.not-export)'],
                    rows: function(index) {
                        return _dt_maybe_export_only_selected_rows(index, table);
                    },
                    format: formatExport,
                },
                orientation: 'landscape',
                customize: function(doc) {
                    // Fix for column widths
                    var table_api = $(table).DataTable();
                    var columns = table_api.columns().visible();
                    var columns_total = columns.length;
                    var pdf_widths = [];
                    var total_visible_columns = 0;
                    for (i = 0; i < columns_total; i++) {
                        // Is only visible column
                        if (columns[i] == true) {
                            total_visible_columns++;
                        }
                    }
                    setTimeout(function() {
                        if (total_visible_columns <= 5) {
                            for (i = 0; i < total_visible_columns; i++) {
                                pdf_widths.push((735 / total_visible_columns));
                            }
                            doc.content[1].table.widths = pdf_widths;
                        }
                    }, 10);

                    if (app.user_language.toLowerCase() == 'persian' || app.user_language.toLowerCase() == 'arabic') {
                        doc.defaultStyle.font = Object.keys(pdfMake.fonts)[0];
                    }
                    //  doc.defaultStyle.font = 'test';
                    doc.styles.tableHeader.alignment = 'left';
                    doc.styles.tableHeader.margin = [5, 5, 5, 5];
                    doc.pageMargins = [12, 12, 12, 12];
                }
            }, {
                extend: 'print',
                text: app.lang.dt_button_print,
                footer: true,
                exportOptions: {
                    columns: [':not(.not-export)'],
                    rows: function(index) {
                        return _dt_maybe_export_only_selected_rows(index, table);
                    },
                    format: formatExport,
                }
            }],
        });
    }
    var tableButtons = $("body").find('.table-btn');

    $.each(tableButtons, function() {
        var b = $(this);
        if (b.length && b.attr('data-table')) {
            if ($(table).is(b.attr('data-table'))) {
                table_buttons_options.push({
                    text: b.text().trim(),
                    className: 'btn btn-default-dt-options',
                    action: function(e, dt, node, config) {
                        b.click();
                    }
                });
            }
        }
    });

    if (!$(table).hasClass('dt-inline')) {
        table_buttons_options.push({
            text: '<i class="fa fa-refresh"></i>',
            className: 'btn btn-default-dt-options btn-dt-reload',
            action: function(e, dt, node, config) {
                dt.ajax.reload();
            }
        });
    }

    // TODO
    // console.log

    /*   if ($(table).hasClass('customizable-table')) {
            table_buttons_options.push({
                columns: '.toggleable',
                text: '<i class="fa fa-cog"></i>',
                extend: 'colvis',
                className: 'btn btn-default-dt-options dt-column-visibility',
            });
        }*/

    return table_buttons_options;
}

// Check if table export button should be hidden based on settings
// Admin area only
function table_export_button_is_hidden() {
    if (app.options.show_table_export_button != 'to_all') {
        if (app.options.show_table_export_button === 'hide' || app.options.show_table_export_button === 'only_admins' &&
            app.user_is_admin == 0) {
            return true;
        }
    }
    return false;
}

function _dt_maybe_export_only_selected_rows(index, table) {
    table = $(table);
    index = index.toString();
    var bulkActionsCheckbox = table.find('thead th input[type="checkbox"]').eq(0);
    if (bulkActionsCheckbox && bulkActionsCheckbox.length > 0) {
        var rows = table.find('tbody tr');
        var anyChecked = false;
        $.each(rows, function() {
            if ($(this).find('td:first input[type="checkbox"]:checked').length) {
                anyChecked = true;
            }
        });

        if (anyChecked) {
            if (table.find('tbody tr:eq(' + (index) + ') td:first input[type="checkbox"]:checked').length > 0) {
                return index;
            } else {
                return null;
            }
        } else {
            return index;
        }
    }
    return index;
}


// Slide toggle any selector passed
function slideToggle(selector, callback) {
    var $element = $(selector);
    if ($element.hasClass('hide')) { $element.removeClass('hide', 'slow'); }
    if ($element.length) { $element.slideToggle(); }
    // Set all progress bar to 0 percent
    var progress_bars = $('.progress-bar').not('.not-dynamic');
    if (progress_bars.length > 0) {
        progress_bars.each(function() {
            $(this).css('width', 0 + '%');
            $(this).text(0 + '%');
        });
        // Init the progress bars again
        if (typeof(appProgressBar) == 'function') {
            appProgressBar();
        }
    }
    // Possible callback after slide toggle
    if (typeof(callback) == 'function') { callback(); }
}

// Date picker init, options and optionally element
function appDatepicker(options) {

    if (typeof(app._date_picker_locale_configured) === 'undefined') {
        jQuery.datetimepicker.setLocale(app.locale);
        app._date_picker_locale_configured = true;
    }

    var defaults = {
        date_format: app.options.date_format,
        time_format: app.options.time_format,
        week_start: app.options.calendar_first_day,
        date_picker_selector: '.datepicker',
        date_time_picker_selector: '.datetimepicker',
    }

    var settings = $.extend({}, defaults, options);

    var datepickers = typeof(settings.element_date) != 'undefined' ? settings.element_date : $(settings.date_picker_selector);
    var datetimepickers = typeof(settings.element_time) != 'undefined' ? settings.element_time : $(settings.date_time_picker_selector);

    if (datetimepickers.length === 0 && datepickers.length === 0) {
        return;
    }

    // Datepicker without time
    $.each(datepickers, function() {
        var that = $(this);

        var opt = {
            timepicker: false,
            scrollInput: false,
            lazyInit: true,
            format: settings.date_format,
            dayOfWeekStart: settings.week_start,
        };

        // Check in case the input have date-end-date or date-min-date
        var max_date = that.attr('data-date-end-date');
        var min_date = that.attr('data-date-min-date');
        var lazy = that.attr('data-lazy');

        if (lazy) {
            opt.lazyInit = lazy == 'true';
        }

        if (max_date) {
            opt.maxDate = max_date;
        }

        if (min_date) {
            opt.minDate = min_date;
        }

        // Init the picker
        that.datetimepicker(opt);

        that.parents('.form-group').find('.calendar-icon').on('click', function() {
            that.focus();
            that.trigger('open.xdsoft');
        });
    });

    // Datepicker with time
    $.each(datetimepickers, function() {
        var that = $(this);
        var opt_time = {
            lazyInit: true,
            scrollInput: false,
            validateOnBlur: false,
            dayOfWeekStart: settings.week_start
        };
        if (settings.time_format == 24) {
            opt_time.format = settings.date_format + ' H:i';
        } else {
            opt_time.format = settings.date_format + ' g:i A';
            opt_time.formatTime = 'g:i A';
        }
        // Check in case the input have date-end-date or date-min-date
        var max_date = that.attr('data-date-end-date');
        var min_date = that.attr('data-date-min-date');
        var lazy = that.attr('data-lazy');

        if (lazy) {
            opt.lazyInit = lazy == 'true';
        }

        if (max_date) {
            opt_time.maxDate = max_date;
        }

        if (min_date) {
            opt_time.minDate = min_date;
        }
        // Init the picker
        that.datetimepicker(opt_time);

        that.parents('.form-group').find('.calendar-icon').on('click', function() {
            that.focus();
            that.trigger('open.xdsoft');
        });
    });
}

function appTagsInput(element) {

    if (typeof(element) == 'undefined') {
        element = $("body").find('input.tagsinput');
    }

    if (element.length) {
        element.tagit({
            availableTags: app.available_tags,
            allowSpaces: true,
            animate: false,
            placeholderText: app.lang.tag,
            showAutocompleteOnFocus: true,
            caseSensitive: false,
            autocomplete: {
                appendTo: '#inputTagsWrapper',
            },
            afterTagAdded: function(event, ui) {
                var tagIndexAvailable = app.available_tags.indexOf($.trim($(ui.tag).find('.tagit-label').text()));
                if (tagIndexAvailable > -1) {
                    var _tagId = app.available_tags_ids[tagIndexAvailable];
                    $(ui.tag).addClass('tag-id-' + _tagId);
                }
                showHideTagsPlaceholder($(this));
            },
            afterTagRemoved: function(event, ui) {
                showHideTagsPlaceholder($(this));
            }
        });
    }
}
// Fix for reordering the items the tables to show the full width
function fixHelperTableHelperSortable(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
}

// Predefined and default dropzone plugin options
function _dropzone_defaults() {

    var acceptedFiles = app.options.allowed_files;

    // https://discussions.apple.com/thread/7229860
    if (app.browser === 'safari' &&
        acceptedFiles.indexOf('.jpg') > -1 &&
        acceptedFiles.indexOf('.jpeg') === -1) {
        acceptedFiles += ',.jpeg';
    }

    return {
        createImageThumbnails: true,
        dictDefaultMessage: app.lang.drop_files_here_to_upload,
        dictFallbackMessage: app.lang.browser_not_support_drag_and_drop,
        dictFileTooBig: app.lang.file_exceeds_maxfile_size_in_form,
        dictCancelUpload: app.lang.cancel_upload,
        dictRemoveFile: app.lang.remove_file,
        dictMaxFilesExceeded: app.lang.you_can_not_upload_any_more_files,
        maxFilesize: (app.max_php_ini_upload_size_bytes / (1024 * 1024)).toFixed(0),
        acceptedFiles: acceptedFiles,
        error: function(file, response) {
            alert_float('danger', response);
        },
        complete: function(file) {
            this.files.length && this.removeFile(file);
        },
    };
}

function appCreateDropzoneOptions(options) {
    return $.extend({}, _dropzone_defaults(), options);
}

function onChartClickRedirect(evt, chart, fetchUrl) {
    if (typeof(fetchUrl) == 'undefined') {
        fetchUrl = 'statusLink';
    }
    var item = chart.getElementAtEvent(evt)[0];
    if (item) {
        var link = chart.data.datasets[0][fetchUrl][item['_index']];
        if (link) {
            window.location.href = link;
        }
    }
}

// Clear memory leak
// Only use it if all libraries are included
function destroy_dynamic_scripts_in_element(element) {
    element.find('input.tagsinput').tagit('destroy')
        .find('.manual-popover').popover('destroy')
        .find('.datepicker').datetimepicker('destroy')
        .find('select').selectpicker('destroy');
}

// Old validate form function, callback to _validate_form
// You should use only $(form).appFormValidator();
function appValidateForm(form, form_rules, submithandler, overwriteMessages) {
    $(form).appFormValidator({ rules: form_rules, onSubmit: submithandler, messages: overwriteMessages });
}

function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
