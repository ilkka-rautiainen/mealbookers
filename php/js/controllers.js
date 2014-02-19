'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('NavigationController', ['$scope', '$rootScope', '$location', function($scope, $rootScope, $location) {

    $scope.today = (new Date().getDay() - 1) % 7;
    $scope.tomorrow = $scope.today + 1;
    $scope.weekDay = $scope.today;
    $scope.weekDayChangeProcess = false;
    $scope.restaurantsEmptied = false;
    var maxDay = 7

    $scope.remainingDays = [];
    for (var i=$scope.weekDay+2; i<7; i++)
        $scope.remainingDays.push(i);

    $scope.changeDay = function(num) {
        $location.search();
        $location.search({day: num + 1});
        $scope.weekDay = num;
        $scope.hideNavbarCollapse();
    };

    $scope.hideNavbarCollapse = function() {
        if ($('.navbar-toggle').css('display') != 'none') {
            $(".navbar-toggle").trigger("click");
        }
    }

    var locationDay = $location.search().day;
    if (typeof locationDay != 'undefined' && locationDay > $scope.today && locationDay <= maxDay)
        $scope.changeDay(parseInt($location.search().day) - 1);
}])


.controller('MenuController', ['$scope', '$rootScope', '$window', '$state', 'Restaurants', function($scope, $rootScope, $window, $state, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    $scope.restaurantRows = [];

    /**
     * Load restaurants
     */
    var restaurants = Restaurants.query(null, function() {
        $scope.restaurants = restaurants;
        $scope.restaurantRows = new Array(Math.ceil(restaurants.length / $rootScope.columns));
        for (var i = 0; i < $scope.restaurantRows.length; i++)
            $scope.restaurantRows[i] = [];
        for (var i = 0; i < restaurants.length; i++)
            $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push(restaurants[i]);
        $rootScope.loaded.restaurants = true;
    });

    $rootScope.$watch('widthClass', function() {
        $scope.restaurantRows = new Array(Math.ceil($scope.restaurants.length / $rootScope.columns));
        for (var i = 0; i < $scope.restaurantRows.length; i++)
            $scope.restaurantRows[i] = [];
        for (var i = 0; i < $scope.restaurants.length; i++)
            $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push($scope.restaurants[i]);
    });

    /**
     * Suggest a restaurant and time
     */
    $scope.suggest = function(restaurant) {
        $("#suggestionModal").modal();
    };
}])