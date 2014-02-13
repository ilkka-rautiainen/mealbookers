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
        scope: {
            restaurant:'=',
            weekDay:'=',
            restaurantData:'='
        },
        controller: function($scope, $element) {
            $scope.getElementHeight = function () {
                return $element.height();
            };

            $scope.elementHeight = null;
            $scope.expanded = false;

            $scope.layoutCounter = 0;
            $scope.$watch($scope.getElementHeight, function (newValue, oldValue) {
                $scope.layoutCounter = $scope.layoutCounter + 1;
                if ($scope.layoutCounter == 1) {
                    $($element[0]).find('.thumbnail').css("max-height", "none");
                }
                else if ($scope.layoutCounter == 2) {
                    $($element[0]).find('.thumbnail').css("transition", "height 0s linear");
                    $($element[0]).find('.thumbnail').css("height", "auto");
                    $scope.elementHeight = $scope.getElementHeight();
                    $scope.restaurantData.height = Math.max($scope.restaurantData.height, $scope.elementHeight);
                    console.log($scope.restaurantData.height);
                    $($element[0]).find('.thumbnail').css("height", 200);
                    setTimeout(function() {
                        $($element[0]).find('.thumbnail').css("transition", "height 0.4s linear");
                    }, 100);
                }
            }, true);

            $element.bind('resize', function () {
                $scope.$apply();
            });

            $scope.toggle = function(id) {
                $scope.restaurantData.expanded = !$scope.restaurantData.expanded;
                if ($scope.restaurantData.expanded) {
                    $(".restaurant-thumbnail").css("height", $scope.restaurantData.height);
                    $(".restaurant-thumbnail .overlay_sub").addClass("overlay_long");
                }
                else {
                    $(".restaurant-thumbnail").css("height", 200);
                    $(".restaurant-thumbnail .overlay_sub").removeClass("overlay_long");
                }
            };
        }
    };
})