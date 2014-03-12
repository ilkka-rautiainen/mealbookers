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


.controller('NavigationController', ['$scope', '$rootScope', '$location', '$state', function($scope, $rootScope, $location, $state) {

    $scope.openAccountSettings = function() {
        $state.go("Navigation.Menu.AccountSettings");
    }
    $scope.openGroupSettings = function() {
        $state.go("Navigation.Menu.GroupSettings");
    }

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

.controller('LoginController', ['$scope', '$rootScope', '$http', '$state', function($scope, $rootScope, $http, $state) {

    $("#logInModal").modal();

    $('#logInModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $scope.login = {
        email: "",
        password: "",
        remember: false
    };
    $scope.processForm = function() {
        $http.post('api/1.0/user/login', $scope.login)
            .success(function(response) {
                $scope.response = response;
                
                if (response.status == 'ok') {
                    //$rootScope.refreshCurrentUser();
                    console.log("OK");
                }
                
            });
    };

}])

.controller('RegisterController', ['$scope', '$rootScope', '$http', '$state', function($scope, $rootScope, $http, $state) {

    $("#registerModal").modal();

    $('#registerModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $scope.register = {
        email: "",
        password: "",
        firstName: "",
        lastName: "",
    };
    $scope.processRegister = function() {
        $http.post('api/1.0/user/registerUser', $scope.register)
            .success(function(response) {
                $scope.response = response;
                if (response.response == 'ok')
                    console.log("OK");
            });
    };

}])

.controller('MenuController', ['$scope', '$rootScope', '$window', '$location', '$http', '$state', '$filter', 'Restaurants', function($scope, $rootScope, $window, $location, $http, $state, $filter, Restaurants) {

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
        $rootScope.alert($rootScope.stateData.message.type, $rootScope.stateData.message.message);
    }
    if ($rootScope.stateData !== undefined && $rootScope.stateData.day !== undefined) {
        $scope.changeDay($rootScope.stateData.day);
    }

    /**
     * Load restaurants
     */
    $scope.loadRestaurants = function() {
        var restaurants = Restaurants.query(null, function() {
            $scope.restaurants = restaurants;
            $scope.makeRestaurantGrid();
        });
    };
    $scope.makeRestaurantGrid = function() {
        $scope.restaurantGrid = [];
        for (var day = $scope.today; day <= 7; day++) {
            $scope.restaurantGrid[day] = $scope.getRestaurantGrid(day);
        }
    };
    $scope.getRestaurantGrid = function(day) {
        var grid = [];

        // Sort open first
        $scope.restaurants.sort(function compareOpenFirst(a, b) {
            if (a.openingHours[day].closed && !b.openingHours[day].closed)
                return 1;
            if (!a.openingHours[day].closed && b.openingHours[day].closed)
                return -1;
            if (a.order < b.order)
                return -1;
            if (a.order > b.order)
                return 1;
            return 0;
        });

        // Make the grid
        for (var i = 0, row = 0, c = 0; i < $scope.restaurants.length; i++) {
            if (typeof grid[row] != 'object')
                grid[row] = [];

            // if (day >= 6 && $scope.restaurants[i].openingHours[day].closed)
            //     continue;

            grid[row].push($scope.restaurants[i]);
            c++;

            if (c % $rootScope.columns == 0)
                row++;
        }
        return grid;
    };
    $scope.loadRestaurants();


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
        $scope.makeRestaurantGrid();
    });
    
    /**
     * Suggest a restaurant and time
     */
    $scope.openSuggestion = function(restaurant) {
        $scope.suggestRestaurant = restaurant;
        $state.go(".Suggestion");
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
                if (result.status == 'not_manageable') {
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
}])



.controller('AccountSettingsController', ['$scope', '$rootScope', '$state', '$filter', '$http', '$location', '$anchorScroll', function($scope, $rootScope, $state, $filter, $http, $location, $anchorScroll) {

    $("#accountSettingsModal").modal();
    $('#accountSettingsModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $scope.setSaveButtonState = function(state) {
        if (state == 'normal') {
            $scope.submitButtonText = $filter('i18n')('save');
            $scope.submitButtonDisabled = false;
        }
        else if (state == 'busy') {
            $scope.submitButtonText = $filter('i18n')('saving');
            $scope.submitButtonDisabled = true;
        }
    }

    // GENERAL SETTINGS
    $scope.resetForm = function(resetData) {
        if (resetData) {
            $scope.password = {
                old: '',
                new: '',
                repeat: ''
            };
        }
        $scope.generalSettingsMessage = {
            type: '',
            message: ''
        };
        $scope.setSaveButtonState('normal');
    };

    $scope.removingAccount = false;

    $scope.resetForm(true);

    $scope.save = function() {
        if (!$scope.validateForm()) {
            return;
        }
        $scope.modalAlert('alert-info', $filter('i18n')('account_saving'));
        $scope.setSaveButtonState('busy');

        $http.post('api/1.0/user', {
            password: $scope.password
        }).success(function(result) {
            // Fail
            if (typeof result != 'object' || result.status == undefined) {
                $scope.resetForm(false);
                $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'));
            }
            else if (result.status == 'no_old_password') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-warning', $filter('i18n')('account_give_old_password'));
            }
            else if (result.status == 'no_new_password') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-warning', $filter('i18n')('account_give_new_password'));
            }
            else if (result.status == 'passwords_dont_match') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-warning', $filter('i18n')('account_passwords_dont_match'));
            }
            else if (result.status == 'wrong_password') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-warning', $filter('i18n')('account_wrong_password'));
            }
            else if (result.status == 'weak_password') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-warning', $filter('i18n')('account_weak_password'));
            }
            // Success
            else {
                $scope.resetForm(true);
                $scope.modalAlert('alert-success', $filter('i18n')('account_save_succeeded'));
            }
        }).error(function(response, code) {
            $scope.resetForm(false);
            $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'));
        });
    };

    $scope.validateForm = function() {
        if ($scope.password.new || $scope.password.repeat) {
            if (!$scope.password.old) {
                $scope.modalAlert('alert-warning', $filter('i18n')('account_give_old_password'));
                return false;
            }
            else if ($scope.password.new != $scope.password.repeat) {
                $scope.modalAlert('alert-warning', $filter('i18n')('account_passwords_dont_match'));
                return false;
            }
        }
        else if ($scope.password.old) {
            $scope.modalAlert('alert-warning', $filter('i18n')('account_give_new_password'));
            return false;
        }

        return true;
    };

    /**
     * @todo implement logout after successful removal
     */
    $scope.removeAccount = function() {
        $http.delete('/api/1.0/user').success(function(result) {
            // Fail
            if (typeof result != 'object' || result.status != 'ok') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-danger', $filter('i18n')('account_remove_failed'));
            }
            else {
                $rootScope.alert('alert-success', $filter('i18n')('account_remove_success'));
                $("#accountSettingsModal").modal('hide');
            }
        }).error(function(response, code) {
            $scope.resetForm(false);
            $scope.modalAlert('alert-danger', $filter('i18n')('account_remove_failed'));
        });
    };

    $scope.modalAlert = function(type, message) {
        $scope.generalSettingsMessage.type = type;
        $scope.generalSettingsMessage.message = message;
        $location.hash('modal');
        $anchorScroll();
    };
}])



