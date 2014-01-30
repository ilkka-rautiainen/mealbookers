'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('MenuController', ['$scope', '$rootScope', 'Restaurants', function($scope, $rootScope, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    $scope.weekDay = new Date().getDay() - 1;
    var restaurants = Restaurants.query(null, function(){
        for (var i=0; i<restaurants.length; i++)
            restaurants[i].expanded = false;
        $scope.restaurants = restaurants;
    });

    $scope.toggle = function(idx) {
        $scope.restaurants[idx].expanded = !$scope.restaurants[idx].expanded;
    };

}])