(function($, window, angular, document, undefined) {

//'use strict';

var MoneyDirective = [
  '$filter',
  function($filter) {
    return {
      restrict: 'A',
      require: 'ngModel',
      priority: -1000,
      terminal: true,
      //transclude: 'element',
      link: function(scope, elm, attrs, ctrl) {

        //var elm = angular.element('<input/>').insertAfter(elmComment);

        function format(val) {
          return "R" + (val || "");
        };

        function unformat(val) {
          return (val || "").substr(1);
        };

        // view -> model
        elm.on('input', function(e) {
          console.log('inputou', elm.val());
          elm.val(format(elm.val()));
          scope.$apply(function() {
            ctrl.$setViewValue(elm.val());
          });
        });

        // model -> view
        ctrl.$render = function() {
          console.info('renderizou',ctrl.$viewValue);
          elm.val(ctrl.$viewValue);
        };

        ctrl.$formatters.unshift(function(fromModel){
          console.info('formattou', fromModel);
          return fromModel;
        });

        ctrl.$parsers.push(function(fromView){
          console.info('parsou', fromView);
          return fromView;
        });

        // load init value from DOM
        ctrl.$setViewValue(elm.val());

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