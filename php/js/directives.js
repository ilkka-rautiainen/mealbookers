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
            weekDay:'='
        },
        controller: function($scope, $element) {
            $scope.getElementDimensions = function () {
                return { 'h': $element.height(), 'w': $element.width() };
            };

            $scope.elementDimensions = null;
            $scope.expanded = false;

            $scope.layoutCounter = 0;
            $scope.$watch($scope.getElementDimensions, function (newValue, oldValue) {
                $scope.layoutCounter = $scope.layoutCounter + 1;
                if ($scope.layoutCounter == 2) {
                    $($element[0]).find('.thumbnail').css("transition", "max-height 0s linear");
                    $($element[0]).find('.thumbnail').css("max-height", 10000);
                    $scope.elementDimensions = $scope.getElementDimensions();
                    $($element[0]).find('.thumbnail').css("max-height", 200);
                    setTimeout(function() {
                        $($element[0]).find('.thumbnail').css("transition", "max-height 0.3s linear");
                    }, 100);
                    console.log($scope.elementDimensions);
                }
            }, true);

            $element.bind('resize', function () {
                $scope.$apply();
            });

            $scope.toggle = function(id) {
                $scope.expanded = !$scope.expanded;
                if ($scope.expanded) {
                    $("#restaurant_" + id + "_thumbnail").css("max-height", $scope.elementDimensions.h);
                    $("#restaurant_" + id + "_thumbnail .overlay_sub").addClass("overlay_long");
                }
                else {
                    $("#restaurant_" + id + "_thumbnail").css("max-height", 200);
                    $("#restaurant_" + id + "_thumbnail .overlay_sub").removeClass("overlay_long");
                }
            };
        }
    };
})