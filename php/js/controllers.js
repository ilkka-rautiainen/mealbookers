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
    };

    var locationDay = $location.search().day;
    if (typeof locationDay != 'undefined' && locationDay > $scope.today && locationDay <= maxDay)
        $scope.changeDay(parseInt($location.search().day) - 1);
}])


.controller('MenuController', ['$scope', '$rootScope', '$window', 'Restaurants', function($scope, $rootScope, $window, Restaurants) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    var restaurants = Restaurants.query(null, function() {
        $scope.restaurantRows = new Array(Math.ceil(restaurants.length / $rootScope.columns));
        for (var i = 0; i < $scope.restaurantRows.length; i++)
            $scope.restaurantRows[i] = [];
        for (var i = 0; i < restaurants.length; i++)
            $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push(restaurants[i]);
        $rootScope.loaded.restaurants = true;
    });

    $scope.$watch('weekDay', function(newValue) {
        $(".restaurant-thumbnail").css("min-height", 0);
        var interval = setInterval(function() {
            if ($rootScope.pageReady()) {
                clearInterval(interval);
                unifyHeights();
            }
        }, 10);
    });

    function unifyHeights() {
        var maxHeight = 0;
        $('.restaurant-row').find('.restaurant .restaurant-thumbnail').each(function() {
            var height = $(this).outerHeight();
            // alert(height);
            if ( height > maxHeight ) {
                maxHeight = height;
            }
        });
        $('.restaurant-row').find('.restaurant .restaurant-thumbnail').css('min-height', maxHeight);
    }

    // $scope.$watch('weekDay', function() {
    //     $(".restaurant-thumbnail").css("min-height", 0);
    //     // $scope.$evalAsync(function() {
    //     //     console.log("emptied");
    //     //     $scope.restaurantsEmptied = true;
    //     // });
    // });
}])