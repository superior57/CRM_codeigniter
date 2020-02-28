module.exports = function(grunt) {

    let migrationFile = grunt.file.read('./application/config/migration.php');
    let versionRegex = /(\['migration_version'\] = )(\d+;) (\/\/) (\d.\d.\d)/gm;

    let m;
    let versionConfig = '';
    while ((m = versionRegex.exec(migrationFile)) !== null) {
        // This is necessary to avoid infinite loops with zero-width matches
        if (m.index === versionRegex.lastIndex) {
            versionRegex.lastIndex++;
        }
        versionConfig = m[4];
    }

    let gruntConfig = {
        version_header: '/* v' + versionConfig + ' */',
        files: {
            dot: true
        },
        uglify: {
            my_target: {
                files: {
                    'assets/plugins/internal/google-picker/picker.min.js': ['assets/plugins/internal/google-picker/picker.js'],
                    'assets/plugins/internal/validation/app-form-validation.min.js': ['assets/plugins/internal/validation/app-form-validation.js'],
                    'assets/plugins/internal/highlight/highlight.min.js': ['assets/plugins/internal/highlight/highlight.js'],
                    'assets/plugins/internal/hotkeys/hotkeys.min.js': ['assets/plugins/internal/hotkeys/hotkeys.js'],
                    'assets/plugins/internal/desktop-notifications/notifications.min.js': ['assets/plugins/internal/desktop-notifications/notifications.js'],
                    'assets/themes/perfex/js/clients.min.js': ['assets/themes/perfex/js/clients.js'],
                    'assets/themes/perfex/js/global.min.js': ['assets/themes/perfex/js/global.js'],
                    'assets/js/main.min.js': ['assets/js/main.js'],
                    'assets/js/map.min.js': ['assets/js/map.js'],
                    'assets/js/projects.min.js': ['assets/js/projects.js'],
                    'assets/js/tickets.min.js': ['assets/js/tickets.js'],
                    'assets/builds/bootstrap-select.min.js': ['assets/builds/bootstrap-select.min.js'],
                    'assets/builds/moment.min.js': ['assets/builds/moment.min.js'],
                    'assets/plugins/tagsinput/js/tag-it.min.js': ['assets/plugins/tagsinput/js/tag-it.js'], // tag-it.js is modified
                    'assets/builds/vendor-admin.js': ['assets/builds/vendor-admin.js'],
                    'assets/builds/common.js': ['assets/builds/common.js'],
                }
            }
        },
        cssmin: {
            target: {
                files: [{
                        src: ['assets/themes/perfex/css/style.css'],
                        dest: 'assets/themes/perfex/css/style.min.css',
                        ext: '.min.css'
                    },
                    {
                        src: ['assets/css/bs-overides.css'],
                        dest: 'assets/css/bs-overides.min.css',
                        ext: '.min.css'
                    },
                    {
                        src: ['assets/css/forms.css'],
                        dest: 'assets/css/forms.min.css',
                        ext: '.min.css'
                    },
                    {
                        src: ['assets/css/style.css'],
                        dest: 'assets/css/style.min.css',
                        ext: '.min.css'
                    },
                    {
                        src: ['assets/builds/vendor-admin.css'],
                        dest: 'assets/builds/vendor-admin.css',
                        ext: '.css'
                    },
                ]
            }
        },
        postcss: {
            options: {
                map: false,
                processors: [
                    require('autoprefixer')
                ]
            },
            dist: {
                src: [
                    'assets/css/*.css',
                    '!assets/css/*.min.css',
                    'assets/themes/perfex/css/*.css',
                    '!assets/themes/perfex/css/*.min.css',
                ]
            }
        },
        concat: {
            moment: {
                src: [
                    'assets/plugins/moment/moment-with-locales.min.js',
                    'assets/plugins/moment-timezone/moment-timezone-with-data-2012-2022.min.js',
                ],
                dest: 'assets/builds/moment.min.js',
            },
            bootstrap_select: {
                src: [
                    'assets/plugins/bootstrap-select/js/bootstrap-select.min.js',
                    'assets/plugins/bootstrap-select-ajax/js/ajax-bootstrap-select.min.js',
                ],
                dest: 'assets/builds/bootstrap-select.min.js',
            },
            misc: {
                src: [
                    'assets/plugins/jquery/jquery.min.js',
                    'assets/plugins/jquery-ui/jquery-ui.min.js',
                    'assets/plugins/metisMenu/metisMenu.min.js',
                    'assets/plugins/readmore/readmore.min.js',
                    'assets/plugins/bootstrap/js/bootstrap.min.js',
                    'assets/plugins/tagsinput/js/tag-it.js',
                    'assets/plugins/jquery.are-you-sure/jquery.are-you-sure.js',
                    'assets/plugins/jquery.are-you-sure/ays-beforeunload-shim.js',
                    'assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js',
                    'assets/plugins/dropzone/min/dropzone.min.js',
                    'assets/plugins/Chart.js/Chart.min.js',
                    'assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js',
                    'assets/plugins/internal/hotkeys/hotkeys.js',
                    'assets/plugins/internal/desktop-notifications/notifications.js',
                    'assets/plugins/lightbox/js/lightbox.min.js',
                    'assets/plugins/accounting.js/accounting.min.js',
                    'assets/plugins/waypoint/jquery.waypoints.min.js',
                    'assets/plugins/internal/bootstrap-nav-tabs-scrollable/bootstrap-nav-tab-scrollable.js',
                ],
                dest: 'assets/builds/vendor-admin.js',
            },
            internal: {
                src: [
                    'assets/plugins/internal/validation/app-form-validation.min.js',
                    'assets/plugins/internal/highlight/highlight.js',
                    'assets/plugins/internal/google-picker/picker.min.js',
                    'assets/js/app.js',
                ],
                dest: 'assets/builds/common.js',
            },
            css_plugins: {
                src: [
                    'assets/plugins/bootstrap/css/bootstrap.min.css',
                    'assets/plugins/datatables/datatables.min.css',
                    'assets/plugins/jquery-ui/jquery-ui.min.css',
                    'assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css',
                    'assets/plugins/dropzone/min/dropzone.min.css',
                    'assets/plugins/bootstrap-select/css/bootstrap-select.min.css',
                    'assets/plugins/bootstrap-select-ajax/css/ajax-bootstrap-select.min.css',
                    'assets/plugins/tagsinput/css/jquery.tagit.css',
                    'assets/plugins/tagsinput/css/tagit.ui-zendesk.css',
                    'assets/plugins/datetimepicker/jquery.datetimepicker.min.css',
                    'assets/plugins/font-awesome/css/font-awesome.min.css',
                    'assets/plugins/lightbox/css/lightbox.min.css',
                    'assets/plugins/internal/bootstrap-nav-tabs-scrollable/bootstrap-nav-tab-scrollable.css',
                ],
                dest: 'assets/builds/vendor-admin.css',
            },
        },
        replace: {
            dist: {
                options: {
                    patterns: [{
                            match: /img\/bootstrap-colorpicker/g,
                            replacement: function() {
                                return 'plugins/bootstrap-colorpicker/img/bootstrap-colorpicker';
                            }
                        },
                        {
                            match: /..\/fonts\/fontawesome-/g,
                            replacement: function() {
                                return '../plugins/font-awesome/fonts/fontawesome-';
                            }
                        },
                        {
                            match: /images\/ui-/g,
                            replacement: function() {
                                return '../plugins/jquery-ui/images/ui-';
                            }
                        },
                        {
                            match: /..\/fonts\/glyphicons-/g,
                            replacement: function() {
                                return '../plugins/bootstrap/fonts/glyphicons-';
                            }
                        },
                        // lightbox
                        {
                            match: /..\/images\/close/g,
                            replacement: function() {
                                return '../plugins/lightbox/images/close';
                            }
                        },
                        {
                            match: /..\/images\/loading/g,
                            replacement: function() {
                                return '../plugins/lightbox/images/loading';
                            }
                        },
                        {
                            match: /..\/images\/next/g,
                            replacement: function() {
                                return '../plugins/lightbox/images/next';
                            }
                        },
                        {
                            match: /..\/images\/prev/g,
                            replacement: function() {
                                return '../plugins/lightbox/images/prev';
                            }
                        },

                    ]
                },
                files: [{
                    flatten: true,
                    expand: true,
                    src: ['assets/builds/vendor-admin.css'],
                    dest: 'assets/builds/'
                }]
            },
            maps_tinymce: {
                options: {
                    patterns: [{
                            match: /\/\*# sourceMappingURL=skin\.min\.css\.map \*\//,
                            replacement: function() {
                                return '';
                            }
                        },
                        {
                            match: /\/\*# sourceMappingURL=skin\.mobile\.min\.css\.map \*\//,
                            replacement: function() {
                                return '';
                            }
                        },
                    ]
                },
                files: [{
                    flatten: true,
                    expand: true,
                    src: ['assets/plugins/tinymce/skins/lightgray/skin.min.css', 'assets/plugins/tinymce/skins/lightgray/skin.mobile.min.css'],
                    dest: 'assets/plugins/tinymce/skins/lightgray/'
                }]
            },
            maps_datatables: {
                options: {
                    patterns: [{
                        match: /\/\/# sourceMappingURL=pdfmake\.min\.js\.map/,
                        replacement: function() {
                            return '';
                        }
                    }, ]
                },
                files: [{
                    flatten: true,
                    expand: true,
                    src: ['assets/plugins/datatables/datatables.min.js'],
                    dest: 'assets/plugins/datatables/'
                }]
            }
        },
        watch: {
            scripts: {
                files: ['assets/js/app.js'],
                tasks: ['build-assets'],
                options: {
                    spawn: false,
                },
            },
        },
        header: {
            dist: {
                options: {
                    text: '<%= version_header %>'
                },
                files: {
                    'assets/js/main.min.js': 'assets/js/main.min.js',
                    'assets/js/map.min.js': 'assets/js/map.min.js',
                    'assets/js/projects.min.js': 'assets/js/projects.min.js',
                    'assets/js/tickets.min.js': 'assets/js/tickets.min.js',
                    'assets/themes/perfex/js/clients.min.js': 'assets/themes/perfex/js/clients.min.js',
                    'assets/themes/perfex/js/global.min.js': 'assets/themes/perfex/js/global.min.js',
                }
            }
        },
    }

    if (grunt.file.isFile('./GruntPrivate.js')) {
        let privateConfig = require('./GruntPrivate.js')(grunt);
        gruntConfig = { ...gruntConfig, ...privateConfig.config };
    }

    grunt.initConfig(gruntConfig);

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-header');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-replace');

    grunt.registerTask('build-plugins', [
        'concat:moment',
        'concat:bootstrap_select',
        'concat:misc',
        'concat:internal',
        'concat:css_plugins',
    ]);

    grunt.registerTask('build-assets', [
        'build-plugins',
        'postcss:dist',
        'uglify:my_target',
        'replace:dist',
        'replace:maps_tinymce',
        'replace:maps_datatables',
        'cssmin',
        'header',
    ]);
};
