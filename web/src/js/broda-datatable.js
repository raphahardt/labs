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
  draw: noop,
  page: {
    len: function(){return 0},
    info: function(){return {}}
  }
};

function DataTableProvider() {

  var defaults = this.defaults = {
    // makes dataTables always returns the instancied api,
    // even if is not instancied yet
    retrieve: true,

    // specific options of dt directive
    rowTag: '<tr>',
    ajaxMethod: 'POST'
  };

  this.$get = [
    '$compile', '$http', '$interpolate', '$log',
    function($compile, $http, $interpolate, $log) {

      function escapeInterpolation(text) {
        text = "" + text; // forces a string

        var interpolateSymbols = [
          $interpolate.startSymbol(),
          $interpolate.endSymbol()
        ];

        if (angular.version.major >= 1 && angular.version.minor >= 3 || angular.version.major >= 2) {
          // angular 1.3>= has \{\{ syntax
          for(var i in interpolateSymbols) {
            text = text.replace(new RegExp('['+interpolateSymbols[i]+']', 'g'), '\\$&');
          }
        } else {
          // angular 1.3< does not, so is better strip off this values
          for(var i in interpolateSymbols) {
            text = text.replace(new RegExp(interpolateSymbols[i], 'g'), '');
          }
        }
        return text;
      }

      return {
        /**
         * Escapes the {{ }} symbols of values of datatables for solve
         * possibilly security issues.
         *
         * @param {String} text
         * @returns {String}
         */
        escapeInterpolation: function (text) {
          return escapeInterpolation(text);
        },
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
            case (value === '0'):
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

          optsCopy = $.extend(true, {}, defaults, opts, opts2);

          // only render the table, 'cuz other tools are handled by subdirectives
          // and by angular. This gives you total control of datatable's design
          optsCopy.dom = 't';

          // rows are created as needed
          optsCopy.deferRender = true;

          // for increase performance
          optsCopy.autoWidth = false;

          var method = optsCopy.ajaxMethod;
          // triggered when data is get from server
          var ajaxUrl = optsCopy.ajax;
          optsCopy.ajax = function(sendData, cb, opts) {
            $http({ method: method, url: ajaxUrl, data: sendData })
            .success(function(data) {
              cb(data);
            })
            .error(function(data, status, headers, config) {
              $scope.$emit('datatable:error', data, status, headers, config);
            });
          };

          // triggered when row is created (some columns could be hidden, so
          // compile is not safe here)
          optsCopy.createdRow = function(row, data, index) {
            var rowScope = $scope.$new(),
                $row = angular.element(row);

            for(var key in data) {
              rowScope[key] = escapeInterpolation(data[key]);
            }
            rowScope.$index = index;
            rowScope.$even = (index % 2 === 0);
            rowScope.$odd = (index % 2 !== 0);
            rowScope.$row = angular.copy(data); // for access to all row data

            // replace the <tr> for the custom tag
            $row.attrs(angular.element(optsCopy.rowTag).attrs());

            // store scope for the next event
            $row.data('scope', rowScope);

            $log.info('row '+index+' created');
          };

          // triggered when row is rendered (safe to compile)
          optsCopy.rowCallback = function(row) {
            var $row = angular.element(row),
                rowScope = $row.data('scope');

            if ($row.data('compiled') !== true) {
              // compiles once the row
              $row.replaceWith($compile($row)(rowScope));
              $row.data('compiled', true);
              $row.children().data('compiled', true);

              $log.info('row '+rowScope.$index+' compiled');
            } else {
              // if already compiled, compiles only what not have compiled yet
              $row.children().each(function() {
                var $td = $(this);
                // compiles only td's not compiled
                if ($td.data('compiled') !== true) {
                  $td.replaceWith($compile($td)(rowScope));
                  $td.data('compiled', true);

                  $log.info('compiled only td '+$td.index()+' from row '+rowScope.$index+' ');
                }
              });
            }
            rowScope.$evalAsync(); // schedule a digest if one is not already in progress
          };

          return optsCopy;
        }
      };
    }
  ];

}

