'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

services.factory('Restaurants', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants?lang=:lang', {}, {
          query: {method:'GET', params:{lang: $rootScope.userLang}, isArray:true}
        });
}]);

services.factory('Suggestions', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants/:restaurantId/suggestions/:suggestionId', {}, {
          get: {method: 'GET', params: {restaurantId: '@restaurantId', suggestionId: '@suggestionId'}, isArray:true},
          post: {method: 'POST', params: {restaurantId: '@restaurantId'}}
        });
}]);

services.factory('CurrentUser', ['$resource', function($resource) {
    return $resource('api/1.0/user/:action', {}, {
          get: {method: 'GET', params: {action: '@action'}, isArray: true}
        });
}]);