.controller('GroupSettingsController', ['$scope', '$rootScope', '$state', '$filter', '$http', '$location', '$anchorScroll', function($scope, $rootScope, $state, $filter, $http, $location, $anchorScroll) {

    $("#groupSettingsModal").modal();
    $('#groupSettingsModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $scope.groupSettingsMessage = {
        type: '',
        message: ''
    };
    $scope.modalAlert = function(type, message) {
        $scope.groupSettingsMessage.type = type;
        $scope.groupSettingsMessage.message = message;
        if (message.length) {
            $location.hash('modal');
            $anchorScroll();
        }
    };

    $scope.openAddMember = function(group) {
        group.addMemberOpened = true;
        $scope.modalAlert('', '');
    };

    $scope.closeAddMember = function(group) {
        group.addMemberOpened = false;
        group.newMemberEmail = '';
    };

    $scope.addMemberToGroup = function(group) {
        group.addMemberSaveProcess = true;
        $http.post('/api/1.0/user/groups/' + group.id + '/members', {
            email_address: (group.newMemberEmail) ? group.newMemberEmail : ''
        }).success(function(result) {
            if (typeof result != 'object' || result.status == 'undefined' || result.status == 'failed') {
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_member_failed'));
            }
            else if (result.status == 'invalid_email') {
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('group_add_member_failed_invalid_email'));
            }
            else if (result.status == 'already_member') {
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('group_add_member_already_member'));
            }
            else if (result.status == 'joined_existing') {
                $rootScope.refreshCurrentUser();
                $scope.modalAlert('alert-success', $filter('i18n')('group_add_member_success_joined_existing'));
            }
            else if (result.status == 'invited_new') {
                group.addMemberOpened = false;
                group.addMemberSaveProcess = false;
                group.newMemberEmail = '';
                $scope.modalAlert('alert-success', $filter('i18n')('group_add_member_success_invited_new'));
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_member_failed'))
            }
        }).error(function(response) {
            group.addMemberSaveProcess = false;
            $scope.modalAlert('alert-danger', $filter('i18n')('group_add_member_failed'))
        });
    };

    $scope.saveGroupName = function(group) {
        group.editNameSaveProcess = true;
        $http.post('/api/1.0/user/groups/' + group.id, {
            name: group.name
        }).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                group.editNameSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_edit_failed'));
            }
            else if (result.status == 'invalid_name') {
                group.editNameSaveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('group_edit_failed_invalid_name'));
            }
            else if (result.status == 'ok') {
                $rootScope.refreshCurrentUser(function() {
                    group.editNameProcess = false;
                    group.editNameSaveProcess = false;
                    $scope.modalAlert('', '');
                });
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.editNameSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_edit_failed'))
            }
        }).error(function(response) {
            group.editNameSaveProcess = false;
            $scope.modalAlert('alert-danger', $filter('i18n')('group_edit_failed'))
        });
    }

    $scope.deleteGroupMember = function(group, member) {
        member.deleteSaveProcess = true;
        $http.delete('/api/1.0/user/groups/' + group.id + '/members/' + member.id).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                member.deleteSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
                return;
            }
            else if (result.status == 'ok') {
                for (var i = 0; i < group.members.length; i++) {
                    if (group.members[i].id == member.id) {
                        group.members.splice(i, 1);
                        break;
                    }
                }
                $scope.modalAlert('', '');
            }
            else if (result.status == 'removed_yourself') {
                for (var i = 0; i < $rootScope.currentUser.groups.length; i++) {
                    if ($rootScope.currentUser.groups[i].id == group.id) {
                        $rootScope.currentUser.groups.splice(i, 1);
                        break;
                    }
                }
                if (result.last_member) {
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself_group_removed'));
                }
                else {
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself'));
                }
                $rootScope.refreshSuggestions();
            }
            else {
                console.error("Unknown response");
                console.error(result);
                member.deleteSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
            }
        }).error(function(response) {
            member.deleteSaveProcess = false;
            $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
        });
    }

    $scope.newGroup = {
        open: false,
        name: '',
        saving: false
    };

    $scope.openAddGroup = function() {
        $scope.newGroup.open = true;
        $scope.modalAlert('', '');
    };

    $scope.closeAddGroup = function() {
        $scope.newGroup.open = false;
        $scope.newGroup.name = '';
    };

    $scope.addGroup = function() {
        $scope.newGroup.saving = true;
        $http.post('/api/1.0/user/groups', {
            name: $scope.newGroup.name
        }).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $scope.newGroup.saving = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_group_failed'));
            }
            else if (result.status == 'ok') {
                $rootScope.refreshCurrentUser(function () {
                    $scope.newGroup.open = false;
                    $scope.newGroup.saving = false;
                    $scope.newGroup.name = '';
                });
                $scope.modalAlert('', '');
            }
            else if (result.status == 'invalid_name') {
                $scope.newGroup.saving = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_group_failed_invalid_name'));
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.newGroup.saving = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_group_failed'));
            }
        }).error(function(response) {
            $scope.newGroup.saving = false;
            $scope.modalAlert('alert-danger', $filter('i18n')('group_add_group_failed'));
        });
    };

}])



.controller('SuggestionController', ['$scope', '$rootScope', '$state', '$filter', 'Suggestions', function($scope, $rootScope, $state, $filter, Suggestions) {

    // No direct requests to this controller
    if (!$scope.suggestRestaurant) {
        return $state.go(".^");
    }

    $scope.suggestTime = "";
    $scope.suggestionMessage.type = '';
    $scope.suggestionMessage.message = '';
    $("#suggestionModal .group").removeClass("group-selected");
    $("#suggestionModal .member").removeClass("member-selected");
    $("#suggestionModal").modal();
    $('#suggestionModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });
    $('#suggestionModal').on('shown.bs.modal', function () {
        if ($rootScope.widthClass != 'xs') {
            $("#suggestionModal input.suggest-time").focus();
        }
    });

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
            if (suggestionDate.getTime() + $rootScope.currentUser.config.limits.suggestion_create_in_past_time * 1000
                < new Date().getTime()) {
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
                    $scope.loadRestaurants();
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