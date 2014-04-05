'use strict';

/* Controllers */

angular.module('Mealbookers.controllers', [])


.controller('AcceptSuggestionController', ['$http', '$filter', '$rootScope', '$state', '$stateParams', function($http, $filter, $rootScope, $state, $stateParams) {
    if ($stateParams.token) {
        $http.post('api/1.0/suggestion/' + $stateParams.token).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
            }
            if (result.status == 'deleted') {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-warning', $filter('i18n')('suggestion_been_deleted'));
            }
            else if (result.status == 'not_found') {
                $state.go("Navigation.Menu", {day: $rootScope.today});
                $rootScope.alert('alert-warning', $filter('i18n')('suggestion_accepting_token_not_found'));
            }
            else if (result.status == 'too_old') {
                $state.go("Navigation.Menu", {day: result.weekDay});
                $rootScope.alert('alert-info', $filter('i18n')('suggestion_accept_gone'));
            }
            else if (result.status == 'wrong_user') {
                $rootScope.logOut(false);
                $state.go("Navigation.Menu.Login", {day: $rootScope.today});
                $rootScope.modalAlert('alert-info', $filter('i18n')('suggestion_accept_wrong_user'));
                $rootScope.postLoginState = {
                    name: "Navigation.AcceptSuggestion", 
                    stateParams: {
                        token: $stateParams.token
                    }
                };
            }
            else if (result.status == 'not_logged_in') {
                $state.go("Navigation.Menu.Login", {day: $rootScope.today});
                $rootScope.modalAlert('alert-info', $filter('i18n')('suggestion_accept_not_logged_in'));
                $rootScope.postLoginState = {
                    name: "Navigation.AcceptSuggestion", 
                    stateParams: {
                        token: $stateParams.token
                    }
                };
            }
            else if (result && result.status == 'ok') {
                $state.go("Navigation.Menu", {day: result.weekDay});
                $rootScope.refreshCurrentUser(function() {
                    $rootScope.alert('alert-success', $filter('i18n')('suggestion_accept_succeeded')
                        + ', ' + result.restaurant + ', '
                        + $filter('lowercase')($rootScope.getWeekDayText(result.weekDay)) + ' ' + result.time);
                });
            }
            else {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
            }
        });
    }
    else {
        $state.go("Navigation.Menu");
        $rootScope.alert('alert-danger', $filter('i18n')('suggestion_accept_failed'));
    }
}])


.controller('VerifyEmailController', ['$http', '$filter', '$rootScope', '$state', '$stateParams', function($http, $filter, $rootScope, $state, $stateParams) {
    if ($stateParams.token) {
        $http.post('api/1.0/user/email/verify/' + $stateParams.token).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('register_email_verify_failed'));
            }
            else if (result.status == 'not_found') {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-warning', $filter('i18n')('register_email_verify_token_not_found'));
            }
            else if (result && result.status == 'ok') {
                $state.go("Navigation.Menu", {day: result.weekDay});
                $rootScope.refreshCurrentUser(function() {
                    $rootScope.alert('alert-success', $filter('i18n')('register_email_verify_succeeded'));
                });
            }
            else {
                $state.go("Navigation.Menu");
                $rootScope.alert('alert-danger', $filter('i18n')('register_email_verify_failed'));
            }
        });
    }
    else {
        $state.go("Navigation.Menu");
        $rootScope.alert('alert-danger', $filter('i18n')('register_email_verify_failed'));
    }
}])


.controller('NavigationController', ['$scope', '$rootScope', '$location', '$state', '$http', function($scope, $rootScope, $location, $state, $http) {

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
    $scope.maxDay = 7;
    $scope.hasData = true;
    $rootScope.weekDay;

    // Make remaining days array for navbar
    $scope.remainingDays = [];
    for (var i = $rootScope.today + 2; i <= $scope.maxDay; i++) {
        $scope.remainingDays.push(i);
    }

    // Makes navbar hide when menu link clicked in xs-devices
    $(".navbar").on("click", "a:not([data-toggle=dropdown])", null, function () {
        if ($rootScope.widthClass === 'xs')
            $(".navbar-collapse").collapse('hide');
    });

    $scope.languageChangeProcess = false;
    $scope.changeLanguage = function(lang) {
        if ($rootScope.currentUser.language == lang)
            return;

        if ($rootScope.currentUser.role != 'guest') {
            $scope.languageChangeProcess = true;
            $rootScope.currentUser.language = lang;
            $http.post('api/1.0/user/language', {
                language: $rootScope.currentUser.language
            }).success(function() {
                $rootScope.refreshLocalization(function() {
                    $scope.languageChangeProcess = false;
                });
            }).error(function() {
                $scope.languageChangeProcess = false;
            });
        }
        else {
            $scope.languageChangeProcess = true;
            $rootScope.currentUser.language = lang;
            $rootScope.refreshLocalization(function() {
                $scope.languageChangeProcess = false;
            });
        }
    };
}])

