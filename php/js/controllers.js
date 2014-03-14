'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('AcceptSuggestionController', ['$http', '$filter', '$rootScope', '$state', '$location', function($http, $filter, $rootScope, $state, $location) {
    if (typeof $location.search().hash != 'undefined') {
        $http.post('api/1.0/suggestion?hash=' + $location.search().hash).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
            }
            if (result.status == 'deleted') {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-warning', $filter('i18n')('suggestion_been_deleted'));
            }
            else if (result.status == 'too_old') {
                $state.go("Navigation.Menu", {day: result.weekDay});
                $rootScope.alert('alert-info', $filter('i18n')('suggestion_accept_gone'));
            }
            else if (result.status == 'ok') {
                $state.go("Navigation.Menu", {day: result.weekDay});
                $rootScope.alert('alert-success', $filter('i18n')('suggestion_accept_succeeded')
                    + ', ' + result.restaurant + ', '
                    + $filter('lowercase')($rootScope.getWeekDayText(result.weekDay)) + ' ' + result.time);
            }
            else {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
            }
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
        $state.go("Navigation.Menu", {day: day});
    };

    $rootScope.today = ((new Date().getDay() + 6) % 7) + 1;
    $scope.tomorrow = $rootScope.today + 1;
    $scope.restaurantsEmptied = false;
    $scope.maxDay = 7;
    $scope.hasData = true;
    $rootScope.weekDay;

    // Make remaining days array for navbar
    $scope.remainingDays = [];
    for (var i = $rootScope.today + 2; i <= $scope.maxDay; i++) {
        $scope.remainingDays.push(i);
    }

    // Makes navbar hide when menu link clicked in xs-devices
    $(".navbar").on("click", "a", null, function () {
        if ($rootScope.widthClass === 'xs')
            $(".navbar-collapse").collapse('hide');
    });
}])

.controller('LoginController', ['$scope', '$rootScope', '$http', function($scope, $rootScope, $http) {
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

.controller('registerController', ['$scope', '$rootScope', '$http', function($scope, $rootScope, $http) {
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

.controller('MenuController', ['$scope', '$rootScope', '$window', '$location', '$http', '$state', '$filter', 'Restaurants', '$stateParams', function($scope, $rootScope, $window, $location, $http, $state, $filter, Restaurants, $stateParams) {

    $rootScope.weekDay = $stateParams.day;
    if (!$rootScope.weekDay || $rootScope.weekDay < $rootScope.today || $rootScope.weekDay > 7) {
        $state.go("Navigation.Menu", {day: $rootScope.today});
    }

    $rootScope.title = "Menu";
    $rootScope.restaurants = [];
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
    $scope.loadRestaurants = function() {
        var restaurants = Restaurants.query(null, function() {
            $rootScope.restaurants = restaurants;
            $scope.makeRestaurantGrid();
        });
    };
    $scope.makeRestaurantGrid = function() {
        $scope.restaurantGrid = [];
        for (var day = $rootScope.today; day <= 7; day++) {
            $scope.restaurantGrid[day] = $scope.getRestaurantGrid(day);
        }
    };
    $scope.getRestaurantGrid = function(day) {
        var grid = [];

        // Sort open first
        $rootScope.restaurants.sort(function compareOpenFirst(a, b) {
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
        for (var i = 0, row = 0, c = 0; i < $rootScope.restaurants.length; i++) {
            if (typeof grid[row] != 'object')
                grid[row] = [];

            // if (day >= 6 && $rootScope.restaurants[i].openingHours[day].closed)
            //     continue;

            grid[row].push($rootScope.restaurants[i]);
            c++;

            if (c % $rootScope.columns == 0)
                row++;
        }
        return grid;
    };
    $scope.loadRestaurants();

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
                $rootScope.refreshCurrentUser(function() {
                    // Canceled and deleted (last one out)
                    if (result.suggestionDeleted) {
                        $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_canceled_and_deleted'));
                    }
                    // Accepted or canceled (not last one out)
                    else {
                        if (accept) {
                            $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_accepted'));
                        }
                        else {
                            $rootScope.alert('alert-success', $filter('i18n')('suggestion_manage_canceled'));
                        }
                    }
                });
            }
        })
        .error(function(response, httpCode) {
            suggestion.processing = false;
            $rootScope.operationFailed(httpCode, 'suggestion_accept_failed');
        });
    };
}])



.controller('AccountSettingsController', ['$scope', '$rootScope', '$state', '$filter', '$http', '$location', '$anchorScroll', function($scope, $rootScope, $state, $filter, $http, $location, $anchorScroll) {

    $rootScope.refreshCurrentUserAndStopLiveView(function() {
        $("#accountSettingsModal").modal();

        $('#accountSettingsModal').on('hidden.bs.modal', function () {
            $rootScope.startLiveView();
            $state.go("^");
        });
    });
    $scope.resetPassword = function() {
        $scope.password = {
            old: '',
            new: '',
            repeat: ''
        };
    };

    $scope.saveProcess = false;
    $scope.removingAccount = false;
    $scope.resetPassword();

    $scope.save = function() {
        if (!$scope.validateForm()) {
            return;
        }
        $scope.saveProcess = true;
        $scope.modalAlert('', '');

        $http.post('api/1.0/user', {
            password: $scope.password,
            suggestion: $rootScope.currentUser.notification_settings.suggestion,
            group: $rootScope.currentUser.notification_settings.group
        }).success(function(result) {
            // Fail
            if (typeof result != 'object' || result.status == undefined) {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'));
            }
            else if (result.status == 'no_old_password') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('account_give_old_password'));
            }
            else if (result.status == 'no_new_password') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('account_give_new_password'));
            }
            else if (result.status == 'passwords_dont_match') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('account_passwords_dont_match'));
            }
            else if (result.status == 'wrong_password') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('account_wrong_password'));
            }
            else if (result.status == 'weak_password') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('account_weak_password'));
            }
            // Success
            else if (result.status == 'ok') {
                $scope.resetPassword();
                $scope.saveProcess = false;
                $rootScope.alert('alert-success', $filter('i18n')('account_save_succeeded'));
                $("#accountSettingsModal").modal('hide');
                console.log("Account settings saved");
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'))
            }
        }).error(function(response, code) {
            $scope.saveProcess = false;
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

    $scope.removeAccount = function() {
        $http.delete('/api/1.0/user').success(function(result) {
            // Fail
            if (typeof result != 'object' || result.status != 'ok') {
                $scope.resetForm(false);
                $scope.modalAlert('alert-danger', $filter('i18n')('account_remove_failed'));
            }
            // Ok
            else {
                $rootScope.refreshCurrentUser(function() {
                    $rootScope.alert('alert-success', $filter('i18n')('account_remove_success'));
                    $("#accountSettingsModal").modal('hide');
                    console.log("Account removed");
                });
            }
        }).error(function(response, code) {
            $scope.resetForm(false);
            $scope.modalAlert('alert-danger', $filter('i18n')('account_remove_failed'));
        });
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('modal');
            $anchorScroll();
        }
    };
}])



