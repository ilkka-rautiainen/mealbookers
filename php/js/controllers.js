'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('NavigationController', ['$scope', '$rootScope', '$location', function($scope, $rootScope, $location) {

    $scope.changeDay = function(day) {
        $scope.weekDay = day;
        var search = $location.search();
        search.day = day;
        $location.search(search);
    };

    $scope.today = ((new Date().getDay() - 1) % 7) + 1;
    $scope.tomorrow = $scope.today + 1;
    $scope.weekDayChangeProcess = false;
    $scope.restaurantsEmptied = false;
    $scope.maxDay = 5;
    $scope.hasData = true;
    $scope.weekDay;

    $scope.remainingDays = [];
    for (var i = $scope.today + 2; i <= $scope.maxDay; i++) {
        $scope.remainingDays.push(i);
    }

    $scope.$watch(function() {
        return $location.search().day;
    }, function (newValue) {
        if (newValue == undefined || parseInt(newValue) < $scope.today || parseInt(newValue) > $scope.maxDay)
            return $scope.changeDay($scope.today);
        $scope.weekDay = parseInt(newValue);
    });

    $(".navbar").on("click", "a", null, function () {
        if ($rootScope.widthClass === 'xs')
            $(".navbar-collapse").collapse('hide');
    });
}])


.controller('MenuController', ['$scope', '$rootScope', '$window', '$location', '$http', '$state', '$filter', 'Restaurants', 'CurrentUser', 'Suggestions', function($scope, $rootScope, $window, $location, $http, $state, $filter, Restaurants, CurrentUser, Suggestions) {

    $rootScope.title = "Menu";
    $scope.restaurants = [];
    $scope.restaurantRows = [];
    $scope.suggestTime = "";
    $scope.suggestRestaurant;
    $scope.suggestionMessage = {
        type: '',
        message: ''
    };

    /**
     * Load restaurants
     */
    var loadRestaurants = function() {
        var restaurants = Restaurants.query(null, function() {
            $scope.restaurants = restaurants;
            $scope.restaurantRows = new Array(Math.ceil(restaurants.length / $rootScope.columns));
            for (var i = 0; i < $scope.restaurantRows.length; i++)
                $scope.restaurantRows[i] = [];
            for (var i = 0; i < restaurants.length; i++)
                $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push(restaurants[i]);
            $rootScope.loaded.restaurants = true;
        });
    };
    loadRestaurants();

    $rootScope.$watch('widthClass', function() {
        $scope.restaurantRows = new Array(Math.ceil($scope.restaurants.length / $rootScope.columns));
        for (var i = 0; i < $scope.restaurantRows.length; i++)
            $scope.restaurantRows[i] = [];
        for (var i = 0; i < $scope.restaurants.length; i++)
            $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push($scope.restaurants[i]);
    });

    /**
     * @todo  visualize the acceptance process to user as a bootstrap alert or smth
     */
    if (typeof $location.search().hash != 'undefined') {
        $http.post('api/1.0/suggestion?hash=' + $location.search().hash).success(function(result) {
            loadRestaurants();
            var search = $location.search();
            delete search.hash;
            $location.search(search);
            $scope.weekDay = result.weekDay;
        });
    }

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
        $scope.suggestionMessage.type = '';
        $scope.suggestionMessage.message = '';
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

    var validateSuggestForm = function() {
        if ($scope.weekDay <= $scope.today) {
            var timeParts = $scope.suggestTime.split(":");
            var suggestionDate = new Date();
            suggestionDate.setHours(timeParts[0]);
            suggestionDate.setMinutes(timeParts[1]);
            if (suggestionDate.getTime() + 600000 < new Date()) {
                $scope.suggestionMessage.type = 'alert-warning';
                $scope.suggestionMessage.message = $filter('i18n')('suggestion_too_early');
                return false;
            }
        }
        return true;
    };

    $scope.suggest = function() {
        if (!validateSuggestForm()) {
            return;
        }
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
                if (typeof response.failed_to_invite === 'object') {
                    $scope.suggestionMessage.type = 'alert-warning';
                    $scope.suggestionMessage.message = 'Failed to invite: ' + response.failed_to_invite.join(", ");
                }
                else {
                    $("#closeSuggestion").trigger('click');
                    loadRestaurants();
                }
            }
            else {
                $scope.suggestionMessage.type = 'alert-danger';
                $scope.suggestionMessage.message = $filter('i18n')('suggestion_save_error');
            }
        },
        function() {
            $scope.suggestionMessage.type = 'alert-danger';
            $scope.suggestionMessage.message = $filter('i18n')('suggestion_save_error');
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