var DataTableController = [
  '$scope', '$element', '$attrs', '$templateCache', '$datatable', '$parse', '$log',
  function ($scope, $element, $attrs, $templateCache, $datatable, $parse, $log) {

    var controller = this,
        // parse the options of directive
        settingsGet = $parse($attrs[DATATABLE_DIRECTIVE_NAME]),
        scopeSettings = settingsGet($scope),

        processingSetter = $attrs.processingIn ? $parse($attrs.processingIn).assign : false,

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
        _api.off('.dt');
        _api.destroy();
        $log.info('datatable detroyed');
      }
      _api = null;
    };

    function initApi() {
      if (_api === null) {
        var settings = $datatable.makeSettings($scope, scopeSettings, controller.options);
        _api = table.DataTable(settings);
        _api.on('init.dt', function() {
          _api.draw();
        });
        _api.on('draw.dt', function() {
          $scope.$broadcast('datatable:draw');
        });
        _api.on('processing.dt', function(event,opt,processing) {
          if (processingSetter) {
            console.info('processing in', processing);
            processingSetter($scope.$parent, processing);
          }
          $scope.$broadcast('datatable:processing', processing);
        });
        // method to recompile rows if visible of a column is changed
        // hack for avoid a draw() after a col vis toggle
        // ..is a hack, but still better than a full redraw
        _api._refreshVisible = function() {
          var rows = _api.rows().nodes().toArray();
          for (var i=0; i<rows.length; i++) {
            // redraw each known row
            settings.rowCallback(rows[i]);
          }
        };

        $log.info('datatable initialized', _api);
      }
    }
    // end privates
    //
    // controller options
    this.options = {
      columns: []
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
      col.visible = angular.isDefined(colAttrs.visible) ? $datatable.toBoolean(colAttrs.visible) : true;
      col.orderable = angular.isDefined(colAttrs.orderable) ? $datatable.toBoolean(colAttrs.orderable) : true;
      if (template) {
        col.render = function(data, type) {
          if (type === 'display')
            return $templateCache.get(template);
          return data;
        };
      } else {
        col.render = function(data, type) {
          if (type === 'display')
            return $datatable.escapeInterpolation(data);
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
            col.render = function(data, type) {
              if (type === 'display')
                return $datatable.escapeInterpolation(data);
              return data;
            };
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
        var v = $datatable.toBoolean(val);
        if (angular.isDefined(val) && col.visible !== v) {
          console.log('visible', colIndex, v);
          col.visible = v; // change the setting for next possibl reinit and for comparising
          controller.api().column( colIndex ).visible( v );//.draw(false);
          if (v) { // only refresh if a column is now visible (the inverse is unecessary)
            controller.api()._refreshVisible();
          }

          /*if (v) {
            var rows = controller.api().rows().nodes().toArray();
            for (var i=0; i<rows.length; i++) {
              var rowScope = angular.element(rows[i]).data('scope');
              if (rowScope) {
                window.setTimeout(function() {
                  rowScope.$apply();
                },0);
              }
            }
          }*/
          // must redraw :(
          // there is a bug if draw is not called: if table is initialized with columns invisible,
          // the cells are not compiled by angular, so a refresh is need
          // is lame, but is the only way
        }
      });

      colAttrs.$observe('orderable', function (val) {
        var v = $datatable.toBoolean(val);
        if (angular.isDefined(val) && col.orderable !== v) {
          console.log('changed orderable', v);
          col.orderable = v;
          needToReinitialize = true;
        }
      });

    };

    /**
     * Lazy load the Datatable API
     *
     * Used by subdirectives to set Datatables properties at runtime.
     *
     * @returns {fakeDtApi|DataTable.API}
     */
    this.api = function() {
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
      $log.warn('using fake api! '+columnsLeft+' columns left to define');
      return new fakeDtApi();
    };


    // getting header information
    table = $element.find('table').eq(0);

    // check the number of columns
    table.find('thead tr > *').each(function () {
      var col = {};
      if (angular.isDefined($(this).attr(''+DATATABLE_DIRECTIVE_NAME+'-column')) ||
          angular.isDefined($(this).data(''+DATATABLE_DIRECTIVE_NAME+'-column')) ||
          $(this).is('['+DATATABLE_DIRECTIVE_NAME+'-column]')) {
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

    $(document).on('ready', function() {
      initApi();
      $scope.$broadcast('datatable:init');
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
      controller: [
        '$scope', '$element',
        function(scope, element) {
          this.index = element.index();
        }
      ],
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
        '?^'+DATATABLE_DIRECTIVE_NAME+'Column',
        '?ngModel'
      ],
      link: function(scope, element, attrs, ctrls) {
        var dt = ctrls[0],
            forCol = ctrls[1] ? ctrls[1].index : (attrs[DATATABLE_DIRECTIVE_NAME+'Search'] || null),
            model = ctrls[2] ? attrs.ngModel : attrs.watch;

        scope.$watch(model, function (newVal, oldVal) {
          if (newVal !== oldVal) {
            console.info('searching for ', newVal, forCol);
            var api = dt.api();
            if (forCol) {
              api = api.column( parseInt(forCol) );
            }
            api.search( newVal || '' ).draw();
          }
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
            dtScope = scope.$parent,

            inGetter = $parse(attrs.in),
            inSetter = inGetter.assign,

            modelGetter = $parse(model),
            modelSetter = modelGetter.assign,

            pages = [];

        if (!inSetter) return;

        // define the page info for "in" attribute
        scope.$on('datatable:draw', function () {
          var info = dt.api().page.info();

          pages = [];
          for(var i=0; i<info.pages; i++) {
            pages.push({
              index: i,
              active: i === info.page, // convenience
              number: i+1 // convenience
            });
          }

          console.info('init pagination', pages, info);
          inSetter(dtScope, pages);
          if (modelGetter(dtScope) != info.page) {
            modelSetter(dtScope, info.page);
          }
        });

        scope.$watch(model, function (newVal, oldVal) {
          newVal = parseInt(newVal,10);
          oldVal = parseInt(oldVal,10);
          if (newVal !== oldVal) {
            console.info('pagination for ', newVal);
            dt.api().page( newVal || 0 ).draw(false); // false to not reset dt (see http://datatables.net/reference/api/page() )

            for(var i=0; i<pages; i++) {
              pages[i].active = (i == newVal);
            }
            inSetter(dtScope, pages);
          }
        });
      }
    };
  }
];

var DataTableLengthDirective = [
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
            dtScope = scope.$parent,

            modelGetter = $parse(model),
            modelSetter = modelGetter.assign;

        if (!modelSetter) return;

        scope.$on('datatable:init', function () {
          console.info('init length ', dt.api().page.len());
          modelSetter(dtScope, modelGetter(dtScope) || dt.api().page.len());
        });

        scope.$watch(model, function (newVal, oldVal) {
          if (newVal !== oldVal) {
            console.info('length for ', newVal);
            dt.api().page.len( newVal || 10 ).draw();
          }
        });
      }
    };
  }
];