.controller('LoginController', ['$scope', '$rootScope', '$http', '$state', '$log', '$filter', '$location', '$anchorScroll', function($scope, $rootScope, $http, $state, $log, $filter, $location, $anchorScroll) {

    $("#logInModal").modal();
    $('#logInModal').on('hidden.bs.modal', function () {
        delete $rootScope.postLoginState;
        $state.go("^");
    });

    $('#logInModal').on('shown.bs.modal', function () {
        $scope.$broadcast('modalOpened');
    });

    $scope.login = {
        email: "",
        password: "",
        remember: true
    };

    $scope.processForm = function() {
        $scope.loginProcess = true;
        $http.post('api/1.0/user/login', $scope.login)
            .success(function(result) {
                $scope.loginProcess = false;
                if (result && result.status == 'ok') {
                    $log.info("Logged in");
                    $rootScope.refreshCurrentUser(function() {
                        $("#logInModal").modal('hide');

                        var ready = function() {
                            if ($rootScope.postLoginState) {
                                $state.go($rootScope.postLoginState.name, $rootScope.postLoginState.stateParams);
                                delete $rootScope.postLoginState;
                            }
                            else {
                                $rootScope.alert('alert-success', $filter('i18n')('logged_in'));
                            }
                        };

                        if ($rootScope.currentUser.language != $rootScope.localizationCurrentLanguage) {
                            $rootScope.refreshLocalization(ready);
                        }
                        else {
                            ready();
                        }
                    });
                }
                else {
                    console.error("Unknown response");
                    console.error(result);
                    $scope.modalAlert('alert-danger', $filter('i18n')('log_in_failed'));
                }
            }).error(function(response, httpCode, headers) {
                $scope.loginProcess = false;
                $rootScope.operationFailed(httpCode, 'log_in_failed', $scope.modalAlert, headers());
            });
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('login-modal');
            $anchorScroll();
        }
    };

}])

.controller('LoginForgotPasswordController', ['$scope', '$rootScope', '$http', '$state', '$log', '$filter', '$location', '$anchorScroll', function($scope, $rootScope, $http, $state, $log, $filter, $location, $anchorScroll) {

    $("#forgot-password-modal").modal();
    $('#forgot-password-modal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $('#forgot-password-modal').on('shown.bs.modal', function () {
        $scope.$broadcast('modalOpened');
    });


    $scope.forgot = {
        email: ""
    };

    $scope.forgotPasswordProcess = false;
    $scope.forgotPasswordSaveProcess = false;

    $scope.closeForgotPassword = function() {
        $scope.forgotPasswordProcess = false;
        $scope.forgotPasswordSaveProcess = false;
    };

    $scope.processForm = function() {
        $scope.modalAlert('', '');
        $scope.sendProcess = true;
        $http.post('api/1.0/user/login/forgot', $scope.forgot)
            .success(function(result) {
                $scope.sendProcess = false;
                if (result && result.status == 'ok') {
                    $log.info("Password request sent");
                    $("#forgot-password-modal").modal('hide');
                    $rootScope.alert('alert-success', $filter('i18n')('forgot_password_succeeded'));
                }
                else if (result.status == 'invalid_email') {
                    $scope.modalAlert('alert-warning', $filter('i18n')('forgot_password_invalid_email'));
                }
                else {
                    console.error("Unknown response");
                    console.error(result);
                    $scope.modalAlert('alert-danger', $filter('i18n')('forgot_password_failed'));
                }
            }).error(function(response, httpCode, headers) {
                $scope.sendProcess = false;
                $rootScope.operationFailed(httpCode, 'forgot_password_failed', $scope.modalAlert, headers());
            });
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('forgot-password-modal');
            $anchorScroll();
        }
    };

}])

