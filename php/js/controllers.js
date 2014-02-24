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

    $scope.changeDay = function(num, hideNavBar) {
        $location.search();
        $location.search({day: num + 1});
        $scope.weekDay = num;
        if (hideNavBar) {
            $scope.hideNavbarCollapse();
        }
    };

    $scope.navigateHome = function() {
        $scope.changeDay($scope.today, true);
    };

    $scope.hideNavbarCollapse = function() {
        if ($('.navbar-collapse').height() > 10) {
            $(".navbar-toggle").trigger("click");
        }
    }

    var locationDay = $location.search().day;
    if (typeof locationDay != 'undefined' && locationDay > $scope.today && locationDay <= maxDay)
        $scope.changeDay(parseInt($location.search().day) - 1, false);
}])


.controller('MenuController', ['$scope', '$rootScope', '$window', '$state', '$filter', 'Restaurants', 'CurrentUser', 'Suggestions', function($scope, $rootScope, $window, $state, $filter, Restaurants, CurrentUser, Suggestions) {

    $rootScope.title = "Menu";
    $scope.restaurants = new Array();
    $scope.restaurantRows = [];
    $scope.suggestTime = "";
    $scope.suggestRestaurant;
    $scope.suggestionError = "";

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
     * Load user's groups
     */
    $scope.groups = CurrentUser.get({action: 'groups'});

    /**
     * Suggest a restaurant and time
     */
    $scope.openSuggestion = function(restaurant) {
        $scope.suggestRestaurant = restaurant;
        $scope.suggestTime = "";
        $("#suggestionModal .group").removeClass("group-selected");
        $("#suggestionModal .member").removeClass("member-selected");
        $("#suggestionModal").modal();
    };

    $scope.suggestionSelectedMembers = [];

    $scope.toggleGroup = function(group) {
        $("#group_" + group.id).toggleClass("group-selected");

        if ($("#group_" + group.id).hasClass("group-selected")) {
            $(".group_" + group.id + "_member").each(function() {
                $(".member_" + $(this).attr("data-member-id")).addClass("member-selected");
            });
        }
        else {
            $(".group_" + group.id + "_member").each(function() {
                $(".member_" + $(this).attr("data-member-id")).removeClass("member-selected");
            });
        }
    };

    $scope.toggleGroupMember = function(group, member) {
        $(".member_" + member.id).toggleClass("member-selected");
    };

    $scope.suggest = function() {
        var members = {};
        $(".group-container .member-selected").each(function(idx, el) {
            members[$(el).attr("data-member-id")] = true;
        });
        var response = Suggestions.post({
            restaurantId: $scope.suggestRestaurant.id,
            day: $scope.weekDay,
            time: $scope.suggestTime,
            members: members
        }, function() {
            if (typeof response.status != 'undefined' && response.status == "ok") {
                $("#closeSuggestion").trigger('click');
            }
            else {
                $scope.suggestionError = $filter('i18n')('suggestion_save_error');
            }
        },
        function() {
            $scope.suggestionError = $filter('i18n')('suggestion_save_error');
        });
    };

    /**
     * Modify suggest time when it's changed
     */
    $scope.$watch('suggestTime', function(newTime, oldTime) {
        if (!newTime)
            return;
        else if (newTime.match(/^[0-9]{3}$/))
            $scope.suggestTime = $scope.suggestTime.substring(0, 2) + ":" + $scope.suggestTime.substring(2, 3);
        else if (!newTime.match(/^([0-9]{1}|[0-9]{2}(:[0-9]{0,2})?)$/))
            $scope.suggestTime = $scope.suggestTime.substring(0, $scope.suggestTime.length - 1);
        else if ((newTime && oldTime && newTime.length > oldTime.length) || (newTime && !oldTime && newTime.length > 0)) {
            if (newTime.length === 1) {
                var intTime = parseInt(newTime);
                if (intTime > 2) {
                    $scope.suggestTime = "0" + $scope.suggestTime + ":";
                }
            }
            else if (newTime.length === 2) {
                if (parseInt(newTime) > 23)
                    $scope.suggestTime = "";
                else
                    $scope.suggestTime = $scope.suggestTime + ":";
            }
            else if (newTime.length === 4) {
                if (parseInt(newTime.substring(3, 4)) > 5)
                    $scope.suggestTime = $scope.suggestTime.substring(0, 3) + "0" + $scope.suggestTime.substring(3, 4);
            }
        }
    });
}])