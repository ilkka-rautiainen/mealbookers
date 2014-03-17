'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

/**
 * Load current user and localization
 */
services.factory('InitApp', ['$http', '$rootScope', '$q', function($http, $rootScope, $q) {
    // Init promises
    var currentUserPromise = $http.get('api/1.0/user'), localizationPromise, restaurantsPromise;

    // Get deferred
    var deferred = $q.defer();

    currentUserPromise.then(function(result) {
        console.log("Current user loaded");
        $rootScope.currentUser = result.data;
        $rootScope.updateGroupsWithMe();

        // Get localization
        localizationPromise = $http.get('api/1.0/app/language/'
            + $rootScope.currentUser.language).then(function(result)
        {
            console.log("Localization loaded");
            $rootScope.localization = result.data;
        }, function() {
            console.error("Error while loading translations");
        });

        // Get restaurants
        restaurantsPromise = $http.get('api/1.0/restaurants', {
            params: {
                lang: $rootScope.currentUser.language
            }
        }).then(function(result) {
            console.log("Restaurants loaded");
            $rootScope.restaurants = result.data;
        }, function() {
            console.error("Error while loading restaurants");
        });

        // Wait for both of them to be ready
        $q.all([localizationPromise, restaurantsPromise]).then(function(response) {
            deferred.resolve(response);
        });
    }, function() {
        console.error("Error while loading current user");
    });
         
    return deferred.promise;
}]);