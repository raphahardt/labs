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

function StateWebStorage(prefix, ttl) {
  this.separator = '.';
  this.prefix = (prefix || 'broda')+this.separator;
  this.ttl = ttl || 86400;
  this.storage = WebStorage;
}
StateWebStorage.prototype = {
  constructor: StateWebStorage,

  _prefixed: function(key, postfix) {
    var s = this.prefix + key;
    if (postfix) {
      s += '' + this.separator + postfix;
    }
    return s;
  },

  _isExpired: function(key) {
    var expires = parseInt(this.storage.getItem(this._prefixed(key, 'ttl')), 10);
    if (!expires || expires < (new Date).getTime()) {
      return true;
    }
    return false;
  },

  _setExpires: function(key) {
    console.info('set expires', (new Date).getTime() + this.ttl);
    this.storage.setItem(this._prefixed(key, 'ttl'), (new Date).getTime() + this.ttl);
  },

  setItem: function(key, val) {
    this.storage.setItem(this._prefixed(key), JSON.stringify(val));
    if (this._isExpired(key)) {
      this._setExpires(key);
    }
    return this;
  },

  getItem: function(key) {
    if (this._isExpired(key)) {
      this.removeItem(key);
    }
    try {
      return JSON.parse(this.storage.getItem(this._prefixed(key)));
    } catch(e) {
      return undefined;
    }
  },

  removeItem: function(key) {
    this.storage.removeItem(this._prefixed(key));
    this.storage.removeItem(this._prefixed(key, 'ttl'));
    return this;
  }
};

function ArrayCache(prefix, ttl) {
  this.storage = new StateWebStorage('broda'+prefix, ttl);
  this.collection = [];
  //this._recover();
  //console.log(this.collection, typeof this.collection, Object.prototype.toString.call(this.collection));
}
ArrayCache.prototype = {
  constructor: ArrayCache,

  _recover: function() {
    this.collection = [];
    var cached = this.storage.getItem('collection');
    if (angular.isArray(cached)) {
      for(var i=0;i<cached.length;i++) {
        this.collection.push(cached[i]);
      }
    }
  },

  put: function(id) {
    if (this.get(id) === false) {
      this.collection.push(id);
      //this.storage.setItem('collection', this.collection);
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
      //this.storage.setItem('collection', this.collection);
    }
    return this;
  },

  flat: function() {
    return this.collection;
  },

  setAll: function(collection) {
    this.collection = collection;
    this.storage.setItem('collection', this.collection);
    return this;
  },

  setDefault: function(collection) {
    if (!this.storage.getItem('collection')) {
      this.storage.setItem('collection', collection || []);
    }
    //this._recover();
    return this;
  }
};

function ArrayCacheProvider() {

  var defaults = this.defaults = {
    ttl: 864000
  };

  this.$get = [
    function() {

      return function(prefix, ttl) {
        return new ArrayCache(prefix, ttl || defaults.ttl);
      };

    }
  ];

}

angular.module('broda.arraycache', [], [
  '$provide',
  function($provide) {
    // provider
    $provide.provider('ArrayCache', ArrayCacheProvider);
  }
]);

})(window.jQuery, window, window.angular, window.document);