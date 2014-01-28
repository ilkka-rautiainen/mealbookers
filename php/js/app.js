'use strict';


// Declare app level module which depends on filters, and services
angular.module('Mealbookers', [
    'ngRoute',
    'Mealbookers.filters',
    'Mealbookers.services',
    'Mealbookers.directives',
    'Mealbookers.controllers'
])

.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/menu', {
        templateUrl: 'partials/Menu.html', 
        controller: 'MenuController'
    });

    $routeProvider.otherwise({
        redirectTo: '/menu'
    });
}]);
