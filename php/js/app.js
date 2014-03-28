'use strict';


// Declare app level module which depends on filters, and services
angular.module('Mealbookers', [
    'ngAnimate',
    'Mealbookers.filters',
    'Mealbookers.services',
    'Mealbookers.directives',
    'Mealbookers.controllers',
    'Mealbookers.localization',
    'ui.router'
])

.config(['$stateProvider', '$urlRouterProvider', '$uiViewScrollProvider', function($stateProvider, $urlRouterProvider, $uiViewScrollProvider) {
    $uiViewScrollProvider.useAnchorScroll()
    $stateProvider

    .state('Navigation', {
        abstract: true,
        url: "/menu",
        templateUrl: "partials/Navigation.html",
        controller: 'NavigationController',
        resolve: {
            InitApp: "InitApp"
        }
    })
    
    .state('Navigation.Menu', {
        url: "/:day",
        templateUrl: "partials/Menu.html",
        controller: 'MenuController'
    })
    
    .state('Navigation.Menu.Login', {
        url: "/login",
        templateUrl: "partials/modals/Login.html",
        controller: 'LoginController',
        data: {
            modal: true,
            modalId: "logInModal"
        },
    })

    .state('Navigation.Menu.Register', {
        url: "/register",
        templateUrl: "partials/modals/Register.html",
        controller: 'RegisterController',
        data: {
            modal: true,
            modalId: "register-modal"
        },
    })
    
    .state('Navigation.Menu.Suggestion', {
        url: "/restaurant/:restaurantId/suggestion",
        templateUrl: "partials/modals/Suggestion.html",
        data: {
            modal: true,
            modalId: "suggestionModal"
        },
        controller: 'SuggestionController'
    })
    
    .state('Navigation.Menu.AccountSettings', {
        url: "/settings/account",
        templateUrl: "partials/modals/AccountSettings.html",
        data: {
            modal: true,
            modalId: "accountSettingsModal"
        },
        controller: 'AccountSettingsController'
    })
    
    .state('Navigation.Menu.GroupSettings', {
        url: "/settings/groups",
        templateUrl: "partials/modals/GroupSettings.html",
        data: {
            modal: true,
            modalId: "GroupSettingsModal"
        },
        controller: 'GroupSettingsController'
    })
    
    .state('Navigation.Menu.UserManagement', {
        url: "/settings/users",
        templateUrl: "partials/modals/UserManagement.html",
        data: {
            modal: true,
            modalId: "user-management-modal"
        },
        controller: 'UserManagementController'
    })
    
    .state('Navigation.Menu.UserManagement.AccountSettings', {
        url: "/:userId/account",
        templateUrl: "partials/modals/AccountSettings.html",
        data: {
            modal: true,
            modalId: "accountSettingsModal"
        },
        controller: 'AccountSettingsController'
    })
    
    .state('Navigation.Menu.UserManagement.GroupSettings', {
        url: "/:userId/group",
        templateUrl: "partials/modals/GroupSettings.html",
        data: {
            modal: true,
            modalId: "groupSettingsModal"
        },
        controller: 'GroupSettingsController'
    })
    
    .state('Navigation.AcceptSuggestion', {
        url: "/suggestion/accept/:token",
        templateUrl: "partials/AcceptSuggestion.html",
        controller: 'AcceptSuggestionController'
    })

    $urlRouterProvider.otherwise("/menu/" + (((new Date().getDay() + 6) % 7) + 1));
}])

