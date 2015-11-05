/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp').controller('ManageCustomDimensionsController', ManageCustomDimensionsController);

    ManageCustomDimensionsController.$inject = ['$scope', '$rootScope', '$location'];

    function ManageCustomDimensionsController($scope, $rootScope, $location) {

        this.editMode = false;

        var self = this;

        function getValidDimensionScope(scope)
        {
            if (-1 !== ['action', 'visit'].indexOf(scope)) {
                return scope;
            }

            return '';
        }

        function initState() {
            var $search = $location.search();
            if ('id' in $search) {
                self.editMode = true;
                self.dimensionId = parseInt($search['id'], 10);
                self.dimensionScope = getValidDimensionScope($search['scope']);
            } else {
                self.editMode = false;
                self.dimensionId = null;
                self.dimensionScope = '';
            }
        }

        initState();

        $rootScope.$on('$locationChangeSuccess', initState);

        $scope.$on('$destroy', function() {
            $rootScope.off('mouseup', initState);
        });
    }
})();