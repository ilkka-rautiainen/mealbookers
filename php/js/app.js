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
    });

    $urlRouterProvider.otherwise("/app/menu");
}])

.run(['$rootScope', '$window', function($rootScope, $window) {
    $rootScope.userLang = $window.navigator.userLanguage || $window.navigator.language;
}]);