.controller('LoginCreateNewPasswordController', ['$scope', '$rootScope', '$http', '$state', '$log', '$filter', '$location', '$anchorScroll', '$stateParams', function($scope, $rootScope, $http, $state, $log, $filter, $location, $anchorScroll, $stateParams) {

    $http.get('api/1.0/user/login/forgot/' + $stateParams.token).success(function(result) {
        if (result.status == 'ok' && result.user.role != 'guest') {
            $scope.user = result.user;
            $("#create-new-password-modal").modal();
            $('#create-new-password-modal').on('hidden.bs.modal', function () {
                $state.go("^");
            });

            $('#create-new-password-modal').on('shown.bs.modal', function () {
                $scope.$broadcast('modalOpened');
            });
            $rootScope.logOut();
        }
        else {
            $state.go("^");
            $rootScope.alert('alert-danger', $filter('i18n')('new_password_fetch_failed'));
        }
    }).error(function(response, httpCode, headers) {
        $state.go("^");
        $rootScope.operationFailed(httpCode, 'new_password_fetch_failed', null, headers());
    });

    $scope.password = {
        new: '',
        repeat: ''
    };

    $scope.processForm = function() {
        if (!$scope.validateForm())
            return;
        $scope.modalAlert('', '');
        $scope.sendProcess = true;
        $http.post('api/1.0/user/login/forgot/new/' + $stateParams.token, $scope.password)
            .success(function(result) {
                $scope.sendProcess = false;
                if (result && result.status == 'ok') {
                    $log.info("Password request sent");
                    $("#create-new-password-modal").modal('hide');
                    $rootScope.alert('alert-success', $filter('i18n')('new_password_succeeded'));
                }
                else if (result.status == 'passwords_dont_match') {
                    $scope.modalAlert('alert-warning', $filter('i18n')('new_password_passwords_dont_match'));
                }
                else if (result.status == 'weak_password') {
                    $scope.modalAlert('alert-warning', $filter('i18n')('password_criteria'));
                }
                else {
                    console.error("Unknown response");
                    console.error(result);
                    $scope.modalAlert('alert-danger', $filter('i18n')('new_password_failed'));
                }
            }).error(function(response, httpCode, headers) {
                $scope.sendProcess = false;
                $rootScope.operationFailed(httpCode, 'new_password_failed', $scope.modalAlert, headers());
            });
    };

    $scope.validateForm = function() {
        if ($scope.password.new != $scope.password.repeat) {
            $scope.modalAlert('alert-warning', $filter('i18n')('new_password_passwords_dont_match'));
            return false;
        }

        return true;
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('create-new-password-modal');
            $anchorScroll();
        }
    };

}])

.controller('RegisterController', ['$scope', '$rootScope', '$http', '$state', '$filter', '$location', '$anchorScroll', '$log', function($scope, $rootScope, $http, $state, $filter, $location, $anchorScroll, $log) {

    // Load eventual invitation
    var invitation = $location.search().invitation;
    if (invitation) {
        $("#register-modal").modal('hide');
        $http.get('api/1.0/invitation/' + invitation).success(function(result) {
            if (result && result.status == 'ok') {
                $scope.register.email = result.invitation.email_address;
                $scope.group_name = result.invitation.group_name;
                $scope.register.invitation_code = invitation;
            }
            else {
                $log.error("Unknown response");
                $log.error(result);
                $scope.modalAlert('alert-danger', $filter('i18n')('register_invitation_fetching_failed'));
            }
            $("#register-modal").modal('show');
        }).error(function(response, httpCode, headers) {
            $("#register-modal").modal('show');
            $rootScope.operationFailed(httpCode, 'register_invitation_fetching_failed', $scope.modalAlert, headers());
        });
    }
    else {
        $("#register-modal").modal();
    }


    $('#register-modal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $('#register-modal').on('shown.bs.modal', function () {
        $scope.$broadcast('modalOpened');
    });

    $scope.register = {
        email: "",
        invitation_code: "",
        password: "",
        password_repeat: "",
        first_name: "",
        last_name: "",
        language: $rootScope.currentUser.language
    };

    $scope.save = function() {
        if (!$scope.validateForm())
            return;
        $scope.registerSaveProcess = true;
        $scope.modalAlert('', '');

        $http.post('api/1.0/user/register', $scope.register)
            .success(function(result) {
                if (result && result.status == 'ok') {
                    $log.info("Registration done");
                    $rootScope.refreshCurrentUser(function() {
                        $("#register-modal").modal('hide');
                        $rootScope.alert('alert-success', $filter('i18n')('register_succeeded'));
                    });
                }
                else {
                    console.error("Unknown response");
                    console.error(result);
                    $scope.registerSaveProcess = false;
                    $scope.modalAlert('alert-danger', $filter('i18n')('register_failed'));
                }
            }).error(function(response, httpCode, headers) {
                $scope.registerSaveProcess = false;
                $rootScope.operationFailed(httpCode, 'register_failed', $scope.modalAlert, headers());
            });
    };

    $scope.validateForm = function() {
        if ($scope.register.password != $scope.register.password_repeat) {
            $scope.modalAlert('alert-warning', $filter('i18n')('register_failed_409_passwords_dont_match'));
            return false;
        }

        return true;
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('register-modal');
            $anchorScroll();
        }
    };

}])

