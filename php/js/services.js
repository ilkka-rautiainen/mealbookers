'use strict';

/* Services */

var services = angular.module('Mealbookers.services', ['ngResource']);

services.value('version', '0.1');

services.factory('Restaurants', ['$resource', function($resource) {
    return $resource('api/1.0/restaurants/:restaurantId', {}, {
          query: {method:'GET', params:{restaurantId:''}, isArray:true}
        });
}]);
