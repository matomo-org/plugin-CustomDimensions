/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp').controller('CustomDimensionsEditController', CustomDimensionsEditController);

    CustomDimensionsEditController.$inject = ['$scope', 'customDimensionsModel', 'piwik', '$location'];

    function CustomDimensionsEditController($scope, customDimensionsModel, piwik, $location) {

        var self = this;
        var currentId = null;

        this.model = customDimensionsModel;

        function showSuccessNotification(message)
        {
            var UI = require('piwik/UI');
            var notification = new UI.Notification();
            notification.show(message, {context: 'success', id:'customdimensions', type: 'toast'});
        }

        function init(dimensionId)
        {
            self.create = dimensionId == '0';
            self.edit   = !(dimensionId == '0');

            if (self.edit && dimensionId) {
                customDimensionsModel.findCustomDimension(dimensionId).then(function (dimension) {
                    self.dimension = dimension;
                    if (dimension && !dimension.extractions.length) {
                        self.addExtraction();
                    }
                });
            } else if (self.create) {
                self.dimension = {
                    idSite: piwik.idSite,
                    name: '',
                    active: false,
                    extractions: [],
                    scope: $scope.dimensionScope
                };
                self.addExtraction();
            }
        }

        this.removeExtraction = function(index)
        {
            if (index > -1) {
                this.dimension.extractions.splice(index, 1);
            }
        }

        this.addExtraction = function()
        {
            this.dimension.extractions.push({dimension: 'url', pattern: ''});
        }

        this.createCustomDimension = function () {

            var method = 'CustomDimensions.configureNewCustomDimension';

            this.isUpdating = true;

            customDimensionsModel.createOrUpdateDimension(this.dimension, method).then(function () {
                showSuccessNotification('Custom Dimension created');
                self.model.reload();
                $location.url('/list');
            });
        };

        this.updateCustomDimension = function () {
            this.dimension.idCustomDimension = this.dimension.idcustomdimension;

            var method = 'CustomDimensions.configureExistingCustomDimension';

            this.isUpdating = true;

            customDimensionsModel.createOrUpdateDimension(this.dimension, method).then(function () {
                showSuccessNotification('Custom Dimension updated');
                $location.url('/list');
            });
        };

        $scope.$watch('dimensionId', function (newValue, oldValue) {
            if (newValue != oldValue || currentId === null) {
                currentId = newValue;
                init(newValue);
            }
        });
    }
})();