.controller('MenuController', ['$scope', '$rootScope', '$window', '$location', '$http', '$state', '$filter', '$stateParams', '$log', function($scope, $rootScope, $window, $location, $http, $state, $filter, $stateParams, $log) {

    if ($stateParams.day == 'today') {
        $rootScope.weekDay = $rootScope.today;
    }
    else {
        $rootScope.weekDay = parseInt($stateParams.day);
    }
    if (!$rootScope.weekDay || $rootScope.weekDay < $rootScope.today || $rootScope.weekDay > 7) {
        $state.go("Navigation.Menu", {day: $rootScope.today});
    }

    $scope.restaurantRows = [];
    $scope.suggestTime = "";
    $scope.suggestionMessage = {
        type: '',
        message: ''
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

    $rootScope.$watchCollection('[restaurants, widthClass]', function() {
        $scope.makeRestaurantGrid();
    });

    $scope.getOpeningHoursTooltip = function(restaurant) {
        var openingHours = [];
        for (var i in restaurant.openingHours[$scope.weekDay].all) {
            openingHours.push(
                '<div class="tooltip-row">'
                + $filter('formatOpeningHour')(restaurant.openingHours[$scope.weekDay].all[i])
                + '</div>'
            );
        }
        return openingHours.join("");
    };

    /**
     * Suggest a restaurant and time
     */
    $scope.openSuggestion = function(restaurantId) {
        $state.go(".Suggestion", {restaurantId: restaurantId});
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
            if (result && result.status == 'ok') {
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
            else {
                console.error("Unknown response");
                console.error(result);
                suggestion.processing = false;
                $rootScope.alert('alert-danger', $filter('i18n')('suggestion_manage_failed'));
            }
        })
        .error(function(response, httpCode, headers) {
            suggestion.processing = false;
            $rootScope.operationFailed(httpCode, 'suggestion_manage_failed', null, headers());
        });
    };
}])

.controller('UserManagementController', ['$scope', '$rootScope', '$state', '$timeout', '$filter', '$http', '$location', '$anchorScroll', '$log', function($scope, $rootScope, $state, $timeout, $filter, $http, $location, $anchorScroll, $log) {
    $("#user-management-modal").modal();

    $('#user-management-modal').on('hidden.bs.modal', function () {
        $state.go("^");
    });

    $('#user-management-modal').on('shown.bs.modal', function () {
        $scope.$broadcast('modalOpened');
    });

    $scope.search = {
        user: '',
        group: ''
    };
    $scope.searchProcess = false;
    $scope.results = [];

    $scope.$on("childModalClosed", function() {
        $scope.search();
    });

    $scope.initSearch = function() {
        if ($scope.searchTimeout)
            $timeout.cancel($scope.searchTimeout);
        if ((!$scope.search.user || !$scope.search.user.length)
            && (!$scope.search.group || !$scope.search.group.length)) {
            $scope.results = [];
            $scope.searchProcess = false;
            return;
        }
        $scope.searchProcess = true;
        $scope.searchTimeout = $timeout($scope.search, 300);
    };

    $scope.search = function() {
        $http.get('api/1.0/users', {
            params: {
                user: $scope.search.user,
                group: $scope.search.group
            }
        }).success(function(result) {
            if (result && result.status == 'ok') {
                $scope.searchProcess = false;
                $scope.results = result.results;
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.searchProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('user_management_search_failed'));
            }
        }).error(function(response, httpCode, headers) {
            $scope.searchProcess = false;
            $rootScope.operationFailed(httpCode, 'user_management_search_failed', $scope.modalAlert, headers());
        });
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('users-modal');
            $anchorScroll();
        }
    };

    $scope.openAccountSettingsFor = function(user) {
        $state.go(".AccountSettings", {userId: user.id});
    };

    $scope.openGroupSettingsFor = function(user) {
        $state.go(".GroupSettings", {userId: user.id});
    };
}])

