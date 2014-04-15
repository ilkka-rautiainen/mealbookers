'use strict';

/* Localized strings */

angular.module('Mealbookers.localization', [])

.factory('localize', ['$http', '$rootScope', function ($http, $rootScope) {

    var localize = {

        getLocalizedString: function (value) {
            var result = '';

            if (typeof $rootScope.localization != 'object') {
                return console.error("No localization found (not loaded correctly)");
            }

            var entry = $rootScope.localization[value];
            if ((entry !== null) && (entry != undefined)) {
                result = entry;
            }
            else {
                console.error("No localization found for: " + value);
            }

            // return the value to the call
            return result;
        }
    };

    // return the local instance when called
    return localize;
}])

.filter('i18n', ['localize', '$sce', function (localize, $sce) {
    return function (input) {
        return $sce.trustAsHtml(localize.getLocalizedString(input));
    };
}]);