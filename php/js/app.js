'use strict';


// Declare app level module which depends on filters, and services
angular.module('Mealbookers', [
    'ngAnimate',
    'Mealbookers.filters',
    'Mealbookers.services',
    'Mealbookers.directives',
    'Mealbookers.controllers',
    'Mealbookers.localization',
    'ui.router'
])

.config(['$stateProvider', '$urlRouterProvider', '$uiViewScrollProvider', function($stateProvider, $urlRouterProvider, $uiViewScrollProvider) {
    $uiViewScrollProvider.useAnchorScroll()
    $stateProvider

    .state('Navigation', {
        abstract: true,
        url: "/menu",
        templateUrl: "partials/Navigation.html",
        controller: 'NavigationController',
        resolve: {
            InitApp: "InitApp"
        }
    })
    
    .state('Navigation.Menu', {
        url: "/:day",
        templateUrl: "partials/Menu.html",
        controller: 'MenuController'
    })
    
    .state('Navigation.Menu.Login', {
        url: "/login",
        templateUrl: "partials/modals/Login.html",
        controller: 'LoginController'
    })

    .state('Navigation.Menu.Register', {
        url: "/register",
        templateUrl: "partials/modals/Register.html",
        controller: 'RegisterController'
    })
    
    .state('Navigation.Menu.Suggestion', {
        url: "/restaurant/:restaurantId/suggestion",
        templateUrl: "partials/modals/Suggestion.html",
        controller: 'SuggestionController'
    })
    
    .state('Navigation.Menu.AccountSettings', {
        url: "/settings/general",
        templateUrl: "partials/modals/AccountSettings.html",
        controller: 'AccountSettingsController'
    })
    
    .state('Navigation.Menu.GroupSettings', {
        url: "/settings/groups",
        templateUrl: "partials/modals/GroupSettings.html",
        controller: 'GroupSettingsController'
    })
    
    .state('Navigation.AcceptSuggestion', {
        url: "/suggestion/accept",
        templateUrl: "partials/AcceptSuggestion.html",
        controller: 'AcceptSuggestionController'
    })

    $urlRouterProvider.otherwise("/menu/" + (((new Date().getDay() + 6) % 7) + 1));
}])