.controller('AccountSettingsController', ['$scope', '$rootScope', '$state', '$filter', '$http', '$location', '$anchorScroll', '$stateParams', '$log', function($scope, $rootScope, $state, $filter, $http, $location, $anchorScroll, $stateParams, $log) {

    // If opened as someone other's settings
    if ($stateParams.userId) {
        // Load user if someone other
        if ($stateParams.userId != $rootScope.currentUser.id) {
            $http.get('api/1.0/user/' + $stateParams.userId).success(function(result) {
                if (result && result.status == 'ok') {
                    $scope.user = result.user;
                }
                else {
                    console.error("Unknown response");
                    console.error(result);
                    $scope.modalAlert('alert-danger', $filter('i18n')('account_settings_user_fetch_failed'));
                }
            }).error(function(response, httpCode, headers) {
                $rootScope.operationFailed(httpCode, 'account_settings_user_fetch_failed', $scope.modalAlert, headers());
            });
            $scope.isCurrentUser = false;
        }
        else {
            $scope.user = $rootScope.currentUser;
            $scope.isCurrentUser = true;
        }
    }
    else {
        $scope.user = $rootScope.currentUser;
        $scope.isCurrentUser = true;
    }

    // Stop live view if current user
    if ($scope.isCurrentUser) {
        $rootScope.refreshCurrentUserAndStopLiveView(function() {
            $("#accountSettingsModal").modal();

            $('#accountSettingsModal').on('hidden.bs.modal', function () {
                $rootScope.startLiveView();
                $state.go("^");
            });
        });
    }
    else {
        $("#accountSettingsModal").modal();

        $('#accountSettingsModal').on('hidden.bs.modal', function () {
            $state.go("^");
        });
    }

    $scope.resetPassword = function() {
        $scope.password = {
            old: '',
            new: '',
            repeat: ''
        };
    };

    $scope.saveProcess = false;
    $scope.languageSaveProcess = false;
    $scope.removingAccount = false;
    $scope.resetPassword();

    $scope.updateLanguage = function() {
        $scope.modalAlert('', '');
        $scope.languageSaveProcess = true;

        var address;
        if ($scope.isCurrentUser)
            address = 'api/1.0/user/language';
        else
            address = 'api/1.0/user/' + $scope.user.id + '/language';

        $http.post(address, {
            language: $scope.user.language
        }).success(function(result) {
            if (result && result.status == 'ok') {
                $log.log("Language changed");
                if ($scope.isCurrentUser) {
                    $rootScope.refreshLocalization(function() {
                        $scope.languageSaveProcess = false;
                    });
                }
                else {
                    $scope.languageSaveProcess = false;
                }
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.languageSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'))
            }
        }).error(function(response, httpCode, headers) {
            $scope.languageSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'account_save_failed', $scope.modalAlert, headers());
        });
    };

    $scope.save = function() {
        if (!$scope.validateForm()) {
            return;
        }
        $scope.saveProcess = true;
        $scope.modalAlert('', '');

        var address;
        if ($scope.isCurrentUser)
            address = 'api/1.0/user';
        else
            address = 'api/1.0/user/' + $scope.user.id;

        $http.post(address, {
            password: $scope.password,
            suggestion: $scope.user.notification_settings.suggestion,
            group: $scope.user.notification_settings.group,
            name: {
                first_name: $scope.user.first_name,
                last_name: $scope.user.last_name
            },
            role: $scope.user.role
        }).success(function(result) {
            if (result && result.status == 'ok') {
                $scope.resetPassword();
                $scope.saveProcess = false;
                $rootScope.alert('alert-success', $filter('i18n')('account_save_succeeded'));
                $("#accountSettingsModal").modal('hide');
                $log.log("Account settings saved");
            }
            else {
                $log.error("Unknown response");
                $log.error(result);
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('account_save_failed'))
            }
        }).error(function(response, httpCode, headers) {
            $scope.saveProcess = false;
            $rootScope.operationFailed(httpCode, 'account_save_failed', $scope.modalAlert, headers());
        });
    };

    $scope.validateForm = function() {
        if ($scope.password.new || $scope.password.repeat) {
            if (!$rootScope.currentUser.role == 'admin' && !$scope.password.old) {
                $scope.modalAlert('alert-warning', $filter('i18n')('account_save_failed_409_no_old_password'));
                return false;
            }
            if ($scope.password.new != $scope.password.repeat) {
                $scope.modalAlert('alert-warning', $filter('i18n')('account_save_failed_409_passwords_dont_match'));
                return false;
            }
        }
        else if ($scope.password.old) {
            $scope.modalAlert('alert-warning', $filter('i18n')('account_save_failed_409_no_new_password'));
            return false;
        }

        return true;
    };

    $scope.removeAccount = function() {
        var address;
        if ($scope.isCurrentUser)
            address = 'api/1.0/user';
        else
            address = 'api/1.0/user/' + $scope.user.id;

        $http.delete(address).success(function(result) {
            if (result && result.status == 'ok') {
                $("#accountSettingsModal").modal('hide');
                if ($scope.isCurrentUser) {
                    $.removeCookie('id');
                    $.removeCookie('check');
                    $.removeCookie('remember');
                    $rootScope.refreshCurrentUser(function() {
                        $rootScope.alert('alert-success', $filter('i18n')('account_remove_success'));
                        console.log("Account removed");
                    });
                }
            }
        }).error(function(response, httpCode, headers) {
            $rootScope.operationFailed(httpCode, 'account_remove_failed', $scope.modalAlert, headers());
        });
    };

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('account-modal');
            $anchorScroll();
        }
    };
}])

