/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp').factory('customDimensionsModel', customDimensionsModel);

    customDimensionsModel.$inject = ['piwikApi', '$q'];

    function customDimensionsModel(piwikApi, $q) {
        var fetchAllPromise;

        var model = {
            customDimensions : [],
            availableScopes: [],
            isLoading: false,
            isUpdating: false,
            fetchCustomDimensionsConfiguration: fetchCustomDimensionsConfiguration,
            fetchAvailableScopes: fetchAvailableScopes,
            findCustomDimension: findCustomDimension,
            createOrUpdateDimension: createOrUpdateDimension,
            reload: reload
        };

        return model;

        function reload()
        {
            model.customDimensions = [];
            model.availableScopes = [];
            fetchAllPromise = null;
            fetchCustomDimensionsConfiguration();
        }

        function fetchCustomDimensionsConfiguration() {
            if (fetchAllPromise) {
                return fetchAllPromise;
            }

            model.isLoading = true;

            fetchAllPromise = piwikApi.fetch({method: 'CustomDimensions.getConfiguredCustomDimensions'})
                .then(function (customDimensions) {
                    model.customDimensions = customDimensions;

                    return customDimensions;
                });

            return $q.all(fetchAllPromise, fetchAvailableScopes()).then(function () {
                model.isLoading = false;
            });
        }

        function fetchAvailableScopes() {
            return piwikApi.fetch({method: 'CustomDimensions.getAvailableScopes'}).then(function (availableScopes) {
                model.availableScopes = availableScopes;

                return availableScopes;
            });
        }

        function findCustomDimension(customDimensionId) {
            return fetchCustomDimensionsConfiguration().then(function (customDimensions) {
                var found;
                angular.forEach(customDimensions, function (dimension) {
                    if (parseInt(dimension.idcustomdimension, 10) === customDimensionId) {
                        found = dimension;
                    }
                });

                return found;
            });
        }

        function createOrUpdateDimension(dimension, method) {
            dimension = angular.copy(dimension);
            dimension.active = dimension.active ? '1' : '0';
            dimension.method = method;
            var extractions = dimension.extractions;
            delete dimension.extractions;

            model.isUpdating = true;

            return piwikApi.post(dimension, {extractions: extractions}).then(function (response) {
                model.isUpdating = false;
                return response;
            }, function () {
                model.isUpdating = false;
            });
        }
    }
})();