'use strict';

/* Filters */

angular.module('Mealbookers.filters', [])

.filter('mealRow', function($sce) {
    return function(val) {
        return $sce.trustAsHtml(val.replace(/\n/, "<br />"));
    };
})

.filter('interpolate', ['version', function(version) {
    return function(text) {
        return String(text).replace(/\%VERSION\%/mg, version);
    }
}]);