.controller('GroupSettingsController', ['$scope', '$rootScope', '$state', '$stateParams', '$filter', '$http', '$location', '$anchorScroll', '$log', function($scope, $rootScope, $state, $stateParams, $filter, $http, $location, $anchorScroll, $log) {

    // Function for loading the user for the scope
    $scope.loadOtherUser = function(done) {
        $http.get('api/1.0/user/' + $stateParams.userId).success(function(result) {
            if (result && result.status == 'ok') {
                $scope.user = result.user;
                $scope.$broadcast("userReady");
                if (typeof done == 'function')
                    done();
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.modalAlert('alert-danger', $filter('i18n')('group_settings_user_fetch_failed'));
            }
        }).error(function(response, httpCode, headers) {
            $rootScope.operationFailed(httpCode, 'group_settings_user_fetch_failed', $scope.modalAlert, headers());
        });
    };

    // If opened as someone other's settings
    if ($stateParams.userId) {
        // Load user if someone other
        if ($stateParams.userId != $rootScope.currentUser.id) {
            $scope.loadOtherUser();
            $scope.isCurrentUser = false;
        }
        else {
            $scope.user = $rootScope.currentUser;
            $scope.isCurrentUser = true;
        }
    }
    else {
        $scope.user = $rootScope.currentUser;
        $scope.isCurrentUser = true;
    }

    // Stop live view if current user
    if ($scope.isCurrentUser) {
        $rootScope.refreshCurrentUserAndStopLiveView(function() {
            $("#groupSettingsModal").modal();

            $("#groupSettingsModal").on('hidden.bs.modal', function () {
                $rootScope.startLiveView();
                $state.go("^");
            });

            $scope.$broadcast("userReady");
        });
    }
    else {
        $("#groupSettingsModal").modal();

        $("#groupSettingsModal").on('hidden.bs.modal', function () {
            $state.go("^");
        });
    }

    // Refresh user
    $scope.refreshUser = function(done) {
        if ($scope.isCurrentUser) {
            $rootScope.refreshCurrentUser(done);
        }
        else {
            $scope.loadOtherUser(done);
        }
    };

    // Construct groups with the user in them as member
    angular.forEach(["userReady","currentUserRefresh"], function(value) {
        $scope.$on(value, function() {
            var groups = angular.copy($scope.user.groups);
            $scope.user.groupsWithMe = [];
            for (var i in groups) {
                groups[i].members.unshift(jQuery.extend({}, $scope.user.me));
                $scope.user.groupsWithMe.push(groups[i]);
            }
        });
    });

    $scope.modalAlert = function(type, message) {
        $scope.modalAlertMessage = {
            type: type,
            message: message
        };
        if (message.length) {
            $location.hash('group-modal');
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

        var address;
        if ($scope.isCurrentUser)
            address = '/api/1.0/user/groups/' + group.id + '/members';
        else
            address = '/api/1.0/user/' + $scope.user.id + '/groups/' + group.id + '/members';

        $http.post(address, {
            email_address: (group.newMemberEmail) ? group.newMemberEmail : ''
        }).success(function(result) {
            if (result && result.status == 'joined_existing') {
                $scope.refreshUser(function() {
                    if (!result.notification_error) {
                        $scope.modalAlert('alert-success', $filter('i18n')('group_add_member_success_joined_existing'));
                    }
                    else {
                        $scope.modalAlert('alert-warning', $filter('i18n')('group_add_member_success_joined_existing_but_notification_error'));
                    }
                    console.log("Joined existing member to group");
                });
            }
            else if (result && result.status == 'invited_new') {
                $scope.refreshUser(function() {
                    $scope.modalAlert('alert-success', $filter('i18n')('group_add_member_success_invited_new'));
                    console.log("Invited new member to group");
                });
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.addMemberSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_settings_invite_member_failed'));
            }
        }).error(function(response, httpCode, headers) {
            group.addMemberSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_settings_invite_member_failed', $scope.modalAlert, headers());
        });
    };

    $scope.saveGroupName = function(group) {
        group.editNameSaveProcess = true;
        $scope.modalAlert('', '');

        var address;
        if ($scope.isCurrentUser)
            address = '/api/1.0/user/groups/' + group.id;
        else
            address = '/api/1.0/user/' + $scope.user.id + '/groups/' + group.id;

        $http.post(address, {
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
            else if (result && result.status == 'ok') {
                console.log("Group name saved");
                $scope.refreshUser();
            }
            else {
                console.error("Unknown response");
                console.error(result);
                group.editNameSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_edit_failed'))
            }
        }).error(function(response, httpCode, headers) {
            group.editNameSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_edit_failed', $scope.modalAlert, headers());
        });
    }

    $scope.deleteGroupMember = function(group, member) {
        member.deleteSaveProcess = true;
        $scope.modalAlert('', '');

        var address;
        if ($scope.isCurrentUser)
            address = '/api/1.0/user/groups/' + group.id + '/members/' + member.id;
        else
            address = '/api/1.0/user/' + $scope.user.id + '/groups/' + group.id + '/members/' + member.id;

        $http.delete(address).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                member.deleteSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
                return;
            }
            else if (result && result.status == 'ok') {
                console.log("Removed member from group");
                $scope.refreshUser(function() {
                    for (var i = 0; i < group.members.length; i++) {
                        if (group.members[i].id == member.id) {
                            group.members.splice(i, 1);
                            break;
                        }
                    }
                });
            }
            else if (result.status == 'removed_himself') {
                for (var i = 0; i < $rootScope.currentUser.groups.length; i++) {
                    if ($rootScope.currentUser.groups[i].id == group.id) {
                        $rootScope.currentUser.groups.splice(i, 1);
                        break;
                    }
                }
                if (result.last_member) {
                    console.log("Removed yourself from group + whole group removed");
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself_group_removed' + (($scope.isCurrentUser) ? '':'_admin')));
                }
                else {
                    console.log("Removed yourself from group");
                    $scope.modalAlert('alert-success', $filter('i18n')('group_member_deleted_yourself' + (($scope.isCurrentUser) ? '':'_admin')));
                }
                $scope.refreshUser();
            }
            else {
                console.error("Unknown response");
                console.error(result);
                member.deleteSaveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_member_delete_failed'));
            }
        }).error(function(response, httpCode, headers) {
            member.deleteSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_member_delete_failed', $scope.modalAlert, headers());
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
        $scope.modalAlert('', '');

        var address;
        if ($scope.isCurrentUser)
            address = '/api/1.0/user/groups';
        else
            address = '/api/1.0/user/' + $scope.user.id + '/groups';

        $http.post(address, {
            name: $scope.newGroup.name
        }).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $scope.newGroup.saving = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('group_add_group_failed'));
            }
            else if (result.status == 'ok' || result.status == 'ok_but_notification_failed') {
                console.log("Created new group");
                $scope.refreshUser(function () {
                    $scope.newGroup.open = false;
                    $scope.newGroup.saving = false;
                    $scope.newGroup.name = '';
                    if (result.status == 'ok_but_notification_failed') {
                        $scope.modalAlert('alert-warning', $filter('i18n')('group_add_group_ok_but_notification_failed'));
                    }
                });
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

    $scope.openJoinGroup = function() {
        $scope.invitationCode = {
            text: ''
        };
        $scope.joinGroupProcess = true;
        $scope.modalAlert('', '');
    };

    $scope.closeJoinGroup = function() {
        $scope.joinGroupProcess = false;
        $scope.invitationCode = {
            text: ''
        };
    };

    $scope.joinGroup = function() {
        $scope.joinGroupSaveProcess = true;
        $scope.modalAlert('', '');

        var address;
        if ($scope.isCurrentUser)
            address = '/api/1.0/user/groups/join';
        else
            address = '/api/1.0/user/' + $scope.user.id + '/groups/join';

        $http.post(address, {
            code: $scope.invitationCode.text
        }).success(function(result) {
            $scope.joinGroupSaveProcess = false;
            if (typeof result != 'object' || result.status == undefined) {
                $scope.modalAlert('alert-danger', $filter('i18n')('group_join_failed'));
            }
            else if (result.status == 'already_member') {
                $scope.modalAlert('alert-info', $filter('i18n')('group_join_already_member'));
            }
            else if (result && result.status == 'ok') {
                $log.log("Joined group")
                $scope.closeJoinGroup();
                $scope.refreshUser(function() {
                    $scope.modalAlert('alert-success', $filter('i18n')('group_join_succeeded'));
                });
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.modalAlert('alert-danger', $filter('i18n')('group_join_failed'));
            }
        }).error(function(response, httpCode, headers) {
            $scope.joinGroupSaveProcess = false;
            $rootScope.operationFailed(httpCode, 'group_join_failed', $scope.modalAlert, headers());
        });
    };

}])