.run(['$rootScope', '$window', '$http', '$timeout', '$state', '$stateParams', 'InitApp', function($rootScope, $window, $http, $timeout, $state, $stateParams, InitApp) {

    $rootScope.currentUser = {
        role: 'guest',
        groups: [],
    };

    var emptyMessage = {
        message: '',
        type: ''
    };
    $rootScope.alertMessage = emptyMessage;

    $rootScope.config = {
        alertTimeouts: {
            'alert-danger': 30000,
            'alert-warning': 10000,
            'alert-info': 4000,
            'alert-success': 4000
        },
        liveViewInterval: 15000
    };

    var alertTimeout = null;
    var alertFadeTimeout = null;

    $rootScope.dismissAlert = function() {
        if (alertTimeout) {
            clearTimeout(alertTimeout);
        }
        if (alertFadeTimeout) {
            clearTimeout(alertFadeTimeout);
        }
        alertFadeout();
    };

    $rootScope.alert = function(type, message) {
        if (!$rootScope.config.alertTimeouts[type]) {
            return console.error("Invalid alert type: " + type);
        }

        $rootScope.alertMessage = {
            type: type,
            message: message
        };
        $(".main-alert").finish();
        $(".main-alert").show();
        if (alertTimeout) {
            clearTimeout(alertTimeout);
        }
        if (alertFadeTimeout) {
            clearTimeout(alertFadeTimeout);
        }
        alertTimeout = setTimeout(alertFadeout, $rootScope.config.alertTimeouts[type]);
    };

    var alertFadeout = function() {
        $(".main-alert").animate({
            height: 'toggle',
            'margin-top': 'toggle',
            'margin-bottom': 'toggle',
            'padding-top': 'toggle',
            'padding-bottom': 'toggle',
            'border-top': 'toggle',
            'border-bottom': 'toggle',
            opacity: 'toggle'
        }, 1500);
        alertFadeTimeout = setTimeout(function() {
            $rootScope.alertMessage = emptyMessage;
        }, 1500);
    };

    $rootScope.updateGroupsWithMe = function() {
        if ($rootScope.currentUser.role == 'guest') {
            return;
        }

        var groups = angular.copy($rootScope.currentUser.groups);
        $rootScope.currentUser.groupsWithMe = [];
        $rootScope.currentUser.friends = 0;
        for (var i in groups) {
            $rootScope.currentUser.friends += groups[i].members.length;
            groups[i].members.unshift(jQuery.extend({}, $rootScope.currentUser.me));
            $rootScope.currentUser.groupsWithMe.push(groups[i]);
        }
    };

    /**
     * Reloads current user
     */
    $rootScope.refreshCurrentUser = function(done, liveView) {
        if (liveView) {
            var params = {
                after: $rootScope.currentUser.timestamp
            };
        }
        else {
            var params = {};
        }
        $http.get('api/1.0/user', {
            params: params
        }).success(function(data) {
            if (typeof data.status == 'string' && data.status == 'up_to_date') {
                $rootScope.currentUser.timestamp = data.timestamp;
                if (typeof done == 'function') {
                    done();
                }
            }
            else {
                console.log("Current user refreshed");
                for (var i in data) {
                    $rootScope.currentUser[i] = data[i];    
                }
                $rootScope.updateGroupsWithMe();
                $rootScope.refreshSuggestions(done);
            }
        });
    };

    $rootScope.refreshSuggestions = function(done) {
        $http.get('api/1.0/restaurants/suggestions').success(function(data) {

            for (var i = 0; i < $rootScope.restaurants.length; i++) {
                $rootScope.restaurants[i].suggestionList = [];
            }

            for (var restaurantId in data) {
                // Find restaurant with that id
                var restaurantIndex = -1;
                for (var i = 0; i < $rootScope.restaurants.length; i++) {
                    if ($rootScope.restaurants[i].id == restaurantId) {
                        restaurantIndex = i;
                        break;
                    }
                }
                if (restaurantIndex == -1) {
                    console.log("restaurant " + restaurantId.toString() + " not found in restaurant array, skipping");
                    continue;
                }

                $rootScope.restaurants[restaurantIndex].suggestionList = data[restaurantId];
            }

            console.log("Suggestions refreshed");
            if (typeof done == 'function') {
                done();
            }
        });
    };

    $rootScope.liveViewUpdate = function() {
        // console.log("Live view update");
        $timeout.cancel($rootScope.liveViewTimeout);

        // Check if the page has loaded
        if (!$rootScope.restaurants || !$rootScope.today) {
            $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
            return console.log("Waiting before starting live view.");
        }
        // Day has changed
        if ($rootScope.today != ((new Date().getDay() + 6) % 7) + 1) {
            if ($rootScope.today == 7) {
                $state.transitionTo($state.current, {day: 1}, {reload: true, inherit: false, notify: true});
            }
            else {
                $state.transitionTo($state.current, $stateParams, {reload: true, inherit: false, notify: true});
            }
            $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
            return console.log("Day has changed, refreshing state");;
        }

        $rootScope.refreshCurrentUser(function() {
            $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
        }, true);
    };

    /**
     * Refreshes current user once and then stops the live view
     * @param  done callback which is called after the current user refresh
     */
    $rootScope.refreshCurrentUserAndStopLiveView = function(done) {
        $timeout.cancel($rootScope.liveViewTimeout);
        $rootScope.refreshCurrentUser(function(){
            console.log("Live View stopped");
            if (typeof done == 'function') {
                done();
            }
        });
    };

    $rootScope.startLiveView = function() {
        $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
        console.log("Live View started");
    };

    $rootScope.refreshLocalization = function(done) {
        // Get localization
        $http.get('api/1.0/app/language/' + $rootScope.currentUser.language).success(function(result)
        {
            console.log("Localization refreshed");
            $rootScope.localization = result;
        });

        $rootScope.refreshRestaurants(done);
    };

    $rootScope.refreshRestaurants = function(done) {
        // Get restaurants
        $http.get('api/1.0/restaurants', {
            params: {
                lang: $rootScope.currentUser.language
            }
        }).success(function(result) {
            console.log("Restaurants refreshed");
            $rootScope.restaurants = result;

            if (typeof done == 'function') {
                done();
            }
        });
    };

    $rootScope.startLiveView();

    $rootScope.operationFailed = function(httpCode, errorMessage, customAlertFunction) {
        var alertFunction;
        if (typeof customAlertFunction == 'function')
            alertFunction = customAlertFunction;
        else
            alertFunction = $rootScope.alert;

        $rootScope.refreshCurrentUser(function() {
            if ($rootScope.localization[errorMessage + '_' + httpCode.toString()])
                alertFunction('alert-warning', $rootScope.localization[errorMessage + '_' + httpCode.toString()]);
            else
                alertFunction('alert-danger', $rootScope.localization[errorMessage]);

        });
    };

    $rootScope.$on('$stateChangeStart', function(e, toState, toParams, fromState, fromParams) {
        if (fromState.name == 'Navigation.Menu.Suggestion'
            || fromState.name == 'Navigation.Menu.Register'
            || fromState.name == 'Navigation.Menu.Login'
            || fromState.name == 'Navigation.Menu.AccountSettings'
            || fromState.name == 'Navigation.Menu.GroupSettings')
            $(".modal-backdrop").remove();
    });


    $rootScope.getWeekDayText = function(day) {
        if (day < 1 || day > 7) {
            return console.error("Incorrect day passed: " + day);
        }
        
        var today = ((new Date().getDay() + 6) % 7) + 1;
        if (day == today) {
            return $rootScope.localization.today;
        }
        else if (day == today + 1) {
            return $rootScope.localization.tomorrow;
        }
        else {
            return $rootScope.localization['weekday_' + day];
        }
    };

    var setWidthClass = function() {
        $rootScope.$apply(function() {
            $rootScope.windowWidth = $window.innerWidth;
            if ($rootScope.windowWidth >= 1200) {
                $rootScope.widthClass = "lg";
                $rootScope.columns = 4;
            }
            else if ($rootScope.windowWidth >= 992) {
                $rootScope.widthClass = "md";
                $rootScope.columns = 3;
            }
            else if ($rootScope.windowWidth >= 768) {
                $rootScope.widthClass = "sm";
                $rootScope.columns = 2;
            }
            else {
                $rootScope.widthClass = "xs";
                $rootScope.columns = 1;
            }
        });
    }

    setWidthClass();
    $($window).bind('resize', setWidthClass);
}]);
