'use strict';


// Declare app level module which depends on filters, and services
angular.module('Mealbookers', [
    'ngRoute',
    'Mealbookers.filters',
    'Mealbookers.services',
    'Mealbookers.directives',
    'Mealbookers.controllers',
    'Mealbookers.localization',
    'ui.router'
])

.config(['$stateProvider', '$urlRouterProvider', function($stateProvider, $urlRouterProvider) {
    
    $stateProvider

    .state('Navigation', {
        url: "/app",
        templateUrl: "partials/Navigation.html",
        controller: 'NavigationController'
    })
    
    .state('Navigation.Menu', {
        url: "/menu",
        templateUrl: "partials/menu/Menu.html",
        controller: 'MenuController'
    })

    $urlRouterProvider.otherwise("/app/menu");
}])

.run(['$rootScope', '$window', function($rootScope, $window) {

    /**
     * Load user
     */
    $rootScope.userLang = $window.navigator.userLanguage || $window.navigator.language;

    $rootScope.loaded = {
        restaurants: false,
        lang: false,
        all: false
    };

    $rootScope.errorMessage = {
        message: '',
        type: ''
    };

    $rootScope.$watch('errorMessage', function(newValue) {
        if (newValue.message.length) {
            $window.scrollTo(0,0);
        }
    }, true);

    $rootScope.$watch('loaded', function(newValue) {
        if (newValue.restaurants && newValue.lang)
            $rootScope.loaded.all = true;
    }, true);

    $rootScope.pageReady = function() {
        return !$rootScope.$$phase && $rootScope.loaded.all;
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
