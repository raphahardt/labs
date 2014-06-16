(function($, window, angular, document, undefined) {

//'use strict';

var WebStorage = window.localStorage || {

  setItem: function(key, val) {
    this[key] = val;
  },

  getItem: function(key) {
    return this[key];
  },

  removeItem: function(key) {
    this[key] = undefined;
    delete this[key];
  }

};

function PrefixedWebStorage(prefix, ttl) {
  this.separator = '.';
  this.prefix = (prefix || 'broda')+this.separator;
  this.ttl = ttl || 86400;
  this.storage = WebStorage;
}
PrefixedWebStorage.prototype = {
  constructor: PrefixedWebStorage,

  _prefixed: function(key, postfix) {
    var s = this.prefix + key;
    if (postfix) {
      s += '' + this.separator + postfix;
    }
    return s;
  },

  _isExpired: function(key) {
    var expires = this.storage.getItem(this._prefixed(key, 'ttl'));
    if (!expires || expires > (new Date).getTime()) {
      return true;
    }
    return false;
  },

  _setExpires: function(key) {
    this.storage.setItem(this._prefixed(key, 'ttl'), (new Date).getTime() + this.ttl);
  },

  setItem: function(key, val) {
    this.storage.setItem(this._prefixed(key), val);
    if (this._isExpired(key)) {
      this._setExpires(key);
    }
  },

  getItem: function(key) {
    if (this._isExpired(key)) {
      this.storage.removeItem(this._prefixed(key, 'ttl'));
    }
    return this.storage.getItem(this._prefixed(key)) || undefined;
  },

  removeItem: function(key) {
    this.storage.removeItem(this._prefixed(key));
    this.storage.removeItem(this._prefixed(key, 'ttl'));
  }
};

function Cache(prefix, ttl) {
  this.storage = new PrefixedWebStorage(prefix, ttl);
  this.collection = this.storage.getItem('collection') || [];
}
Cache.prototype = {
  constructor: Cache,

  put: function(id) {
    if (this.get(id) === false) {
      this.collection.push(id);
    }
    return this;
  },

  get: function(id) {
    for(var i=0;i<this.collection.length;i++) {
      if (this.collection[i] === id) {
        return i;
      }
    }
    return false;
  },

  remove: function(id) {
    var i=this.get(id);
    if (i !== false) {
      this.collection.splice(i, 1);
    }
    return this;
  },

  flat: function() {
    return this.collection;
  },

  setAll: function(collection) {

  }
};

function CacheProvider() {

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

angular.module('broda.datatable', [], [
  '$provide',
  function($provide) {
    // provider
    $provide.provider('$cache', CacheProvider);
  }
]);

})(window.jQuery, window, window.angular, window.document);