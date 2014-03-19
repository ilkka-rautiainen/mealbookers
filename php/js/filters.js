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
})

.filter('formatOpeningHour', ['$filter', function($filter) {
    return function(openingHour) {
        if (!openingHour)
            return '';
        return $filter('i18n')('opening_hour_type_' + openingHour.type)
            + ' ' + openingHour.start
            + ' - ' + openingHour.end;
    }
}])

.filter('replace', function() {
	return function(haystack, search, replace) {
        if (typeof haystack != 'string')
            return '';
		return haystack.replace(search, replace);
	}
});