.controller('SuggestionController', ['$scope', '$rootScope', '$state', '$stateParams', '$filter', '$http', '$location', '$anchorScroll', '$timeout', function($scope, $rootScope, $state, $stateParams, $filter, $http, $location, $anchorScroll, $timeout) {

    if (!$stateParams.restaurantId) {
        return $state.go("^");
    }

    for (var i in $rootScope.restaurants) {
        if ($rootScope.restaurants[i].id == $stateParams.restaurantId) {
            $scope.suggestRestaurant = $rootScope.restaurants[i];
            break;
        }
    }

    if (!$scope.suggestRestaurant) {
        return $state.go("^");
    }

    $scope.suggestTime = "";
    $scope.saveProcess = false;

    $("#suggestionModal").modal();
    $('#suggestionModal').on('hidden.bs.modal', function () {
        $state.go("^");
    });
    $('#suggestionModal').on('shown.bs.modal', function () {
        $scope.$broadcast('modalOpened');
    });

    $rootScope.liveViewUpdate();

    for (var i in $rootScope.currentUser.groups) {
        $rootScope.currentUser.groups[i].selectedForSuggestion = false;
        for (var j in $rootScope.currentUser.groups[i].members) {
            $rootScope.currentUser.groups[i].members[j].selectedForSuggestion = false;
        }
    }

    $scope.suggestionSelectedMembers = [];

    $scope.toggleGroup = function(group) {
        group.selectedForSuggestion = !group.selectedForSuggestion;
        for (var j in group.members) {
            $scope.toggleMember(group.members[j], group.selectedForSuggestion);
        }
    };

    $scope.toggleMember = function(member, value) {
        if (value == undefined)
            value = !member.selectedForSuggestion;

        for (var i in $rootScope.currentUser.groups) {
            for (var j in $rootScope.currentUser.groups[i].members) {
                if ($rootScope.currentUser.groups[i].members[j].id == member.id) {
                    $rootScope.currentUser.groups[i].members[j].selectedForSuggestion = value;
                }
            }
        }
    };

    var validateSuggestForm = function() {
        if ($rootScope.weekDay <= $rootScope.today) {
            var timeParts = $scope.suggestTime.split(":");
            var suggestionDate = new Date();
            suggestionDate.setHours(timeParts[0]);
            suggestionDate.setMinutes(timeParts[1]);
            if (suggestionDate.getTime() + $rootScope.currentUser.config.limits.suggestion_create_in_past_time * 1000
                < new Date().getTime())
            {
                $scope.modalAlert('alert-warning', $filter('i18n')('suggestion_too_early'));
                return false;
            }
        }
        return true;
    };

    $scope.suggestTimeValidityError = function() {
        if (!($scope.suggestRestaurant.openingHours[$scope.weekDay].lunch
            && $scope.suggestTime && $scope.suggestTime.length == 5))
            return 0;

        if ($scope.suggestRestaurant.openingHours[$scope.weekDay].lunch.start
            > $scope.suggestTime)
            return 1;
        else if ($scope.suggestRestaurant.openingHours[$scope.weekDay].lunch.end
            < $scope.suggestTime)
            return 2;
        else
            return 0;
    };

    $scope.send = function() {
        if (!validateSuggestForm()) {
            return;
        }
        var members = {};
        for (var i in $rootScope.currentUser.groups) {
            for (var j in $rootScope.currentUser.groups[i].members) {
                if ($rootScope.currentUser.groups[i].members[j].selectedForSuggestion) {
                    members[$rootScope.currentUser.groups[i].members[j].id] = true;
                }
            }
        }

        $scope.saveProcess = true;
        $scope.modalAlert('', '');

        $http.post('api/1.0/restaurants/' + $scope.suggestRestaurant.id + '/suggestions', {
            day: $rootScope.weekDay - 1,
            time: $scope.suggestTime,
            members: members
        }).success(function(result) {
            if (typeof result != 'object' || result.status == undefined) {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('suggestion_save_error'));
            }
            else if (result.status == 'invalid_time') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('suggestion_invalid_time'));
            }
            else if (result.status == 'too_early') {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-warning', $filter('i18n')('suggestion_too_early'));
            }
            // Success
            else if (result && result.status == 'ok') {
                $rootScope.refreshCurrentUser(function() {
                    $scope.saveProcess = false;
                    $("#suggestionModal").modal('hide');

                    if (result.failed_to_send_invitation_email) {
                        $rootScope.alert('alert-warning', $filter('i18n')('suggest_failed_to_send_invitation_email')
                            + ' ' + result.failed_to_send_invitation_email.join(", "));
                    }
                    else {
                        $rootScope.alert('alert-success', $filter('i18n')('suggestion_created'));
                    }
                });
            }
            else {
                console.error("Unknown response");
                console.error(result);
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('suggestion_save_error'))
            }
        }).error(function(response, httpCode) {
                $scope.saveProcess = false;
                $scope.modalAlert('alert-danger', $filter('i18n')('suggestion_save_error'))
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

        $timeout(function() {
            $("#suggest-time").focus().val($("#suggest-time").val());
        }, 0);
    });

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