var DataTableInfoDirective = [
  '$parse',
  function ($parse) {
    return {
      restrict: 'AC',
      require: '^'+DATATABLE_DIRECTIVE_NAME,
      link: function(scope, element, attrs, ctrl) {
        var dt = ctrl,
            dtScope = scope.$parent,

            inGetter = $parse(attrs.in),
            inSetter = inGetter.assign;

        if (!inSetter) return;

        // define the page info for "in" attribute
        scope.$on('datatable:draw', function () {
          var info = dt.api().page.info();

          console.info('init info', info);
          inSetter(dtScope, {
            start:     info.start,
            end:       info.end,
            length:    info.length,
            total:     info.recordsTotal,
            displayed: info.recordsDisplay
          });
        });

      }
    };
  }
];

var DataTableRowSelectableDirective = [
  '$parse', 'ArrayCache',
  function ($parse, $cache) {

    return {
      restrict: 'AC',
      link: function(scope, element, attrs) {
        var inGetter = $parse(attrs.in),
            inSetter = inGetter.assign,

            $id = element.data('id') || element.attr('id') || element.html(),
            selected = $cache('selected-rows').setDefault(inGetter(scope));

        if (!inSetter) return;

        inSetter(scope, selected.flat());

        if (selected.get($id) !== false) {
          element.addClass('selected');
        }

        element.on('click.dtselectable', function(e) {
          if (e.isDefaultPrevented()) {
            return false;
          }
          if (selected.get($id) !== false) {
            element.removeClass('selected');
            selected.remove($id);
          } else {
            element.addClass('selected');
            selected.put($id);
          }
          scope.$apply();
        });

        scope.$on('$destroy', function() {
          element.off('.dtselectable');
        });

      }
    };
  }
];

angular.module('broda.datatable', ['broda.arraycache'], [
  '$provide', '$compileProvider',
  function($provide, $compileProvider) {
    // provider
    $provide.provider('$datatable', DataTableProvider);

    // directives
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME, DataTableDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Column', DataTableColumnDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Search', DataTableSearchDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Pagination', DataTablePaginationDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Length', DataTableLengthDirective);
    $compileProvider.directive(DATATABLE_DIRECTIVE_NAME+'Info', DataTableInfoDirective);
    $compileProvider.directive('rowSelectable', DataTableRowSelectableDirective);
  }
]);

})(window.jQuery, window, window.angular, window.document);