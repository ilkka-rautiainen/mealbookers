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
}])

.filter('capitalize', function() {
	return function(input) {
		if (input)
			input = input.toLowerCase();
		return input.substring(0,1).toUpperCase()+input.substring(1);
	}
});