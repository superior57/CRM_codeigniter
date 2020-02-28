$.fn.dataTable.ext.type.order['task-status-pre'] = function(d) {
    switch (d) {
        case '2':
            return 1;
        case '4':
            return 2;
        case '3':
            return 3;
        case '1':
            return 4;
        case '5':
            return 6;
    }
    return 5;
};

var project_id = $('input[name="project_id"]').val();
var discussion_user_profile_image_url = $('input[name="discussion_user_profile_image_url"]').val();
var discussion_id = $('input[name="discussion_id"]').val();

Dropzone.options.projectFilesUpload = false;
Dropzone.options.taskFileUpload = false;
Dropzone.options.filesUpload = false;

if (app.options.enable_google_picker == '1') {
    $.fn.googleDrivePicker.defaults.clientId = app.options.google_client_id;
    $.fn.googleDrivePicker.defaults.developerKey = app.options.google_api;
}

var salesChart;

$(function() {

    // Set moment locale
    moment.locale(app.locale);
    // Set timezone locale
    moment().tz(app.options.timezone).format();

    fix_phases_height();

    initDataTable();

    client_home_chart();

    $('select[name="currency"],select[name="payments_years"]').on('change', function() {
        client_home_chart();
    });

    $('#open-new-ticket-form').appFormValidator();

    $('#ticket-reply').appFormValidator();

    $('#task-form').appFormValidator();

    $('#discussion_form').appFormValidator({
        rules: {
            subject: 'required',
        }
    });

    var file_id = get_url_param('file_id');

    if (file_id) {
        view_project_file(file_id, project_id);
    }

    if (typeof(discussion_id != 'undefined')) {
        discussion_comments('#discussion-comments', discussion_id, 'regular');
    }

    $('body').on('show.bs.modal', '._project_file', function() {
        discussion_comments('#project-file-discussion', discussion_id, 'file');
    });

    if (typeof(Dropbox) != 'undefined') {
        if ($('#dropbox-chooser-task').length > 0) {
            document.getElementById("dropbox-chooser-task").appendChild(Dropbox.createChooseButton({
                success: function(files) {
                    taskExternalFileUpload(files, 'dropbox');
                },
                linkType: "preview",
                extensions: app.options.allowed_files.split(','),
            }));
        }

        if ($('#files-upload').length > 0) {
            document.getElementById("dropbox-chooser-files").appendChild(Dropbox.createChooseButton({
                success: function(files) {
                    customerExternalFileUpload(files, 'dropbox');
                },
                linkType: "preview",
                extensions: app.options.allowed_files.split(','),
            }));
        }

        if (typeof(Dropbox) != 'undefined' && $('#dropbox-chooser-project-files').length > 0) {
            document.getElementById("dropbox-chooser-project-files").appendChild(Dropbox.createChooseButton({
                success: function(files) {
                    projectExternalFileUpload(files, 'dropbox');
                },
                linkType: "preview",
                extensions: app.options.allowed_files.split(','),
            }));
        }
    }

    if ($('#calendar').length) {
        var settings = {
            themeSystem: 'bootstrap3',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            editable: false,
            eventLimit: parseInt(app.options.calendar_events_limit) + 1,
            views: {
                day: {
                    eventLimit: false
                }
            },
            defaultView: app.options.default_view_calendar,
            eventLimitClick: function(cellInfo, jsEvent) {
                $('#calendar').fullCalendar('gotoDate', cellInfo.date);
                $('#calendar').fullCalendar('changeView', 'basicDay');
            },
            loading: function(isLoading, view) {
                isLoading && $('#calendar .fc-header-toolbar .btn-default').addClass('btn-info').removeClass('btn-default').css('display', 'block');
                !isLoading ? $('.dt-loader').addClass('hide') : $('.dt-loader').removeClass('hide');
            },
            isRTL: (app.options.isRTL == 'true' ? true : false),
            eventStartEditable: false,
            firstDay: parseInt(app.options.calendar_first_day),
            eventSources: [{
                url: site_url + 'clients/get_calendar_data',
                type: 'GET',
                error: function() {
                    console.error('There was error fetching calendar data')
                },
            }, ],
            eventRender: function(event, element) {
                element.attr('title', event._tooltip);
                element.attr('onclick', event.onclick);
                element.attr('data-toggle', 'tooltip');
            },
        }
        // Init calendar
        $('#calendar').fullCalendar(settings);
    }

    var tab_group = get_url_param('group');
    if (tab_group) {
        $('body').find('.nav-tabs li').removeClass('active');
        $('body').find('.nav-tabs [data-group="' + tab_group + '"]').parents('li').addClass('active');
    }

    for (var i = -10; i < $('.task-phase').not('.color-not-auto-adjusted').length / 2; i++) {
        var r = 120;
        var g = 169;
        var b = 56;
        $('.task-phase:eq(' + (i + 10) + ')').not('.color-not-auto-adjusted').css('background', color(r - (i * 13), g - (i * 13), b - (i * 13))).css('border', '1px solid ' + color(r - (i * 13), g - (i * 13), b - (i * 13)));
    };

    var circle = $('.project-progress').circleProgress({
        fill: {
            gradient: ["#84c529", "#84c529"]
        }
    }).on('circle-animation-progress', function(event, progress, stepValue) {
        $(this).find('strong.project-percent').html(parseInt(100 * stepValue) + '<i>%</i>');
    });

    $('.toggle-change-ticket-status').on('click', function() {
        $('.ticket-status,.ticket-status-inline').toggleClass('hide');
    });

    $('#ticket_status_single').on('change', function() {
        data = {};
        data.status_id = $(this).val();
        data.ticket_id = $('input[name="ticket_id"]').val();
        $.post(site_url + 'clients/change_ticket_status/', data).done(function() {
            window.location.reload();
        });
    });

    if (typeof(contracts_by_type) != 'undefined') {
        new Chart($('#contracts-by-type-chart'), {
            type: 'bar',
            data: JSON.parse(contracts_by_type),
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        display: true,
                        ticks: {
                            beginAtZero: true,
                        }
                    }]
                }
            }
        });
    }

    if ($('#files-upload').length > 0) {
        createDropzone('#files-upload');
    }

    if ($('#task-file-upload').length > 0) {
        createDropzone('#task-file-upload', {
            sending: function(file, xhr, formData) {
                formData.append("action", 'upload_task_file');
                formData.append("task_id", $('input[name="task_id"]').val());
            },
        });
    }

    if ($('#project-files-upload').length > 0) {
        createDropzone('#project-files-upload', {
            sending: function(file, xhr, formData) {
                formData.append("action", 'upload_file');
            },
        });
    }

    // User cant add more money then the invoice total remaining
    $('body.viewinvoice input[name="amount"]').on('keyup', function() {
        var original_total = $(this).data('total');
        var val = $(this).val();
        var form_group = $(this).parents('.form-group');
        if (val > original_total) {
            form_group.addClass('has-error');
            if (form_group.find('p.text-danger').length == 0) {
                form_group.append('<p class="text-danger">Maximum pay value passed</p>');
                $(this).parents('form').find('input[name="make_payment"]').attr('disabled', true);
            }
        } else {
            form_group.removeClass('has-error');
            form_group.find('p.text-danger').remove();
            $(this).parents('form').find('input[name="make_payment"]').attr('disabled', false);
        }
    });


    $('#discussion').on('hidden.bs.modal', function(event) {
        $('#discussion input[name="subject"]').val('');
        $('#discussion textarea[name="description"]').val('');
        $('#discussion .add-title').removeClass('hide');
        $('#discussion .edit-title').removeClass('hide');
    });

});

