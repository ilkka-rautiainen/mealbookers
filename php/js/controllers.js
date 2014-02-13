'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('NavigationController', ['$scope', function($scope) {

    $scope.today = (new Date().getDay() - 1) % 7;
    $scope.tomorrow = $scope.today + 1;
    $scope.weekDay = $scope.today;

    $scope.remainingDays = [];
    for (var i=$scope.weekDay+2; i<7; i++)
        $scope.remainingDays.push(i);

    $scope.changeDay = function(num) {
        $scope.weekDay = num;
    }

}])

.controller('LoginController', ['$scope', '$rootScope', function($scope, $rootScope) {

    

}])


.controller('MenuController', ['$scope', '$rootScope', 'Restaurants', function($scope, $rootScope, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    var restaurants = Restaurants.query(null, function(){
        $scope.restaurants = restaurants;
    });
}])