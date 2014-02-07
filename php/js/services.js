'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

services.factory('Restaurants', ['$resource', '$rootScope', function($resource, $rootScope) {
    return $resource('api/1.0/restaurants?lang=:lang', {}, {
          query: {method:'GET', params:{lang: $rootScope.userLang}, isArray:true}
        });
}]);
