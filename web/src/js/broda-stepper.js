(function($, window, angular, document, undefined) {

//'use strict';

var StepperDirective = [
  function() {
    return {
      require: 'ngModel',
      link: function(scope, element, attr, ctrl) {

        ctrl.$parsers.push(function(value) {
          var empty = ctrl.$isEmpty(value);
          if (empty || NUMBER_REGEXP.test(value)) {
            ctrl.$setValidity('number', true);
            return value === '' ? null : (empty ? value : parseFloat(value));
          } else {
            ctrl.$setValidity('number', false);
            return undefined;
          }
        });

        ctrl.$formatters.push(function(value) {
          return ctrl.$isEmpty(value) ? '' : '' + value;
        });

        ctrl.$render = function() {
          element.val(ctrl.$isEmpty(ctrl.$viewValue) ? '' : ctrl.$viewValue);
        };

      }
    };
  }
];

angular.module('broda.stepper', [], [
  '$compileProvider',
  function($compileProvider) {

    // directives
    $compileProvider.directive('stepper', StepperDirective);

  }
]);

})(window.jQuery, window, window.angular, window.document);