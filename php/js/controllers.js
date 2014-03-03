'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('AcceptSuggestionController', ['$http', '$filter', '$rootScope', '$state', '$location', function($http, $filter, $rootScope, $state, $location) {
    if (typeof $location.search().hash != 'undefined') {
        $http.post('api/1.0/suggestion?hash=' + $location.search().hash).success(function(result) {
            if (typeof result == 'object' && result.status == 'deleted') {
                $rootScope.stateData = {
                    message: {
                        message: $rootScope.localization['suggestion_been_deleted'],
                        type: 'alert-warning'
                    }
                };
            }
            else if (typeof result == 'object' && result.status == 'too_old') {
                $rootScope.stateData = {
                    day: result.weekDay + 1,
                    message: {
                        message: $rootScope.localization['suggestion_accept_gone'],
                        type: 'alert-info'
                    }
                };
            }
            else if (typeof result == 'object' && result.status == 'ok') {
                $rootScope.stateData = {
                    day: result.weekDay + 1,
                    message: {
                        message: $rootScope.localization['suggestion_accept_succeeded']
                            + ', ' + result.restaurant + ', '
                            + $filter('lowercase')($rootScope.getWeekDayText(result.weekDay + 1)) + ' ' + result.time,
                        type: 'alert-success'
                    }
                };
            }
            else {
                $rootScope.stateData = {
                    message: {
                        message: $rootScope.localization['suggestion_accept_failed'],
                        type: 'alert-danger'
                    }
                };
            }
            $state.go("Navigation.Menu");
        });
    }
}])


.controller('NavigationController', ['$scope', '$rootScope', '$location', function($scope, $rootScope, $location) {
    
    // Changes day
    $scope.changeDay = function(day) {
        $scope.weekDay = day;
        var search = $location.search();
        search.day = day;
        $location.search(search);
    };

    $scope.today = ((new Date().getDay() + 6) % 7) + 1;
    $scope.tomorrow = $scope.today + 1;
    $scope.weekDayChangeProcess = false;
    $scope.restaurantsEmptied = false;
    $scope.maxDay = 7;
    $scope.hasData = true;
    $scope.weekDay;

    // Make remaining days array for navbar
    $scope.remainingDays = [];
    for (var i = $scope.today + 2; i <= $scope.maxDay; i++) {
        $scope.remainingDays.push(i);
    }

    // Listens for weekday changes together with changeDay() function
    $scope.$watch(function() {
        return $location.search().day;
    }, function (newValue) {
        if (newValue == undefined || parseInt(newValue) < $scope.today || parseInt(newValue) > $scope.maxDay)
            return $scope.changeDay($scope.today);
        $scope.weekDay = parseInt(newValue);
    });

    // Makes navbar hide when menu link clicked in xs-devices
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

    // Show information passed from previous state
    if ($rootScope.stateData !== undefined && $rootScope.stateData.message !== undefined) {
        $rootScope.alertMessage = $rootScope.stateData.message;
    }
    if ($rootScope.stateData !== undefined && $rootScope.stateData.day !== undefined) {
        $scope.changeDay($rootScope.stateData.day);
    }

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
        });
    };
    loadRestaurants();


    var updateSuggestion = function(restaurant, suggestion, day) {
        for (var i = 0; i < $scope.restaurants.length; i++) {
            if ($scope.restaurants[i].id == restaurant.id) {
                for (var j = 0; j < $scope.restaurants[i].suggestionList[day - 1].length; j++) {
                    if ($scope.restaurants[i].suggestionList[day - 1][j].id == suggestion.id) {
                        $scope.restaurants[i].suggestionList[day - 1][j] = suggestion;
                        break;
                    }
                }
                break;
            }
        }
    };

    var deleteSuggestion = function(restaurant, suggestion, day) {
        for (var i = 0; i < $scope.restaurants.length; i++) {
            if ($scope.restaurants[i].id == restaurant.id) {
                for (var j = 0; j < $scope.restaurants[i].suggestionList[day - 1].length; j++) {
                    if ($scope.restaurants[i].suggestionList[day - 1][j].id == suggestion.id) {
                        $scope.restaurants[i].suggestionList[day - 1].splice(j, 1);
                        break;
                    }
                }
                break;
            }
        }
    };

    $rootScope.$watch('widthClass', function() {
        $scope.restaurantRows = new Array(Math.ceil($scope.restaurants.length / $rootScope.columns));
        for (var i = 0; i < $scope.restaurantRows.length; i++)
            $scope.restaurantRows[i] = [];
        for (var i = 0; i < $scope.restaurants.length; i++)
            $scope.restaurantRows[Math.floor(i / $rootScope.columns)].push($scope.restaurants[i]);
    });

    // Load user's groups
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
        $('#suggestionModal').on('shown.bs.modal', function (e) {
            if ($rootScope.widthClass != 'xs') {
                $("#suggestionModal input.suggest-time").focus();
            }
        });
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

        $scope.suggestionMessage.type = 'alert-info';
        $scope.suggestionMessage.message = $filter('i18n')('suggest_sending');

        var response = Suggestions.post({
            restaurantId: $scope.suggestRestaurant.id,
            day: $scope.weekDay - 1,
            time: $scope.suggestTime,
            members: members
        }, function() {
            if (typeof response.status != 'undefined' && response.status == "ok") {
                if (typeof response.failed_to_send_invitation_email === 'object') {
                    $scope.suggestionMessage.type = 'alert-warning';
                    $scope.suggestionMessage.message = $filter('i18n')('suggest_failed_to_send_invitation_email')
                        + ' ' + response.failed_to_send_invitation_email.join(", ");
                }
                else {
                    $("#closeSuggestion").trigger('click');
                    loadRestaurants();
                    $rootScope.alert('alert-success', $filter('i18n')('suggestion_created'));
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

    $scope.manageSuggestion = function(restaurant, suggestion, day, accept) {
        if (suggestion.processing) {
            return;
        }

        var action;
        if (accept) {
            action = 'accept';
        }
        else {
            action = 'cancel';
        }

        // Execute the operation
        suggestion.processing = true;
        $http.post('api/1.0/restaurants/' + restaurant.id + '/suggestions/' + suggestion.id, {
            action: action
        }).success(function(result) {
            // Check the result
            if (typeof result !== 'object' || result.status !== 'ok') {
                // Too old
                if (result.status == 'too_old') {
                    $rootScope.alert('alert-info', $filter('i18n')('suggestion_accept_gone'));
                }
                // Failed
                else {
                    $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
                    console.error("Failed to accept/cancel, got response:");
                    console.error(result);
                }
                suggestion.processing = false;
            }
            // OK
            else {
                // Canceled and deleted (last one out)
                if (result.suggestionDeleted) {
                    deleteSuggestion(restaurant, suggestion, day);
                    $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_canceled_and_deleted'));
                }
                // Accepted or canceled (not last one out)
                else {
                    updateSuggestion(restaurant, result.suggestion, day);
                    if (accept) {
                        $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_accepted'));
                    }
                    else {
                        $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_canceled'));
                    }
                }
            }
        })
        .error(function(response, httpCode) {
            suggestion.processing = false;
            $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
            console.error("Failed to accept/cancel: " + httpCode.toString() + ", " + response);
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