.run(['$rootScope', '$window', '$http', '$timeout', '$interval', '$state', '$stateParams', 'InitApp', '$log', '$filter', function($rootScope, $window, $http, $timeout, $interval, $state, $stateParams, InitApp, $log, $filter) {

    $rootScope.currentUser = {
        role: 'guest',
        groups: [],
    };

    var emptyMessage = {
        message: '',
        type: ''
    };
    $rootScope.alertMessage = emptyMessage;
    $rootScope.liveViewOn = false;

    $rootScope.config = {
        alertTimeouts: {
            'alert-danger': 30000,
            'alert-warning': 10000,
            'alert-info': 4000,
            'alert-success': 4000
        },
        liveViewInterval: 15000,
        liveViewRecoveryInterval: 5000,
        defaultLanguage: 'fi'
    };

    var alertTimeout = null;
    var alertFadeTimeout = null;

    $rootScope.dismissAlert = function() {
        if (alertTimeout) {
            clearTimeout(alertTimeout);
        }
        if (alertFadeTimeout) {
            clearTimeout(alertFadeTimeout);
        }
        alertFadeout();
    };

    $rootScope.alert = function(type, message) {
        if (!$rootScope.config.alertTimeouts[type]) {
            return console.error("Invalid alert type: " + type);
        }

        $rootScope.alertMessage = {
            type: type,
            message: message
        };
        $(".main-alert").finish();
        $(".main-alert").show();
        if (alertTimeout) {
            clearTimeout(alertTimeout);
        }
        if (alertFadeTimeout) {
            clearTimeout(alertFadeTimeout);
        }
        alertTimeout = setTimeout(alertFadeout, $rootScope.config.alertTimeouts[type]);
    };

    var alertFadeout = function() {
        $(".main-alert").animate({
            height: 'hide',
            'margin-top': 'hide',
            'margin-bottom': 'hide',
            'padding-top': 'hide',
            'padding-bottom': 'hide',
            'border-top': 'hide',
            'border-bottom': 'hide',
            opacity: 'hide'
        }, 1500);
        alertFadeTimeout = setTimeout(function() {
            $rootScope.alertMessage = emptyMessage;
        }, 1500);
    };

    /**
     * Reloads current user
     */
    $rootScope.refreshCurrentUser = function(done, liveView) {
        if (liveView) {
            var params = {
                after: $rootScope.currentUser.timestamp
            };
        }
        else {
            var params = {};
        }
        $http.get('api/1.0/user', {
            params: params
        }).success(function(result) {
            if (typeof result.status == 'string' && result.status == 'up_to_date') {
                $rootScope.currentUser.timestamp = result.timestamp;
                if (typeof done == 'function') {
                    done();
                }
            }
            else {
                $log.debug("Current user refreshed");
                for (var i in result.user) {
                    $rootScope.currentUser[i] = result.user[i];    
                }

                $rootScope.$broadcast("currentUserRefresh");

                // Logged in
                if ($rootScope.currentUser.role != 'guest') {
                    if (!$rootScope.liveViewOn) {
                        $log.info("User is logged in, starting live view");
                        $rootScope.startLiveView();
                    }

                    $rootScope.refreshSuggestions(done);
                }
                // Not logged in
                else {
                    $rootScope.fetchGuestLanguage();

                    if ($rootScope.liveViewOn) {
                        $log.info("User is logged out, stopping live view");
                        $rootScope.clearSuggestions();
                        $rootScope.stopLiveView();
                        $rootScope.resetToMenu();
                        if (typeof done == 'function') {
                            done();
                        }
                    }
                }
            }
        }).error(function() {
            throw new RefreshDataException("Error while refreshing current user");
        });
    };

    $rootScope.fetchGuestLanguage = function() {
        var lang;

        if (!$.cookie('language')) {
            lang = (navigator.language || navigator.userLanguage).substring(0, 2);
            $.cookie('language', lang, {expires: 365, path: '/'});
        }

        lang = $.cookie('language');
        if (lang != 'fi' && lang != 'en') {
            lang = $rootScope.config.defaultLanguage;
            $.cookie('lang', lang, {expires: 365, path: '/'});
        }

        $rootScope.currentUser.language = lang;
        $rootScope.langauge = lang;
        $log.debug("Guest language: " + lang);
    };

    $rootScope.$watch('currentUser.language', function(newLang) {
        $.cookie('language', newLang, {expires: 365, path: '/'});
    });

    $rootScope.logOut = function(showAlert) {
        // $.cookie('language', $rootScope.currentUser.language, {expires: 365, path: '/'});
        $.removeCookie('id');
        $.removeCookie('check');
        $.removeCookie('remember');
        $rootScope.refreshCurrentUser(function() {
            $log.info("Logged out");
            if (showAlert)
                $rootScope.alert('alert-success', $filter('i18n')('logged_out'));
        });
    };

    $rootScope.resetToMenu = function() {
        $state.go('Navigation.Menu');
        $rootScope.alert('alert-warning', $filter('i18n')('logged_out_invalid_user_info'));
    };

    $rootScope.removeModalAlert = function() {
        delete $rootScope.modalAlertMessage;
    };

    $rootScope.modalAlert = function(type, message) {
        if (!$rootScope.modalAlertMessage) {
            $rootScope.modalAlertMessage = {
                type: '',
                message: ''
            };
        }
        $rootScope.modalAlertMessage.type = type;
        $rootScope.modalAlertMessage.message = message;
    };

    $rootScope.refreshSuggestions = function(done) {
        $http.get('api/1.0/restaurants/suggestions').success(function(data) {

            for (var i = 0; i < $rootScope.restaurants.length; i++) {
                $rootScope.restaurants[i].suggestionList = [];
            }

            for (var restaurantId in data) {
                // Find restaurant with that id
                var restaurantIndex = -1;
                for (var i = 0; i < $rootScope.restaurants.length; i++) {
                    if ($rootScope.restaurants[i].id == restaurantId) {
                        restaurantIndex = i;
                        break;
                    }
                }
                if (restaurantIndex == -1) {
                    $log.warn("restaurant " + restaurantId.toString() + " not found in restaurant array, skipping");
                    continue;
                }

                $rootScope.restaurants[restaurantIndex].suggestionList = data[restaurantId];
            }

            $log.debug("Suggestions refreshed");
            if (typeof done == 'function') {
                done();
            }
        }).error(function() {
            throw new RefreshDataException("Error while refreshing suggestions");
        });
    };

    $rootScope.clearSuggestions = function() {
        for (var i = 0; i < $rootScope.restaurants.length; i++) {
            $rootScope.restaurants[i].suggestionList = [];
        }
    };

    $rootScope.liveViewUpdate = function() {
        // console.log("Live view update");
        $timeout.cancel($rootScope.liveViewTimeout);

        // Day has changed
        if ($rootScope.today != ((new Date().getDay() + 6) % 7) + 1) {
            if ($rootScope.today == 7) {
                $state.transitionTo($state.current, {day: 1}, {reload: true, inherit: false, notify: true});
            }
            else {
                $state.transitionTo($state.current, $stateParams, {reload: true, inherit: false, notify: true});
            }
            $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
            return $log.info("Day has changed, refreshing state");
        }

        $rootScope.refreshCurrentUser(function() {
            $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
        }, true);
    };

    /**
     * Refreshes current user once and then stops the live view
     * @param  done callback which is called after the current user refresh
     */
    $rootScope.refreshCurrentUserAndStopLiveView = function(done) {
        $timeout.cancel($rootScope.liveViewTimeout);
        $rootScope.refreshCurrentUser(function() {
            $rootScope.liveViewOn = false;
            $log.info("Live View stopped");
            if (typeof done == 'function') {
                done();
            }
        });
    };

    $rootScope.startLiveView = function() {
        $rootScope.liveViewTimeout = $timeout($rootScope.liveViewUpdate, $rootScope.config.liveViewInterval);
        $rootScope.liveViewOn = true;
        $log.info("Live View started");
    };

    $rootScope.stopLiveView = function() {
        $timeout.cancel($rootScope.liveViewTimeout);
        $rootScope.liveViewOn = false;
        $log.info("Live View stopped");
    };

    /**
     * This function is called when an error occurs in live view
     */
    $rootScope.startLiveViewRecovery = function() {
        $log.log("Live view recovery started");
        $rootScope.liveViewRecoveryInterval = $interval($rootScope.liveViewRecovery, $rootScope.config.liveViewRecoveryInterval);
    };

    $rootScope.liveViewRecovery = function() {
        $http.get('api/1.0/app/status').success(function(result) {
            if (result.status == 'ok') {
                $log.info("Live view recovered");
                $interval.cancel($rootScope.liveViewRecoveryInterval);
                $rootScope.refreshCurrentUser(function() {
                    $rootScope.startLiveView();
                });
            }
        });
    };

    $rootScope.refreshLocalization = function(done) {
        // Get localization
        $http.get('api/1.0/app/language/' + $rootScope.currentUser.language).success(function(result)
        {
            $log.debug("Localization refreshed");
            $rootScope.localization = result;
        }).error(function() {
            $log.error("Error while refreshing localization");
        });

        $rootScope.localizationCurrentLanguage = $rootScope.currentUser.language;

        $rootScope.refreshRestaurants(done);
    };

    $rootScope.refreshRestaurants = function(done) {
        // Get restaurants
        $http.get('api/1.0/restaurants', {
            params: {
                lang: $rootScope.currentUser.language
            }
        }).success(function(result) {
            $log.debug("Restaurants refreshed");
            $rootScope.restaurants = result;

            if (typeof done == 'function') {
                done();
            }
        }).error(function() {
            $log.error("Error while refreshing restaurants");
        });
    };

    $rootScope.operationFailed = function(httpCode, errorMessage, customAlertFunction) {
        var alertFunction;
        if (typeof customAlertFunction == 'function')
            alertFunction = customAlertFunction;
        else
            alertFunction = $rootScope.alert;

        $rootScope.refreshCurrentUser(function() {
            if ($rootScope.localization[errorMessage + '_' + httpCode.toString()])
                alertFunction('alert-warning', $rootScope.localization[errorMessage + '_' + httpCode.toString()]);
            else
                alertFunction('alert-danger', $rootScope.localization[errorMessage]);

        });
    };

    $rootScope.$on('$stateChangeStart', function(e, toState, toParams, fromState, fromParams) {
        // From modal to not modal
        if (fromState.data && fromState.data.modal && !(toState.data && toState.data.modal)) {
            $(".modal-backdrop").remove();
            $("body").removeClass("modal-open");
            $rootScope.removeModalAlert();
        }
        // From modal to another modal
        else if (fromState.data && fromState.data.modal && toState.data && toState.data.modal) {
            $("#" + fromState.data.modalId).css("visibility", "hidden");
            $("#" + toState.data.modalId).css("visibility", "visible");
        }

        // From user management child modal to parent modal
        if (fromState.data && fromState.data.modal && toState.name == 'Navigation.Menu.UserManagement') {
            $rootScope.$broadcast("childModalClosed");
        }

        // To modal
        if (toState.data && toState.data.modal) {
            $timeout(function() {
                $("body").addClass("modal-open");
                $("#" + toState.data.modalId).focus();
            }, 0);
        }
        
        if ($rootScope.initAppDone) {
            $rootScope.setTitle(toState.name);
        }
    });

    $rootScope.$watch('initAppDone', function(newValue) {
        if (newValue) {
            $rootScope.setTitle('currentState');
        }
    });

    $rootScope.setTitle = function(state) {
        if (state == 'currentState') {
            state = $state.current.name;
        }
        $rootScope.title = $filter('i18n')('title_' + state) + $filter('i18n')('title_postfix');
    };

    $rootScope.getWeekDayText = function(day) {
        if (day < 1 || day > 7) {
            return console.error("Incorrect day passed: " + day);
        }
        
        var today = ((new Date().getDay() + 6) % 7) + 1;
        if (day == today) {
            return $rootScope.localization.today;
        }
        else if (day == today + 1) {
            return $rootScope.localization.tomorrow;
        }
        else {
            return $rootScope.localization['weekday_' + day];
        }
    };

    var setWidthClass = function() {
        $rootScope.$apply(function() {
            $rootScope.windowWidth = $window.innerWidth;
            if ($rootScope.windowWidth >= 1200) {
                $rootScope.widthClass = "lg";
                $rootScope.columns = 4;
            }
            else if ($rootScope.windowWidth >= 992) {
                $rootScope.widthClass = "md";
                $rootScope.columns = 3;
            }
            else if ($rootScope.windowWidth >= 768) {
                $rootScope.widthClass = "sm";
                $rootScope.columns = 2;
            }
            else {
                $rootScope.widthClass = "xs";
                $rootScope.columns = 1;
            }
        });
    }

    setWidthClass();
    $($window).bind('resize', setWidthClass);
}]);

function RefreshDataException(message) {
    this.message = message;
    this.type = 'RefreshDataException';
}