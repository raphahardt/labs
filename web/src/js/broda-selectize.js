(function($, window, angular, document, undefined) {

//'use strict';

function SelectizeProvider() {

  var defaults = this.defaults = {
    delimiter: ',',
    diacritics: true,
    create: false,
    createOnBlur: false,
    createFilter: null,
    highlight: true,
    persist: true,
    openOnFocus: true,
    hideSelected: true,
    loadThrottle: 400,
    preload: false,
    dropdownParent: null,
    addPrecedence: false,
    selectOnTab: false,
    options: [],
    valueField: 'value',
    optgroupValueField: 'value',
    labelField: 'text',
    optgroupLabelField: 'label',
    optgroupField: 'optgroup',
    load: null,
    score: null,
    render: {}
  };

  var datasetDefaults = this.datasetDefaults = {
    url: null,
    wildcard: '%QUERY',
    method: 'GET',
    ttl: 846000
  };

  this.$get = [
    '$compile', '$rootScope',
    function($compile, $rootScope) {

      return {

        makeSettings: function(opts) {
          var optsCopy = angular.copy(opts);

          // normalize templates
          angular.forEach([
            'option',
            'item',
            'option_create',
            'optgroup_header',
            'optgroup'
          ], function(val) {
            if (!optsCopy.render) {
              return;
            }
            var fn = optsCopy.render[val];
            if (angular.isString(fn)) {
              optsCopy.render[val] = (function(template) {
                var link = $compile(template);
                return function(context, escape) {
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
              }(fn));
            }
          });

          // normalize load
          if (optsCopy.load) {
            if (angular.isString(optsCopy.load)) {
              optsCopy.load = angular.extend(datasetDefaults, { url: optsCopy.load });
            }
            if (optsCopy.load.url) {
              var url = optsCopy.load.url,
                  wildcard = optsCopy.load.wildcard,
                  method = optsCopy.load.method || 'GET';
              optsCopy.load = function(query, cb) {
                if (!query.length) return cb();
                $.ajax({
                  url: url.replace(wildcard, encodeURIComponent(query)),
                  type: method,
                  error: function() {
                    cb();
                  },
                  success: function(res) {
                    cb(res.repositories.slice(0, 10));
                  }
                });
              };
            }
          }

          // normalize the options
          optsCopy = $.extend(true, {}, defaults, optsCopy);

          return optsCopy;
        }

      };

    }
  ];

}

var SelectizeDirective = [
  '$selectize',
  function($selectize) {
    return {
      require: 'ngModel',
      scope: {
        selectize: '='
      },
      link: function(scope, element, attr, ctrl) {

        var settings = $selectize.makeSettings(scope.selectize);
        var api;
        //console.info(settings);
		/*var update = function(e) {
          console.log('update', element.val());
          scope.$apply(function() {
            ctrl.$setViewValue(element.val());
          });
        };*/

		//$(this).on('change', update);

        ctrl.$formatters.push(function (fromModel) {
          console.warn('formatter', fromModel);
          //api.setValue(fromModel);
          return fromModel;
        });

        api = element.selectize(settings)[0].selectize;

        console.log('api', api);

        /*scope.$watch(attr.ngModel, function (val) {
          console.log('mudou para ', val);
          api.setValue(val);
        });*/


      }
    };
  }
];

angular.module('broda.selectize', [], [
  '$provide', '$compileProvider',
  function($provide, $compileProvider) {
    // provider
    $provide.provider('$selectize', SelectizeProvider);

    // directives
    $compileProvider.directive('selectize', SelectizeDirective);

  }
]);

})(window.jQuery, window, window.angular, window.document);