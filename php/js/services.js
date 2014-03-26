'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

services.factory('$exceptionHandler', ['$log', '$injector', function ($log, $injector) {
    return function (exception, cause) {
        var rootScope = $injector.get('$rootScope');
        if (exception.type == 'RefreshDataException' && rootScope.liveViewOn) {
            $log.warn('Live view interrupted');
            rootScope.startLiveViewRecovery();
        }
        else {
            throw exception;
        }
    };
}]);

/**
 * Load current user and localization
 */
services.factory('InitApp', ['$http', '$rootScope', '$q', '$log', function($http, $rootScope, $q, $log) {
    // Init promises
    var currentUserPromise = $http.get('api/1.0/user'), localizationPromise, restaurantsPromise;

    // Get deferred
    var deferred = $q.defer();

    currentUserPromise.then(function(result) {
        $log.debug("Current user loaded");
        $rootScope.currentUser = result.data.user;

        if ($rootScope.currentUser.role != 'guest') {
            $rootScope.startLiveView();
        }
        else {
            $rootScope.fetchGuestLanguage();
        }

        // Get localization
        localizationPromise = $http.get('api/1.0/app/language/'
            + $rootScope.currentUser.language).then(function(result)
        {
            $log.debug("Localization loaded");
            $rootScope.localization = result.data;
            $rootScope.localizationCurrentLanguage = $rootScope.currentUser.language;
        }, function() {
            $log.error("Error while loading translations");
        });

        // Get restaurants
        restaurantsPromise = $http.get('api/1.0/restaurants', {
            params: {
                lang: $rootScope.currentUser.language
            }
        }).then(function(result) {
            $log.debug("Restaurants loaded");
            $rootScope.restaurants = result.data;
        }, function() {
            $log.error("Error while loading restaurants");
        });

        // Wait for both of them to be ready
        $q.all([localizationPromise, restaurantsPromise]).then(function(response) {
            $rootScope.initAppDone = true;
            deferred.resolve(response);
        });
    }, function() {
        $log.error("Error while loading current user");
    });
         
    return deferred.promise;
}]);