function new_discussion() {
    $('#discussion').modal('show');
    $('#discussion .edit-title').addClass('hide');
}

function manage_discussion(form) {
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            alert_float('success', response.message);
        }
        $('.table-project-discussions').DataTable().ajax.reload(null, false);
        $('#discussion').modal('hide');
    });
    return false;
}

function remove_task_comment(commentid) {
    $.get(site_url + 'clients/remove_task_comment/' + commentid, function(response) {
        if (response.success == true) {
            window.location.reload();
        }
    }, 'json');
}

function edit_task_comment(id) {
    var edit_wrapper = $('[data-edit-comment="' + id + '"]');
    edit_wrapper.removeClass('hide');
    $('[data-comment-content="' + id + '"]').addClass('hide');
}

function cancel_edit_comment(id) {
    var edit_wrapper = $('[data-edit-comment="' + id + '"]');
    edit_wrapper.addClass('hide');
    $('[data-comment-content="' + id + '"]').removeClass('hide');
}

function save_edited_comment(id) {
    var data = {};
    data.id = id;
    data.content = $('[data-edit-comment="' + id + '"]').find('textarea').val();
    $.post(site_url + 'clients/edit_comment', data).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            window.location.reload();
        } else {
            cancel_edit_comment(id);
        }
    });
}

