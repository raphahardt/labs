
(function($, angular, document, undefined) {
    'use strict';

    function PageVisibilityAPI () {
        var api = {};
        // detects visibilityChange API names
        // thanks to David Walsh http://davidwalsh.name/page-visibility
        if (typeof document.hidden !== "undefined") {
            api.hidden = "hidden";
            api.eventName = "visibilitychange";
            api.state = "visibilityState";
        } else if (typeof document.mozHidden !== "undefined") {
            api.hidden = "mozHidden";
            api.eventName = "mozvisibilitychange";
            api.state = "mozVisibilityState";
        } else if (typeof document.msHidden !== "undefined") {
            api.hidden = "msHidden";
            api.eventName = "msvisibilitychange";
            api.state = "msVisibilityState";
        } else if (typeof document.webkitHidden !== "undefined") {
            api.hidden = "webkitHidden";
            api.eventName = "webkitvisibilitychange";
            api.state = "webkitVisibilityState";
        }
        return api;
    }

    function AwayDetectorProvider($doc, $timeout, defaults) {

        this.create = function (opts) {
            opts = $.extend({}, defaults, opts);
            var instance = new AwayDetector($doc, $timeout, opts);
            return instance;
        };

    }
    AwayDetectorProvider.prototype.constructor = AwayDetectorProvider;
    AwayDetectorProvider.prototype.listen = function (opts) {
        return this.create(opts);
    };

    /**
     * AwayDetector Class
     *
     * Listen to idle and away events and executes the user defined callbacks
     * for each event. You can define multiple callbacks per event, and them
     * will be executed in order.
     *
     * @param {Object} $doc Angular's $document injectable
     * @param {Object} $timeout Angular's $timeout service
     * @returns {AwayDetector}
     */
    function AwayDetector($doc, $timeout, opts) {
        // private ----
        var that = this,
            away = false,
            awayTimer = null,
            awayTimestamp,
            idle = false,
            idleTimer = null,
            idleTimestamp,

            settings = {},

            callbacks = {
                active: [],
                away: [],
                idle: []
            };

        /**
         * Define o timeout do idle
         *
         * @private
         * @returns {void}
         */
        function setIdleTimeout() {
            var ms = settings.idleTimeout;
            idleTimestamp = new Date().getTime() + ms;
            if (idleTimer != null) {
                try {
                    idleTimer.cancel();
                } catch(e){}
            }
            idleTimer = $timeout(that.makeIdle, ms + 50);
            //console.log('idle in ' + ms + ', tid = ' + idleTimer);
        }

        /**
         * Define o timeout do away
         *
         * @private
         * @returns {void}
         */
        function setAwayTimeout() {
            var ms = settings.awayTimeout;
            awayTimestamp = new Date().getTime() + ms;
            if (awayTimer != null) {
                try {
                    awayTimer.cancel();
                } catch(e){}
            }
            awayTimer = $timeout(that.makeAway, ms + 50);
            //console.log('away in ' + ms + ', tid = ' + awayTimer);
        }

        // protected -----
        this.defaults = {
            idleTimeout: 5000,
            awayTimeout: 10000
        };

        /**
         * Retorna se está idle ou não
         *
         * @returns {Boolean}
         */
        this.isIdle = function () {
            return idle;
        };

        /**
         * Retorna se está away ou não
         *
         * @returns {Boolean}
         */
        this.isAway = function () {
            return away;
        };

        /**
         *
         *
         * @returns {Boolean}
         */
        this.isActive = function () {
            return !idle && !away;
        };

        /**
         * Adds a callback for a event.
         *
         * Events:
         *  'idle', (when the user is idle for some seconds)
         *  'away', (when the user is away from page for many seconds/minutes)
         *  'active' (when the user comes back to page after idle/away)
         *
         * @param {String} eventName
         * @param {Function} callback
         * @returns {void}
         */
        this.addCallback = function (eventName, callback) {
            switch (true) {
                case (eventName === 'idle'):
                case (eventName === 'away'):
                case (eventName === 'active'):
                    callbacks[eventName].push(callback);
                    break;
                default:
                    throw new Error('"'+eventName+'" event is not supported by AwayDetector');
            }
        };

        this.makeIdle = function () {
            var t = new Date().getTime();
            if (t < idleTimestamp) {
                // not idle yet, waits a little more
                idleTimer = $timeout(that.makeIdle, idleTimestamp - t + 50);
                return;
            }

            try {
                if (!idle) {
                    for (var i = 0; i < callbacks.idle.length; i++) {
                        callbacks.idle[i]();
                    }
                }
            } catch (err) {
            }

            //console.log('** IDLE **');
            idle = true;
        };

        this.makeAway = function () {
            var t = new Date().getTime();
            if (t < awayTimestamp) {
                // not away yet, waits a little more
                awayTimer = $timeout(that.makeAway, awayTimestamp - t + 50);
                return;
            }

            try {
                if (!away) {
                    for (var i = 0; i < callbacks.away.length; i++) {
                        callbacks.away[i]();
                    }
                }
            } catch (err) {
            }

            //console.log('** AWAY **');
            away = true;
        };

        this.makeActive = function () {
            var t = new Date().getTime();
            idleTimestamp = t + settings.idleTimeout;
            awayTimestamp = t + settings.awayTimeout;

            if (idle) {
                setIdleTimeout();
            }

            if (away) {
                setAwayTimeout();
            }

            try {
                // only executes callbacks if user was idle/away
                if (idle || away) {
                    //console.log('** BACK **');

                    // to fire angular's digest, the callbacks needs to be called from
                    // $timeout service
                    $timeout(function () {
                        for (var i = 0; i < callbacks.active.length; i++) {
                            callbacks.active[i]();
                        }
                    }, 0);
                }
            } catch (err) {
            }

            idle = false;
            away = false;
        };

        this.setOption = function (optionName, value) {
            settings[optionName] = value;
            setIdleTimeout();
            setAwayTimeout();
        };

        this.setOptions = function (values) {
            settings = $.extend({}, that.defaults, settings, values);
            setIdleTimeout();
            setAwayTimeout();
        };

        this.getOption = function (optionName) {
            return settings[optionName];
        };

        this.getOptions = function () {
            return settings;
        };

        /**
         * Constructor
         *
         * @param {Object} opts
         * @returns {void}
         */
        function init (opts) {

            // set the settings for this instance
            that.setOptions(opts);

            document.addEventListener(PageVisibilityAPI().eventName, function() {
                if (document[PageVisibilityAPI().state] === PageVisibilityAPI().hidden) {
                    // away from tab change
                    idleTimestamp = awayTimestamp = (new Date).getTime();
                    that.makeIdle();
                    that.makeAway();
                } else {
                    // back from tab change
                    that.makeActive();
                }
            }, false);

            $doc.on([
            'mousemove',
            'mouseenter',
            'scroll',
            'keydown',
            'click',
            'dblclick'
            ].join(' '), function () {
                that.makeActive();
            });

            setIdleTimeout();
            setAwayTimeout();

        };

        // initialize
        init(opts);

    }

    AwayDetector.prototype.constructor = AwayDetector;
    AwayDetector.prototype.option = function (optionName, value) {
        if (angular.isString(optionName)) {
            if (angular.isDefined(value)) {
                // setter
                this.setOption(optionName, value);
            } else {
                // getter
                return this.getOption(optionName);
            }
        } else if (angular.isDefined(optionName)) {
            // setter multiple
            this.setOptions(optionName);
        } else {
            // getter multiple
            return this.getOptions();
        }
        return this;
    };
    AwayDetector.prototype.on = function (eventName, callback) {
        this.addCallback(eventName, callback);
        return this;
    };

    /**
     * Angular module
     */
    angular.module('broda.awayDetector', [], [
        '$provide',
        function ($provide) {
            $provide.provider('$awaylistener',
            function () {
                var defaults = this.defaults = {
                    idleTimeout: 5000,
                    awayTimeout: 10000
                };
                this.$get = [
                    '$document', '$timeout',
                    function ($document, $timeout) {
                        return new AwayDetectorProvider($document, $timeout, defaults);
                    }
                ];
            });
        }
    ])
    .directive('brdShowAway', [
        '$awaylistener', '$animate',
        function ($awaylistener, $animate) {
            return function (scope, element, attrs) {
                var listener = $awaylistener.listen();

                // initialize
                element.addClass('ng-hide');

                listener.on('away', function () {
                    $animate.removeClass(element, 'ng-hide');
                }).on('active', function () {
                    $animate.addClass(element, 'ng-hide');
                });

                scope.$watch(attrs.brdShowAway, function (timeout) {
                    if (angular.isNumber(timeout)) {
                        console.log('inicia com timeout', timeout);
                        listener.option('awayTimeout', timeout);
                    }
                });
            };
        }
    ]);

})(window.jQuery, window.angular, window.document);