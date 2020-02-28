<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

if (!window.fbControls) window.fbControls = new Array();

window.fbControls.push(function (controlClass) {

  var controlInputTypeDatetime = function (_controlClass) {
    _inherits(controlInputTypeDatetime, _controlClass);

    function controlInputTypeDatetime() {
      _classCallCheck(this, controlInputTypeDatetime);

      return _possibleConstructorReturn(this, (controlInputTypeDatetime.__proto__ || Object.getPrototypeOf(controlInputTypeDatetime)).apply(this, arguments));
    }

    _createClass(controlInputTypeDatetime, [{
      key: 'configure',
      value: function configure() {}

      /**
       * build a text DOM element, supporting other jquery text form-control's
       * @return DOM Element to be injected into the form.
       */

    }, {
      key: 'build',
      value: function build() {
        return this.markup('input', null, this.config);
      }
    }, {
      key: 'onRender',
      value: function onRender() {
        var value = this.config.value || '';
        $('#' + this.config.name).val(value);
      }
    }]);

    return controlInputTypeDatetime;
  }(controlClass);

  // register this control for the following types & text subtypes


  controlClass.register('datetime-local', controlInputTypeDatetime);
  return controlInputTypeDatetime;
});

var fbOptions = {
    dataType: 'json',
      stickyControls: {
        enable: false,
      }
};


if (formData && formData.length) {
    fbOptions.formData = formData;
}

fbOptions.disabledActionButtons = [
    'data',
    'clear'
];

fbOptions.disableFields = [
    'autocomplete',
    'button',
    'checkbox',
    'checkbox-group',
    'date',
    'hidden',
    'number',
    'radio-group',
    'select',
    'text',
    'textarea',
    'datetime-local'
];

fbOptions.controlPosition = 'left';

fbOptions.controlOrder = [
    'header',
    'paragraph',
    'file',
];

fbOptions.inputSets = [];

var db_fields = <?php echo json_encode($db_fields); ?>;
var cfields = <?php echo json_encode($cfields); ?>;

$.each(db_fields, function(i, f) {
    fbOptions.inputSets.push(f);
});

if (cfields && cfields.length) {
    $.each(cfields, function(i, f) {
        fbOptions.inputSets.push(f);
    });
}

fbOptions.typeUserEvents = {
    'text': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'input');
        },
    },
    'number': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'input');
        },
    },
    'email': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'input');
        },
    },
    'color': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'input');
        },
    },
    'date': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'input');
        },
    },
    'datetime-local': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'datetime-local');
        },
    },
    'select': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'select');
        },
    },
    'file': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'file');
            // set file upload field name to be always file-input
            $(fId).find('.name-wrap .input-wrap input').val('file-input')
            // Used in delete
            setTimeout(function(){
                $(fId).find('.fb-file input[type="file"]').attr('name','file-input')
            },500);
        },
    },
    'textarea': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'textarea');
        },
    },
    'checkbox-group': {
        onadd: function(fId) {
            do_form_field_restrictions(fId, 'checkbox-group');
        },
    },
}
$(function() {

    $('body').on('click', '.del-button', function() {

        var _field = $(this).parents('li.form-field');

        var _preview_name;
        var s = $('.cb-wrap .ui-sortable');
        if (_field.find('.prev-holder input').length > 0) {
            _preview_name = _field.find('.prev-holder input').attr('name');
        } else if (_field.find('.prev-holder textarea').length > 0) {
            _preview_name = _field.find('.prev-holder textarea').attr('name');
        } else if (_field.find('.prev-holder select').length > 0) {
            _preview_name = _field.find('.prev-holder select').attr('name');
        }

        var pos = _preview_name.lastIndexOf('-');
        _preview_name = _preview_name.substr(0, pos);
        if (_preview_name != 'file-input') {
            $('li[data-type="' + _preview_name + '"]').removeClass('disabled')
        } else {
            setTimeout(function() {
                s.find('li').eq(2).removeClass('disabled');
            }, 50);
        }
        setTimeout(function() {
            s.sortable({ cancel: '.disabled' });
            s.sortable('refresh');
        }, 80);
    });

    $('body').on('blur', '.form-field:not([type="header"],[type="paragraph"],[type="checkbox-group"]) input[name="className"]',
        function() {
        var className = $(this).val();
        if (className.indexOf('form-control') == -1) {
            className = className.trim();
            className += ' form-control';
            className = className.trim();
            $(this).val(className);
        }
    });

    $('body').on('focus', '.name-wrap input', function() {
        $(this).blur();
    });

});

function do_form_field_restrictions(fId, type) {
    var _field = $(fId);

    var _preview_name;
    var s = $('.cb-wrap .ui-sortable');

    if (type == 'checkbox-group') {
        _preview_name = _field.find('input[type="checkbox"]').eq(0).attr('name');
    } else if (type == 'file') {
        setTimeout(function() {
            s.find('li').eq(2).addClass('disabled');
        }, 50);
    } else {
        var check = _field.find('[type="'+type+'"]');
        if(check.length == 0) {
            check = _field.find(type);
        }
        _preview_name = check.attr('name');
    }

    if(type != 'file') {
        var pos = _preview_name.lastIndexOf('-');
        _preview_name = _preview_name.substr(0, pos);
        $('[data-type="' + _preview_name + '"]:not(.form-field)').addClass('disabled');
    }

    $('.frmb-control li[type="'+_preview_name+'"]').removeClass('text-danger');

    if(typeof(mustRequiredFields) != 'undefined' && $.inArray(_preview_name,mustRequiredFields) != -1){
        _field.find('.required-wrap input[type="checkbox"]').prop('disabled',true);
    }

    setTimeout(function() {
        s.sortable({ cancel: '.disabled' });
        s.sortable('refresh');
    }, 80);
}

</script>
