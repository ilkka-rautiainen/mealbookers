'use strict';

/* Directives */


angular.module('Mealbookers.directives', [])

.directive('appVersion', ['version', function(version) {
    return function(scope, elm, attrs) {
        elm.text(version);
    };
}])

.directive('restaurant', ['$timeout', function($timeout) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'partials/directives/restaurant.html',
        controller: function($scope) {
            $timeout(function() {
                // Opening hours tooltip
                $(".opening-hour-tooltip").tooltip({
                    delay: {
                        show: 500,
                        hide: 0
                    }
                });
            }, 0);
        }
    };
}])

.directive('restaurantPlaceHolder', function(){
    return {
        restrict: 'E',
        replace: true,
        template: '<div class="col-md-3 col-sm-6 restaurant-placeholder"></div>'
    };
})

.directive('focusOn', function() {
    return function(scope, elem, attr) {
        scope.$on(attr.focusOn, function(e) {
            elem[0].focus();
        });
    };
})