function initDataTable() {
    appDataTableInline(undefined, {
        scrollResponsive: true,
    });
}

function dt_custom_view(table, column, val) {
    var tableApi = $(table).DataTable();
    if (Array.isArray(val)) {
        tableApi.column(column).search(val.join('|'), true, false).draw();
    } else {
        tableApi.column(column).search(val).draw();
    }
}

function fix_phases_height() {
    if (is_mobile()) {
        return;
    }
    var maxPhaseHeight = Math.max.apply(null, $("div.tasks-phases .panel-body").map(function() {
        return $(this).outerHeight();
    }).get());

    $('div.tasks-phases .panel-body').css('min-height', maxPhaseHeight + 'px');
}

function taskTable() {
    $('.tasks-table').toggleClass('hide');
    $('.tasks-phases').toggleClass('hide');
}

function discussion_comments(selector, discussion_id, discussion_type) {
    var defaults = _get_jquery_comments_default_config(app.lang.discussions_lang);

    var options = {
        getComments: function(success, error) {
            $.post(site_url + 'clients/project/' + project_id, {
                action: 'discussion_comments',
                discussion_id: discussion_id,
                discussion_type: discussion_type,
            }).done(function(response) {
                response = JSON.parse(response);
                success(response);
            });
        },
        postComment: function(commentJSON, success, error) {
            commentJSON.action = 'new_discussion_comment';
            commentJSON.discussion_id = discussion_id;
            commentJSON.discussion_type = discussion_type;
            $.ajax({
                type: 'post',
                url: site_url + 'clients/project/' + project_id,
                data: commentJSON,
                success: function(comment) {
                    comment = JSON.parse(comment);
                    success(comment)
                },
                error: error
            });
        },
        putComment: function(commentJSON, success, error) {
            commentJSON.action = 'update_discussion_comment';
            $.ajax({
                type: 'post',
                url: site_url + 'clients/project/' + project_id,
                data: commentJSON,
                success: function(comment) {
                    comment = JSON.parse(comment);
                    success(comment)
                },
                error: error
            });
        },
        deleteComment: function(commentJSON, success, error) {
            $.ajax({
                type: 'post',
                url: site_url + 'clients/project/' + project_id,
                success: success,
                error: error,
                data: {
                    id: commentJSON.id,
                    action: 'delete_discussion_comment'
                }
            });
        },
        uploadAttachments: function(commentArray, success, error) {
            var responses = 0;
            var successfulUploads = [];

            var serverResponded = function() {
                responses++;
                // Check if all requests have finished
                if (responses == commentArray.length) {
                    // Case: all failed
                    if (successfulUploads.length == 0) {
                        error();
                        // Case: some succeeded
                    } else {
                        successfulUploads = JSON.parse(successfulUploads);
                        success(successfulUploads)
                    }
                }
            }
            $(commentArray).each(function(index, commentJSON) {
                if (commentJSON.file.size && commentJSON.file.size > app.max_php_ini_upload_size_bytes) {
                    alert_float('danger', app.lang.file_exceeds_max_filesize);
                    serverResponded();
                } else {
                    // Create form data
                    var formData = new FormData();
                    $(Object.keys(commentJSON)).each(function(index, key) {
                        var value = commentJSON[key];
                        if (value) formData.append(key, value);
                    });

                    formData.append('action', 'new_discussion_comment');
                    formData.append('discussion_id', discussion_id);
                    formData.append('discussion_type', discussion_type);

                    if (typeof(csrfData) !== 'undefined') {
                        formData.append(csrfData['token_name'], csrfData['hash']);
                    }

                    $.ajax({
                        url: site_url + 'clients/project/' + project_id,
                        type: 'POST',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(commentJSON) {
                            successfulUploads.push(commentJSON);
                            serverResponded();
                        },
                        error: function(data) {
                            var error = JSON.parse(data.responseText);
                            alert_float('danger', error.message);
                            serverResponded();
                        },
                    });
                }
            });
        }
    };

    var settings = $.extend({}, defaults, options);

    $(selector).comments(settings);
}

