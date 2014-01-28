'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('MenuController', ['$scope', '$rootScope', 'Restaurants', function($scope, $rootScope, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restuarants = new Array();
    $scope.weekDay = new Date().getDay() - 1;
    var restaurants = Restaurants.query(null, function(){
        $scope.restaurants = restaurants;
    });

}])