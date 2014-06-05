(function($, window, angular, document, undefined) {

//'use strict';

// http://stackoverflow.com/a/17889439
$.fn.attrs = function(attrs) {
  var t = $(this);
  if (attrs) {
    // Set attributes
    t.each(function(i, e) {
      var j = $(e);
      for (var attr in attrs) {
        j.attr(attr, attrs[attr]);
      }
      ;
    });
    return t;
  } else {
    // Get attributes
    var a = {},
        r = t.get(0);
    if (r) {
      r = r.attributes;
      for (var i in r) {
        var p = r[i];
        if (typeof p.nodeValue !== 'undefined')
          a[p.nodeName] = p.nodeValue;
      }
    }
    return a;
  }
};

var MoneyDirective = [
  function() {
    return {
      restrict: 'EA',
      require: 'ngModel',
      transclude: 'element',
      link: function(scope, elm, attrs, ctrl) {
        var $wrapper = angular.element('<div class="form-control">'),
            $elem = angular.element('<input type="text"/>'),
            $display = angular.element('<div>'),
            currency = $filter('currency');

        $elem.css({
          'text-indent': -999999,
          'transform': 'scale(2)',
          'width': '100%',
          'opacity': 0
        });
        $wrapper.css({
          'position': 'relative',
          'overflow': 'hidden'
        });
        $display.css({
          'position': 'absolute',
          'click-events': 'none',
          'top': 0,
          'left': 0,
          'width': '100%',
          'text-align': 'right',
          'padding': (($wrapper.outerHeight() - $wrapper.height()) / 2) + 'px 8px'
        });
        $display.on('click.money', function() {
          $elem.focus();
        });

        $wrapper.on('focusin focusout', function(e) {
          if (e.type === 'focusin') {
            // focus in
            $display.css('borderRight', '1px solid');

          } else {
            // focus out
            $display.css('borderRight', '0');
          }
        });

        // view -> model
        $elem.on('input.money', function() {
          scope.$apply(function() {
            update();
          });
        });

        $elem.on('keydown.money', function(e) {

          // força ponteiro sempre no final
          setCaretPosition(this, this.value.length);

          var special = {
            0: '', 10: '', 13: 'enter', 8: 'backspace', 46: 'del', 36: 'home',
            33: 'pageup', 34: 'pagedown', 35: 'end', 9: 'tab',
            37: 'left', 38: 'up', 39: 'right', 40: 'down',
            116: 'f5'
          };
          //console.log(e.which);
          if (!special[e.which]) {
            if (e.which >= 96 && e.which <= 105) {
              e.which -= 48;
            }
            if (/[^0-9-]/.test(String.fromCharCode(e.which)))
              return false;
          }
        });

        // model -> view
        ctrl.$render = function() {
          //elm.html(ctrl.$viewValue);
          var value = (parseFloat(ctrl.$viewValue || 0) || 0) * 100;
          $elem.val(value);
        };

        // load init value from DOM
        elm.after($wrapper);
        $wrapper.append($elem).append($display);
        update();

        scope.$on('$destroy', function() {
          $elem.off('.money');
          $display.off('.money');
        });

        function update() {
          var value = (parseFloat($elem.val() || 0) || 0) / 100;
          ctrl.$setViewValue(value);
          $display.text(currency(value, 'R$ '));
        }

        function setCaretPosition(input, pos) {
          if (input.offsetWidth === 0 || input.offsetHeight === 0) {
            return; // Input's hidden
          }
          if (input.setSelectionRange) {
            input.focus();
            input.setSelectionRange(pos, pos);
          }
          else if (input.createTextRange) {
            // Curse you IE
            var range = input.createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
          }
        }

      }
    };
  }
];

angular.module('broda.money', [], [
  '$compileProvider',
  function($compileProvider) {

    // directives
    $compileProvider.directive('money', MoneyDirective);

  }
]);

})(window.jQuery, window, window.angular, window.document);