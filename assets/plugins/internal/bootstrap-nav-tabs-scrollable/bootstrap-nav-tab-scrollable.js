// Add Horizontal Tabs to jquery
// Modified version

(function($) {


    (function($, sr) {

        // debouncing function from John Hann
        // http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
        var debounce = function(func, threshold, execAsap) {
            var timeout;

            return function debounced() {
                var obj = this,
                    args = arguments;

                function delayed() {
                    if (!execAsap)
                        func.apply(obj, args);
                    timeout = null;
                };

                if (timeout)
                    clearTimeout(timeout);
                else if (execAsap)
                    func.apply(obj, args);

                timeout = setTimeout(delayed, threshold || 100);
            };
        }
        // smartresize
        jQuery.fn[sr] = function(fn) { return fn ? this.on('resize', debounce(fn)) : this.trigger(sr); };

    })(jQuery, 'smartresize');

    // http://upshots.org/javascript/jquery-test-if-element-is-in-viewport-visible-on-screen#h-o
    $.fn.isOnScreen = function(x, y) {

        if (x == null || typeof x == 'undefined') x = 1;
        if (y == null || typeof y == 'undefined') y = 1;

        var win = $(window);

        var viewport = {
            top: win.scrollTop(),
            left: win.scrollLeft()
        };
        viewport.right = viewport.left + win.width();
        viewport.bottom = viewport.top + win.height();

        var height = this.outerHeight();
        var width = this.outerWidth();

        if (!width || !height) {
            return false;
        }

        var bounds = this.offset();
        bounds.right = bounds.left + width;
        bounds.bottom = bounds.top + height;

        var visible = (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));

        if (!visible) {
            return false;
        }

        var deltas = {
            top: Math.min(1, (bounds.bottom - viewport.top) / height),
            bottom: Math.min(1, (viewport.bottom - bounds.top) / height),
            left: Math.min(1, (bounds.right - viewport.left) / width),
            right: Math.min(1, (viewport.right - bounds.left) / width)
        };

        return (deltas.left * deltas.right) >= x && (deltas.top * deltas.bottom) >= y;
    };

    $.fn.horizontalTabs = function() {

        return this.each(function() {
            var self = this;
            var $elem = $(this);
            var methods = {};

            methods.getArrowsTotalWidth = function() {
                return ($elem.find('.arrow-left').outerWidth() + $elem.find('.arrow-right').outerWidth());
            };

            methods.adjustScroll = function() {
                widthOfList = 0;
                var $items = $elem.find('.nav-tabs-horizontal li:not(.nav-tabs-submenu-child, nav-tabs-submenu-parent)');
                var $active;
                $items.each(function(index, item) {
                    widthOfList += $(item).outerWidth();
                    if ($(item).hasClass("active") && widthOfList > $elem.width()) {
                        $active = $(item);
                    }
                    if ($(item).is(':last-child')) {
                        $lastItem = $(item);
                    }
                });

                widthAvailale = $elem.width();

                if (widthOfList > widthAvailale) {
                    $elem.find('.scroller').show();
                    methods.updateArrowStyle(currentPos);
                    widthOfReducedList = $elem.find('.nav-tabs-horizontal').outerWidth();
                } else {
                    $elem.find('.scroller').hide();
                }
                if ($active) {
                    setTimeout(function() {
                        currentPos = $active.position().left - methods.getArrowsTotalWidth()
                        $elem.find('.nav-tabs-horizontal').animate({
                            scrollLeft: currentPos
                        }, 100);
                    }, 150);
                }
            };

            methods.scrollLeft = function() {
                $elem.find('.nav-tabs-horizontal').animate({
                    scrollLeft: currentPos - widthOfReducedList
                }, 500);

                if (currentPos - widthOfReducedList > 0) {
                    currentPos -= widthOfReducedList;
                } else {
                    currentPos = 0;
                }
            };

            methods.scrollRight = function() {

                $elem.find('.nav-tabs-horizontal').animate({
                    scrollLeft: currentPos + widthOfReducedList
                }, 500);

                if ((currentPos + widthOfReducedList) < (widthOfList - widthOfReducedList)) {
                    currentPos += widthOfReducedList;
                } else {
                    currentPos = (widthOfList - widthOfReducedList);
                }
            };

            methods.manualScroll = function() {
                currentPos = $elem.find('.nav-tabs-horizontal').scrollLeft();

                methods.updateArrowStyle(currentPos);
            };

            methods.updateArrowStyle = function(position) {

                waypointlastItem = new Waypoint({
                    element: $lastItem[0],
                    context: $elem[0],
                    horizontal: true,
                    offset: 'right-in-view',
                    handler: function(direction) {
                        delay(function() {
                            if (direction == 'right' && $lastItem.isOnScreen()) {
                                $elem.find('.arrow-right').addClass('disabled');
                            } else {
                                $elem.find('.arrow-right').removeClass('disabled');
                            }
                        }, 200);
                    }
                });

                if (position <= 0) {
                    $elem.find('.arrow-left').addClass('disabled');
                    setTimeout(function() {
                        $elem.find('.arrow-right').removeClass('disabled');
                    }, 100);
                } else {
                    $elem.find('.arrow-left').removeClass('disabled');
                };
            };

            methods.clearMenuItem = function(menu) {
                $('[data-sub-menu-id="' + menu.attr('data-menu-id') + '"]').remove();
                menu.removeAttr('data-menu-id');
            }

            methods.genUniqueID = function() {
                return Math.random().toString(36).substr(2, 9);
            }

            // Variable creation
            var $lastItem,
                waypointlastItem,
                $subMenuHref = $elem.find('li.nav-tabs-submenu-parent > a'),
                widthOfReducedList = $elem.find('.nav-tabs-horizontal').outerWidth(),
                widthOfList = 0,
                currentPos = 0;

            $(window).smartresize(function(){
                 methods.adjustScroll();
            });

            // Whenever we click a menu item that has a submenu
            if ($subMenuHref.length > 0) {
                $subMenuHref.on('click', function(e) {
                    e.preventDefault();
                    var $menuItem = $(this);

                    if ($menuItem.attr('data-menu-id')) {
                        methods.clearMenuItem($menuItem);
                        return false;
                    }
                    var newID = methods.genUniqueID();
                    $menuItem.attr('data-menu-id', newID);
                    var $submenuWrapper = $menuItem.parents('li.nav-tabs-submenu-parent').find('.tabs-submenu-wrapper');
                    var $clonedSubmenu = $submenuWrapper.clone();
                    // grab the menu item's position relative to its positioned parent
                    var menuItemOffset = $menuItem.offset();
                    // place the submenu in the correct position relevant to the menu item
                    $clonedSubmenu.find('ul').css({
                            top: menuItemOffset.top + $menuItem.outerHeight() - 5,
                            left: menuItemOffset.left,
                            display: 'block',
                            'border-top-left-radius': '0',
                            'border-top-right-radius': '0',
                        })
                        .attr('data-sub-menu-id', newID);
                    $clonedSubmenu.find('ul li.active:eq(0) > a').css({
                        'border-top-left-radius': '0',
                        'border-top-right-radius': '0',
                    });
                    $('body').append($clonedSubmenu.unwrap().html());
                    $('body').on('click', function(e) {
                        if (e.target != $menuItem[0]) {
                            methods.clearMenuItem($menuItem);
                        }
                    });
                });
            }
            $elem.find('.arrow-left').on('click.horizontalTabs', function() {
                if ($(this).hasClass('disabled')) {
                    return false;
                }
                methods.scrollLeft();
            });

            $elem.find('.arrow-right').on('click.horizontalTabs', function() {
                if ($(this).hasClass('disabled')) {
                    return false;
                }
                methods.scrollRight();
            });

            $elem.find('.nav-tabs-horizontal').scroll(function() {
                methods.manualScroll();
            });

            // Initial Call
            methods.adjustScroll();

            return this;
        });
    }

}(window.jQuery));