function view_project_file(id, project_id) {
    $.post(site_url + 'clients/project/' + project_id, {
        action: 'get_file',
        id: id,
        project_id: project_id
    }).done(function(response) {
        $('#project_file_data').html(response);
    }).fail(function(error) {
        alert_float('danger', error.statusText);
    });
}

function update_file_data(id) {
    var data = {};
    data.id = id;
    data.subject = $('body input[name="file_subject"]').val();
    data.description = $('body textarea[name="file_description"]').val();
    data.action = 'update_file_data';
    $.post(site_url + 'clients/project/' + project_id, data);
}

function render_customer_statement() {
    var $statementPeriod = $('#range');
    var value = $statementPeriod.selectpicker('val');
    var period = new Array();
    var isPeriod = false;
    if (value != 'period') {
        period = JSON.parse(value);
    } else {

        period[0] = $('input[name="period-from"]').val();
        period[1] = $('input[name="period-to"]').val();

        if (period[0] == '' || period[1] == '') {
            return false;
        }
        isPeriod = true;
    }
    var statementUrl = site_url + 'clients/statement';
    var statementUrlParams = new Array();
    statementUrlParams['from'] = period[0];
    statementUrlParams['to'] = period[1];
    if (isPeriod) {
        statementUrlParams['custom_period'] = true;
    }
    window.location.href = buildUrl(statementUrl, statementUrlParams);
}

function client_home_chart() {
    // Check if chart canvas exists.
    var chart = $('#client-home-chart');
    if (chart.length == 0) {
        return;
    }
    if (typeof(salesChart) !== 'undefined') {
        salesChart.destroy();
    }
    var data = {};
    var currency = $('#currency');
    var year = $('#payments_year');
    if (currency.length > 0) {
        data.report_currency = $('select[name="currency"]').val();
    }
    if (year.length > 0) {
        data.year = $('#payments_year').val();
    }

    $.post(site_url + 'clients/client_home_chart', data).done(function(response) {
        response = JSON.parse(response);
        salesChart = new Chart(chart, {
            type: 'bar',
            data: response,
            options: { responsive: true, maintainAspectRatio: false }
        });
    });
}

function projectFileGoogleDriveSave(pickData) {
    projectExternalFileUpload(pickData, 'gdrive');
}

function customerFileGoogleDriveSave(pickData) {
    customerExternalFileUpload(pickData, 'gdrive');
}

function taskFileGoogleDriveSave(pickData) {
    taskExternalFileUpload(pickData, 'gdrive');
}

function projectExternalFileUpload(files, externalType) {
    $.post(site_url + 'clients/project/' + project_id, {
        files: files,
        external: externalType,
        action: 'project_external_file',
    }).done(function() {
        var location = window.location.href;
        window.location.href = location.split('?')[0] + '?group=project_files';
    });
}

function taskExternalFileUpload(files, externalType) {
    $.post(site_url + 'clients/project/' + project_id, {
        files: files,
        task_id: $('input[name="task_id"]').val(),
        external: externalType,
        action: 'add_task_external_file'
    }).done(function() {
        window.location.reload();
    });
}

function customerExternalFileUpload(files, externalType) {
    $.post(site_url + 'clients/upload_files', {
        files: files,
        external: externalType,
    }).done(function() {
        window.location.reload();
    });
}
