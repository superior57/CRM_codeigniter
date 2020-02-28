tinymce.PluginManager.add('stickytoolbar', function(editor, url) {
    editor.on('init', function() {
        setSticky();
    });

    $(window).on('scroll', function() {
        setSticky();
    });

    function setSticky() {
        var container = editor.editorContainer;
        var toolbars = $(container).find('.mce-toolbar-grp');
        var statusbar = $(container).find('.mce-statusbar');

        if (isSticky()) {
            $(container).css({
                paddingTop: toolbars.outerHeight()
            });

            if (isAtBottom()) {
                toolbars.css({
                    top: 'auto',
                    bottom: statusbar.outerHeight(),
                    position: 'absolute',
                    width: '100%',
                    borderBottom: 'none'
                });
            } else {
                toolbars.css({
                    top: 0,
                    bottom: 'auto',
                    position: 'fixed',
                    width: $(container).width(),
                    borderBottom: '1px solid rgba(0,0,0,0.2)'
                });
            }
        } else {
            $(container).css({
                paddingTop: 0
            });

            toolbars.css({
                position: 'relative',
                width: 'auto',
                borderBottom: 'none'
            });
        }
    }

    function isSticky() {
        var container = editor.editorContainer,
            editorTop = container.getBoundingClientRect().top;

        if (editorTop < 0) {
            return true;
        }

        return false;
    }

    function isAtBottom() {
        var container = editor.editorContainer,
            editorTop = container.getBoundingClientRect().top;

        var toolbarHeight = $(container).find('.mce-toolbar-grp').outerHeight();
        var footerHeight = $(container).find('.mce-statusbar').outerHeight();

        var hiddenHeight = -($(container).outerHeight() - toolbarHeight - footerHeight);

        if (editorTop < hiddenHeight) {
            return true;
        }

        return false;
    }
});
