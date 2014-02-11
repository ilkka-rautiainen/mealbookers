'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('NavigationController', ['$scope', '$rootScope', function($scope, $rootScope) {

    $scope.today = (new Date().getDay() - 1) % 7;
    $scope.tomorrow = $scope.today + 1;
    $rootScope.weekDay = $scope.today;

    $scope.remainingDays = [];
    for (var i=$rootScope.weekDay+2; i<7; i++)
        $scope.remainingDays.push(i);

    $scope.changeDay = function(num) {
        $rootScope.weekDay = num;
    }

}])


.controller('MenuController', ['$scope', '$rootScope', 'Restaurants', function($scope, $rootScope, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    var restaurants = Restaurants.query(null, function(){
        for (var i=0; i<restaurants.length; i++)
            restaurants[i].expanded = false;
        $scope.restaurants = restaurants;
    });

    

}])