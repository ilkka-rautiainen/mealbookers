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
        controller: 'NavigationController',
        resolve: {
            localization: "Localization"
        }
    })
    
    .state('Navigation.Menu', {
        url: "/menu",
        templateUrl: "partials/menu/Menu.html",
        controller: 'MenuController'
    })
    
    .state('Navigation.AcceptSuggestion', {
        url: "/suggestion/accept",
        templateUrl: "partials/AcceptSuggestion.html",
        controller: 'AcceptSuggestionController'
    })

    $urlRouterProvider.otherwise("/app/menu");
}])

.run(['$rootScope', '$window', function($rootScope, $window) {

    /**
     * Load user
     */
    $rootScope.userLang = $window.navigator.userLanguage || $window.navigator.language;

    $rootScope.alertMessage = {
        message: '',
        type: ''
    };

    $rootScope.$watch('alertMessage', function(newValue) {
        if (newValue.message.length) {
            $window.scrollTo(0, 0);
        }
    }, true);

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
