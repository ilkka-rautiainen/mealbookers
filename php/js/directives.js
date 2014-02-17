'use strict';

/* Directives */


angular.module('Mealbookers.directives', [])

.directive('appVersion', ['version', function(version) {
    return function(scope, elm, attrs) {
        elm.text(version);
    };
}])

.directive('restaurant', function(){
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'partials/directives/restaurant.html',
        controller: function($scope, $element) { }
    };
})