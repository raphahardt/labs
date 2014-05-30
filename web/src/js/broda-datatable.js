(function($, window, angular, document, undefined) {

var DATATABLE_DIRECTIVE_NAME = 'datatable';

function DataTableProvider() {

  var defaults = this.defaults = {};

  this.$get = [
    function() {
      return {
        makeSettings: function (opts) {
          var optsCopy = angular.copy(opts);

          // os outros controles serão tratados separadamente, então
          // só a tabela interessa aqui
          optsCopy.dom = 't';

          return optsCopy;
        }
      };
    }
  ];

}

var DataTableController = [
  '$scope', '$element', '$attrs', '$templateCache', '$datatable', '$parse',
  function ($scope, $element, $attrs, $templateCache, $datatable, $parse) {
    var settingsGet = $parse($attrs[DATATABLE_DIRECTIVE_NAME]),
        settings = $datatable.makeSettings(settingsGet($scope)),

        dt = $($element[0]).DataTable(settings);

    this.api = dt;
  }
];

var DataTableDirective = [
  function () {
    return {
      restrict: 'AC',
      scope: true,
      controller: DataTableController
    };
  }
];

var DataTableSearchDirective = [
  function () {
    return {
      restrict: 'AC',
      require: [
        '^'+DATATABLE_DIRECTIVE_NAME,
        '?ngModel'
      ],
      scope: {
        watch: '='
      },
      controller: DataTableController,
      link: function(scope, element, attrs, ctrls) {
        var dt = ctrls[0],
            model = ctrls[1] ? attrs.ngModel : attrs.watch;

        scope.$watch(model, function (newVal, oldVal) {
          dt.api.search( newVal ).draw();
        });
      }
    };
  }
];

angular.module('broda.datatable', [], [
  '$provide', '$compileProvider',
  function($provide, $compileProvider) {
    // provider
    $provide.provider('$datatable', DataTableProvider);

    // directives
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME, DataTableDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Search', DataTableSearchDirective);
  }
]);

})(window.jQuery, window, window.angular, window.document);