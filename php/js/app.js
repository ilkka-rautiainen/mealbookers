'use strict';


// Declare app level module which depends on filters, and services
angular.module('Mealbookers', [
    'ngRoute',
    'Mealbookers.filters',
    'Mealbookers.services',
    'Mealbookers.directives',
    'Mealbookers.controllers',
    'Mealbookers.localization'
])

.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/menu', {
        templateUrl: 'partials/Menu.html', 
        controller: 'MenuController'
    });

    $routeProvider.otherwise({
        redirectTo: '/menu'
    });
}])

.run(['$rootScope', '$window', function($rootScope, $window) {
    $rootScope.userLang = $window.navigator.userLanguage || $window.navigator.language;
}]);
