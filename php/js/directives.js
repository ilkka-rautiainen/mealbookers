'use strict';

/* Directives */


angular.module('Mealbookers.directives', [])

.directive('appVersion', ['version', function(version) {
    return function(scope, elm, attrs) {
        elm.text(version);
    };
}])

.directive('restaurant', ['$timeout', '$rootScope', function($timeout, $rootScope) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'partials/directives/restaurant.html',
        controller: function($scope) {
            var resize = function() {
                $timeout(function() {
                    // Opening hours tooltip
                    $(".opening-hour-tooltip").tooltip({
                        delay: {
                            show: 500,
                            hide: 0
                        }
                    });

                    if ($rootScope.widthClass != 'xs') {
                        $("#restaurant-" + $scope.restaurant.id + " .restaurant").css("height", ($("#restaurant-" + $scope.restaurant.id).parent().height()).toString() + 'px');
                        $("#restaurant-" + $scope.restaurant.id).css("visibility", "visible");
                    }
                }, 0);
            };
            $(window).bind("load", function() {
                resize();
            });
            $scope.$on("restaurantsResize", resize);
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