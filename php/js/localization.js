'use strict';

/* Localized strings */

angular.module('Mealbookers.localization', [])

.factory('localize', ['$http', '$rootScope', '$filter', function ($http, $rootScope, $filter) {

    var localize = {
        // use the $window service to get the language of the user's browser
        language: $rootScope.userLang,
        // array to hold the localized resource string entries
        dictionary: [],
        // flag to indicate if the service hs loaded the resource file
        resourceFileLoaded: false,

        successCallback: function (data) {
            // store the returned object in the dictionary
            localize.dictionary = data;
            // set the flag that the resource are loaded
            localize.resourceFileLoaded = true;
            // broadcast that the file has been loaded
            $rootScope.$broadcast('localizeResourcesUpdates');
        },

        initLocalizedResources: function () {
            // build the url to retrieve the localized resource file
            var url = 'api/1.0/app/language/' + localize.language;
            // request the resource file
            $http({ method:"GET", url:url, cache:false }).success(localize.successCallback);
        },

        getLocalizedString: function (value) {
            // default the result to an empty string
            var result = '';
            // check to see if the resource file has been loaded
            if (!localize.resourceFileLoaded) {
                // call the init method
                localize.initLocalizedResources();
                // set the flag to keep from looping in init
                localize.resourceFileLoaded = true;
                // return the empty string
                return result;
            }
            // make sure the dictionary has valid data
            if (typeof localize.dictionary == 'object') {
                // use the filter service to only return those entries which match the value
                // and only take the first result
                var entry = localize.dictionary[value];
                // check to make sure we have a valid entry
                if ((entry !== null) && (entry != undefined)) {
                    // set the result
                    result = entry;
                }
            }
            // return the value to the call
            return result;
        }
    };

    // return the local instance when called
    return localize;
}])

.filter('i18n', ['localize', function (localize) {
    return function (input) {
        return localize.getLocalizedString(input);
    };
}]);