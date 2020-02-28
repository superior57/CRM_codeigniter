/*!
 * Internal Google Drive Picker Plugin.
 *
 * https://perfexcrm.com/
 *
 * Copyright (c) 2019 Marjan Stojanov
 */

(function($) {

    $.fn.googleDrivePicker = function(options) {

        var pickerApiLoaded = false;
        var oauthToken;

        var internal = {
            // Use the API Loader script to load google.picker and gapi.auth.
            initGooglePickerAPI: function(element) {
                gapi.load('auth2', function() {
                    internal.onAuthApiLoad(element)
                });
                gapi.load('picker', internal.onPickerApiLoad);
            },
            onAuthApiLoad: function(element) {
                element.disabled = false;
                element.addEventListener('click', function() {
                    gapi.auth2.authorize({
                        client_id: settings.clientId,
                        scope: settings.scope
                    }, internal.handleAuthResult);
                });
            },
            onPickerApiLoad: function() {
                pickerApiLoaded = true;
                internal.createPicker();
            },
            handleAuthResult: function(authResult) {
                if (authResult && !authResult.error) {
                    oauthToken = authResult.access_token;
                    internal.createPicker();
                } else if (authResult.error) {
                    console.error(authResult)
                }
            },
            createPicker: function() {
                if (pickerApiLoaded && oauthToken) {
                    var view = new google.picker.DocsView().setParent('root').setIncludeFolders(true)
                    var uploadView = new google.picker.DocsUploadView().setIncludeFolders(true);

                    if (settings.mimeTypes) {
                        view.setMimeTypes(settings.mimeTypes);
                        uploadView.setMimeTypes(settings.mimeTypes);
                    }

                    var picker = new google.picker.PickerBuilder().
                    addView(view).
                    addView(uploadView).
                    setOAuthToken(oauthToken).
                    setDeveloperKey(settings.developerKey).
                    setCallback(internal.pickerCallback).
                    build();
                    picker.setVisible(true);
                    setTimeout(function() {
                        $('.picker-dialog').css('z-index', 10002);
                    }, 10);
                }
            },
            pickerCallback: function(data) {
                var url;
                if (data[google.picker.Response.ACTION] == google.picker.Action.PICKED) {
                    var doc = data[google.picker.Response.DOCUMENTS][0];
                    var retVal = [{
                        name: doc[google.picker.Document.NAME],
                        link: doc[google.picker.Document.URL],
                        mime: doc[google.picker.Document.MIME_TYPE],
                    }];

                    typeof(settings.onPick) == 'function' ? settings.onPick(retVal): window[settings.onPick](retVal);
                }
            }
        }

        var settings = $.extend({}, $.fn.googleDrivePicker.defaults, options);

        return this.each(function() {
            if (settings.clientId) {
                if ($(this).data('on-pick')) {
                    settings.onPick = $(this).data('on-pick')
                }
                internal.initGooglePickerAPI($(this)[0]);
                $(this).css('opacity', 1)
            } else {
                // Not configured
                $(this).css('opacity', 0);
            }
        });
    };
}(jQuery));

$.fn.googleDrivePicker.defaults = {
    scope: 'https://www.googleapis.com/auth/drive',
    mimeTypes: null,
    // The Browser API key obtained from the Google API Console.
    developerKey: '',
    // The Client ID obtained from the Google API Console. Replace with your own Client ID.
    clientId: '',
    onPick: function(data) {}
}
