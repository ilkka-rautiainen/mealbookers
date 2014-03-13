'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

/**
 * Load current user and localization
 */
services.factory('Initialization', ['$http', '$rootScope', function($http, $rootScope) {
    var currentUserPromise = $http.get('api/1.0/user');

    // Get localization promise from the then function
    var localizationPromise = currentUserPromise.then(function(result) {
        $rootScope.currentUser = result.data;
        $rootScope.updateGroupsWithMe();
        // Return a new promise 
        return $http.get('api/1.0/app/language/' + $rootScope.currentUser.language)
            .then(function(result) {
                $rootScope.localization = result.data;
            },
            function() {
                console.error("Error while loading translations");
            });
    },
    function() {
        console.error("Error while loading current user");
    });

    return localizationPromise;
}]);

services.factory('Restaurants', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants?lang=:lang', {}, {
          query: {method:'GET', params:{lang: $rootScope.currentUser.language}, isArray:true}
        });
}]);

services.factory('Suggestions', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants/:restaurantId/suggestions', {}, {
          post: {method: 'POST', params: {restaurantId: '@restaurantId'}}
        });
}]);