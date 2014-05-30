(function($, window, angular, document, undefined) {

/**
 *
 * @type window.Bloodhound
 */
var Bloodhound = window.Bloodhound;

/**
 *
 * @returns {$typeaheadProvider}
 */
function $typeaheadProvider() {

  var defaults = this.defaults = {
    highlight: true,
    hint: true,
    minLength: 1
  };

  var datasetDefaults = this.datasetDefaults = {
    dupDetector: function(remoteMatch, localMatch) {
      return angular.equals(remoteMatch, localMatch);
    }
  };


  /**
   *
   * @type {Bloodhound}
   */
  var engine;
  /**
   *
   * @type $.Deferred
   */
  var enginePromise;

  this.$get = [
    '$rootScope', '$compile', '$timeout',
    function($rootScope, $compile) {

      // privates
      function compile(html) {
        var link = $compile(html);
        return function(context) {
          var scope = $rootScope.$new(true), // scope temporario para o $compile
              compiled,
              wrap;
          for (var key in context) {
            if (context.hasOwnProperty(key)) {
              scope[key] = context[key];
            }
          }
          compiled = link(scope);
          scope.$digest();
          scope.$destroy(); // não preciso mais do scope

          wrap = $(document.createElement('div'));
          wrap.append(compiled.clone());
          return wrap.html();
        };
      }
      ;

      function normalizeDatasets(datasets) {
        var newDatasets = [];
        if (!angular.isArray(datasets)) {
          newDatasets.push(datasets);
        } else {
          newDatasets = datasets;
        }
        return newDatasets;
      }

      function normalizeTemplates(dataset) {
        var templates = {};
        if (angular.isDefined(dataset.templates)) {
          angular.forEach(dataset.templates, function(value, key) {
            if ('string' === typeof value) {
              templates[key] = compile(value);
            }
          });
        }
        return templates;
      }

      function normalizeUrl(url) {
        if (angular.isString(url)) {
          return {url: url};
        }
        return url;
      }

      function listenAjaxEvents(sourceOpts) {
        if (!sourceOpts) return; // no prefetch/remote defined, ignore

        if (!angular.isDefined(sourceOpts.ajax)) {
          sourceOpts.ajax = {};
        }
        angular.forEach([
          'beforeSend',
          'success',
          'error',
          'complete'
        ], function(funcName) {
          if (angular.isFunction(sourceOpts.ajax[funcName])) {
            var fn = sourceOpts.ajax[funcName];
            sourceOpts.ajax[funcName] = function() {
              console.info('TYPEAHEAD:AJAX:' + funcName + ' (override)');
              fn.apply(fn, arguments);
              $rootScope.$apply();
            };
          } else {
            sourceOpts.ajax[funcName] = function() {
              console.info('TYPEAHEAD:AJAX:' + funcName);
            };
          }
        });
      }

      return {
        makeSettings: function(opts) {
          var optsCopy = angular.copy(opts),
              tmpSource;

          // normalize the datasets
          optsCopy.datasets = normalizeDatasets(optsCopy.datasets);

          angular.forEach(optsCopy.datasets, function(dataset, i) {
            // normalize and compile the string templates
            if (angular.isDefined(dataset.templates)) {
              optsCopy.datasets[i].templates = normalizeTemplates(dataset);
            }

            // normalize source (it gonna be the ttAdapter from engine)
            tmpSource = optsCopy.datasets[i].source;
            if (!angular.isDefined(tmpSource)) {
              throw new Error('source key from dataset#' + i + ' is undefined! must be a settings object for Bloodhound');
            }
            tmpSource.prefetch = normalizeUrl(dataset.source.prefetch);
            tmpSource.remote = normalizeUrl(dataset.source.remote);
            tmpSource = $.extend(true, {}, datasetDefaults, tmpSource);

            // listen to ajax events
            listenAjaxEvents(tmpSource.prefetch);
            listenAjaxEvents(tmpSource.remote);

            engine = new Bloodhound(tmpSource);
            enginePromise = engine.initialize();
            optsCopy.datasets[i].source = engine.ttAdapter();

            enginePromise
                .done(function(data, textStatus, xhr) {
                  console.info('TYPEAHEAD:AJAX:DONE');
                  //$scope.$emit('typeahead:ajax:done', xhr, data);
                })
                .fail(function(xhr, textStatus, errorThrown) {
                  console.info('TYPEAHEAD:AJAX:FAIL');
                  //$scope.$emit('typeahead:ajax:fail', xhr, errorThrown);
                })
                .always(function() {
                  console.info('TYPEAHEAD:AJAX:ALWAYS');
                  //$scope.$emit('typeahead:ajax:always');
                });

          });

          // normalize the options
          optsCopy.options = $.extend(true, {}, defaults, optsCopy.options);

          return optsCopy;
        }

      };
    }
  ];
}

angular.module('broda.typeahead', [], [
  '$provide', '$compileProvider',
  function($provide, $compileProvider) {
    // provider
    $provide.provider('$typeahead', $typeaheadProvider);

    // directive
    $compileProvider.directive('typeahead', [
      '$typeahead',
      function($typeahead) {
        return {
          restrict: 'A',
          scope: {
            options: '=typeahead',
            model: '='
          },
          link: function(scope, element, attrs) {
            var settings = $typeahead.makeSettings(scope.options);

            // watch options changes
            scope.$watch(attrs.typeahead+'.options', function(newOpts, oldOpts) {
              if (oldOpts !== newOpts) {
                settings = $typeahead.makeSettings(scope.options);

                element.typeahead('destroy');
                element.off('typeahead:selected typeahead:autocompleted input');
                init();
              }
            }, true);

            function updateModel(value) {
              scope.$apply(function() {
                scope.model = value;
              });
            }

            function init() {
              element.typeahead(settings.options, settings.datasets);
              element
                  .on('typeahead:selected typeahead:autocompleted', function(event, suggestion, datasetName) {
                    updateModel(suggestion);
                    console.info('TYPEAHEAD:SELECTED', suggestion);
                  })
                  .on('input', function(event) {
                    var value = element.typeahead('val');
                    updateModel(value);
                    console.info('TYPEAHEAD:INPUT', value);
                  });
            }

            init();

          }
        };
      }
    ]);
  }
]);

})(window.jQuery, window, window.angular, window.document);