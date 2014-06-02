(function($, window, angular, document, undefined) {

var DATATABLE_DIRECTIVE_NAME = 'datatable',
    noop = function () {return this};

/**
 * A fake DataTable API for subdirectives not raise errors when
 * datatable cannot be initialized at the moment.
 *
 * The datatable API is initialized only when all columns
 * are correctly defined (this is because columns options are only set in
 * constructor).
 *
 * @returns {fakeDtApi}
 */
function fakeDtApi() {};
fakeDtApi.prototype = {
  constructor: fakeDtApi,
  columns: noop,
  column: noop,
  row: noop,
  rows: noop,
  search: noop,
  draw: noop
};

function DataTableProvider() {

  var defaults = this.defaults = {
    // makes dataTables always returns the instancied api,
    // even if is not instancied yet
    retrieve: true
  };

  this.$get = [
    '$compile', '$http',
    function($compile, $http) {
      return {
        /**
         * Normalize a value to a boolean value.
         * Converts 'true' and 'false' strings properly.
         *
         * @param {*} value
         * @returns {Boolean}
         */
        toBoolean: function (value) {
          switch (true) {
            case (value === 'true'):
            case (value === 'T'):
              return true;
            case (value === 'false'):
            case (value === 'F'):
              return false;
            default:
              return !!value;
          }
        },
        /**
         * Normalize the options object
         *
         * @param {Object...} opts
         * @returns {Object}
         */
        makeSettings: function ($scope, opts, opts2) {
          var optsCopy = {};

          optsCopy = $.extend(true, {}, opts, opts2);

          // only render the table, 'cuz other tools are handled by subdirectives
          // and by angular. This gives you total control of datatable's design
          optsCopy.dom = 't';

          // rows are created as needed
          optsCopy.deferRender = true;

          // triggered when data is get from server
          var ajaxUrl = optsCopy.ajax;
          optsCopy.ajax = function(sendData, cb, opts) {
            $http.post(ajaxUrl, sendData)
            .success(function(data) {
              console.warn('ajax done');
              cb(data);
            })
            .error(function(data, status, headers, config) {
              alert('erro!');
            });
          };

          // triggered when row is created (some columns could be hidden, so
          // compile is not safe here)
          optsCopy.createdRow = function(row, data, index) {
            var rowScope = $scope.$new(true),
                $row = angular.element(row);

            for(var key in data) {
              rowScope[key] = data[key];
            }
            rowScope.$index = index;
            rowScope.$even = (index % 2 === 0);
            rowScope.$odd = (index % 2 !== 0);
            rowScope.$row = angular.copy(data); // for access to all row data

            console.warn('createdRow');

            $row.data('scope', rowScope);

          };

          // triggered when row is rendered (safe to compile)
          optsCopy.rowCallback = function(row) {
            var $row = angular.element(row),
                rowScope = $row.data('scope');

            $row.replaceWith($compile($row)(rowScope));

            console.warn('rowCallback', row);
          };

          optsCopy.drawCallback = function() {
            console.warn('drawCallback');
          };

          return optsCopy;
        }
      };
    }
  ];

}

var DataTableController = [
  '$scope', '$element', '$attrs', '$templateCache', '$datatable', '$parse', '$timeout',
  function ($scope, $element, $attrs, $templateCache, $datatable, $parse, $timeout) {

    var controller = this,
        // parse the options of directive
        settingsGet = $parse($attrs[DATATABLE_DIRECTIVE_NAME]),
        scopeSettings = settingsGet($scope),

        // table element
        // only the first table of directive will be handled
        table,
        // number of columns of the table
        columnsLeft = 0,
        // schedule a reinitialize when use the api. needed for column changes operations
        needToReinitialize = false,
        // the datatable API
        _api = null;

    // privates
    function destroyApi() {
      if (_api !== null) {
        _api.destroy();
        console.info('datatables detroyed');
      }
      _api = null;
    };

    function initApi() {
      if (_api === null) {
        var settings = $datatable.makeSettings($scope, scopeSettings, controller.options);
        _api = table.DataTable(settings);

        console.info('datatables initialized', _api);
      }
    }
    // end privates
    //
    // controller options
    this.options = {
      columns: [],
      ajax: 'ajax'
    };

    /*
     * Adds a column to be handled by datatable directive
     *
     * This is automatically called by datatableColumn directive
     *
     * @param {$compile.directive.Attributes} colAttrs $attrs of the column
     */
    this.addColumn = function(colIndex, colAttrs) {
      var col = controller.options.columns[colIndex],
          name = colAttrs.name,
          template = colAttrs.template;

      columnsLeft--;

      col.data = name;
      col.visible = true;
      if (template) {
        col.render = function(data, type) {
          if (type === 'display')
            return $templateCache.get(template);
          return data;
        };
      }

      colAttrs.$observe('name', function (val) {
        if (col.data !== val) {
          col.data = val;
          needToReinitialize = true;
        }
      });

      colAttrs.$observe('template', function (val) {
        if (template !== val) {
          if (!val) {
            delete col.render;
          } else {
            col.render = function(data, type) {
              if (type === 'display')
                return $templateCache.get(val);
              return data;
            };
          }
          needToReinitialize = true;
        }
        template = val;
      });

      colAttrs.$observe('visible', function (val) {
        console.log('visible', colIndex, val);
        controller.api().column( colIndex ).visible( $datatable.toBoolean(val) ).draw();
        // must draw :(
        // there is a bug if draw is not called: if table is initialized with columns invisible,
        // the cells are not compiled by angular, so a refresh is need
        // is lame, but is the only way
      });

    };

    /**
     * Lazy load the Datatable API
     *
     * Used by subdirectives to set Datatables properties at runtime.
     *
     * @returns {fakeDtApi|DataTable.API}
     */
    this.api = function () {
      if (needToReinitialize) {
        destroyApi();
        needToReinitialize = false;
      }
      // only initialize datatable when all required information about
      // columns are set
      if (columnsLeft === 0) {
        initApi();
        return _api;
      }
      // use fake api for nor raise errors in sub datatable directives
      console.warn('using fake api!');
      return new fakeDtApi();
    };


    // getting header information
    table = $element.find('table').eq(0);

    // check the number of columns
    table.find('thead tr > *').each(function () {
      var col = {};
      if (angular.isDefined($(this).attr('datatable-column')) ||
          angular.isDefined($(this).data('datatable-column')) ||
          $(this).is('[datatable-column]')) {
        columnsLeft++;
        controller.options.columns.push(col);
      } else {
        controller.options.columns.push({
          data: null,
          defaultContent: ''
        });
      }
    });

    $element.on('$destroy', function () {
      destroyApi();
    });

  }
];

var DataTableDirective = [
  '$compile',
  function ($compile) {
    return {
      restrict: 'AC',
      scope: true,
      controller: DataTableController
    };
  }
];

var DataTableColumnDirective = [
  function () {
    return {
      restrict: 'AC',
      require: '^'+DATATABLE_DIRECTIVE_NAME,
      link: function(scope, element, attrs, ctrl) {
        var dt = ctrl;

        // adds column to be handled by dt controller
        dt.addColumn(element.index(), attrs);
      }
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
      link: function(scope, element, attrs, ctrls) {
        var dt = ctrls[0],
            model = ctrls[1] ? attrs.ngModel : attrs.watch;

        scope.$watch(model, function (newVal, oldVal) {
          console.info('searching for ', newVal);
          dt.api().search( newVal || '' ).draw();
        });
      }
    };
  }
];

var DataTablePaginationDirective = [
  '$parse',
  function ($parse) {
    return {
      restrict: 'AC',
      require: [
        '^'+DATATABLE_DIRECTIVE_NAME,
        '?ngModel'
      ],
      link: function(scope, element, attrs, ctrls) {
        var dt = ctrls[0],
            model = ctrls[1] ? attrs.ngModel : attrs.watch,

            inGetter = $parse(attrs.in),
            inSetter = inGetter.assign;

        if (!inSetter) return;

        // define the page info for "in" attribute
        inSetter();

        scope.$watch(model, function (newVal, oldVal) {
          console.info('searching for ', newVal);
          dt.api().search( newVal || '' ).draw();
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
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Column', DataTableColumnDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Search', DataTableSearchDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Pagination', DataTablePaginationDirective);
  }
]);

})(window.jQuery, window, window.angular, window.document);