.controller('GroupSettingsController', ['$scope', '$rootScope', '$state', '$filter', '$http', '$location', '$anchorScroll', function($scope, $rootScope, $state, $filter, $http, $location, $anchorScroll) {
    
    $rootScope.refreshCurrentUserAndStopLiveView(function() {
        $("#groupSettingsModal").modal();

        $('#groupSettingsModal').on('hidden.bs.modal', function () {
            $rootScope.startLiveView();
            $state.go("^");
        });
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
                console.log("Joined existing member to group");
            }
            else if (result.status == 'invited_new') {
                group.addMemberOpened = false;
                group.addMemberSaveProcess = false;
                group.newMemberEmail = '';
                $scope.modalAlert('alert-success', $filter('i18n')('group_add_member_success_invited_new'));
                console.log("Invited new member to group");
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_member_failed'));
            }
        }).error(function(response, httpCode) {
            group.addMemberSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_add_member_failed', $scope.modalAlert);
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
                console.log("Group name saved");
                $rootScope.refreshCurrentUser(function() {
                    $scope.modalAlert('', '');
                });
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.editNameSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_edit_failed'))
            }
        }).error(function(response, httpCode) {
            group.editNameSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_edit_failed', $scope.modalAlert);
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
                console.log("Removed member from group");
                $rootScope.refreshCurrentUser(function() {
                    $scope.modalAlert('', '');
                });
            }
            else if (result.status == 'removed_yourself') {
                for (var i = 0; i < $rootScope.currentUser.groups.length; i++) {
                    if ($rootScope.currentUser.groups[i].id == group.id) {
                        $rootScope.currentUser.groups.splice(i, 1);
                        break;
                    }
                }
                if (result.last_member) {
                    console.log("Removed yourself from group + whole group removed");
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself_group_removed'));
                }
                else {
                    console.log("Removed yourself from group");
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself'));
                }
                $rootScope.refreshCurrentUser();
            }
            else {
                console.error("Unknown response");
                console.error(result);
                member.deleteSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
            }
        }).error(function(response, httpCode) {
            member.deleteSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_member_delete_failed', $scope.modalAlert);
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
                console.log("Created new group");
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
        if ($rootScope.weekDay <= $rootScope.today) {
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
            day: $rootScope.weekDay - 1,
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