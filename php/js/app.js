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
            initialization: "Initialization"
        }
    })
    
    .state('Navigation.Menu', {
        url: "/:day",
        templateUrl: "partials/Menu.html",
        controller: 'MenuController'
    })
    
    .state('Navigation.Menu.Suggestion', {
        url: "/suggestion",
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

.run(['$rootScope', '$window', '$http', '$timeout', function($rootScope, $window, $http, $timeout) {

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
        liveViewInterval: 3000
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

    $rootScope.refreshCurrentUser = function(done, liveView) {
        $http.get('api/1.0/user').success(function(data) {
            console.log("Current user refreshed");
            for (var i in data) {
                $rootScope.currentUser[i] = data[i];    
            }
            $rootScope.updateGroupsWithMe();
            refreshSuggestions();

            if (typeof done == 'function') {
                done();
            }
        })
    };

    $rootScope.liveViewUpdate = function() {
        $timeout.cancel($rootScope.liveViewTimeout);
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
        }, true);
    };

    $rootScope.startLiveView = function() {
        $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
        console.log("Live View started");
    };

    $rootScope.startLiveView();

    var refreshSuggestions = function() {
        // console.log("refreshSuggestions unimplemented...");
    };

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
    }

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
