'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

services.factory('Localization', ['$http', '$rootScope', function($http, $rootScope) {
    return $http.get('api/1.0/app/language/' + $rootScope.userLang)
        .success(function(data) {
            $rootScope.localization = data;
        })
        .error(function(response) {
            console.error("Error while loading translations");
        });
}]);

services.factory('Restaurants', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants?lang=:lang', {}, {
          query: {method:'GET', params:{lang: $rootScope.userLang}, isArray:true}
        });
}]);

services.factory('Suggestions', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants/:restaurantId/suggestions', {}, {
          post: {method: 'POST', params: {restaurantId: '@restaurantId'}}
        });
}]);

services.factory('CurrentUser', ['$resource', function($resource) {
    return $resource('api/1.0/user/:action', {}, {
          get: {method: 'GET', params: {action: '@action'}, isArray: true}
        });
}]);