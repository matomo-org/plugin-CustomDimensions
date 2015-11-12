/*!
 * Piwik - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("CustomDimensions", function () {
    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\CustomDimensions\\tests\\Fixtures\\TrackVisitsWithCustomDimensionsFixture";

    var generalParams = 'idSite=1&period=year&date=2013-01-23',
        urlBase = 'module=CoreHome&action=index&' + generalParams;

    var manageUrl = "?" + generalParams + "&module=CustomDimensions&action=manage";
    var reportUrl = "?" + urlBase + "#" + generalParams + "&module=CustomDimensions&action=menuGetCustomDimension";

    before(function () {
        testEnvironment.pluginsToLoad = ['CustomDimensions'];
        testEnvironment.save();
    });

    /**
     * VISIT DIMENSION REPORTS
     */

    it('should add a menu item for each active visit dimension', function (done) {
        expect.screenshot('report_visit_mainmenu').to.be.captureSelector('#secondNavBar', function (page) {
            page.load(reportUrl + "&idDimension=2");
        }, done);
    });

    it('should show the report for the selected visit dimension', function (done) {
        expect.screenshot('report_visit').to.be.captureSelector('.pageWrap', function (page) {

        }, done);
    });

    it('should add visit dimensions to goals report', function (done) {
        expect.screenshot('report_goals_overview').to.be.captureSelector('.reportsByDimensionView', function (page) {
            page.load( "?" + urlBase + "#" + generalParams + "&module=Goals&action=index");
            page.click('.reportsByDimensionView .dimension:contains(MyName1)');
        }, done);
    });

    /**
     * ACTION DIMENSION REPORTS
     */

    it('should add a menu item for each active action dimension', function (done) {
        expect.screenshot('report_actions_mainmenu').to.be.captureSelector('#secondNavBar', function (page) {
            page.load(reportUrl + "&idDimension=3");
        }, done);
    });

    it('should show the report for the selected action dimension', function (done) {
        expect.screenshot('report_action').to.be.captureSelector('.pageWrap', function (page) {

        }, done);
    });

    it('should be able to render insights', function (done) {
        expect.screenshot('report_action_insights').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.expandDataTableFooterDrawer');
            page.click('[data-footer-icon-id="insightsVisualization"]');
        }, done);
    });

    it('should show an error when trying to open an inactive dimension', function (done) {
        expect.screenshot('report_actions_inactive').to.be.captureSelector('.pageWrap', function (page) {
            page.load(reportUrl + "&idDimension=4");
        }, done);
    });

    it('should be able to open segmented visitor log', function (done) {
        expect.screenshot('report_actions_segmented_visitorlog').to.be.captureSelector('.pageWrap,.ui-dialog > .ui-dialog-content > div.dataTableVizVisitorLog:visible', function (page) {
            page.load(reportUrl + "&idDimension=3");

            page.click('.segmentationTitle');
            page.click('.segname:contains(en)');

            page.mouseMove('table.dataTable tbody tr:first-child');
            page.mouseMove('a.actionSegmentVisitorLog:visible'); // necessary to get popover to display
            page.click('a.actionSegmentVisitorLog:visible');
        }, done);
    });

    it('should be able to open row evolution', function (done) {
        expect.screenshot('report_actions_rowevolution').to.be.captureSelector('.pageWrap,.ui-dialog > .ui-dialog-content > div.rowevolution:visible', function (page) {
            page.load(reportUrl + "&idDimension=3");

            page.click('.segmentationTitle');
            page.click('.segname:contains(en)');

            page.mouseMove('table.dataTable tbody tr:first-child');
            page.mouseMove('a.actionRowEvolution:visible'); // necessary to get popover to display
            page.click('a.actionRowEvolution:visible');
        }, done);
    });

    /**
     * MANAGE CUSTOM DIMENSIONS
     */

    it('should load initial manange page', function (done) {
        expect.screenshot('manage_inital').to.be.captureSelector('.pageWrap', function (page) {
            page.load(manageUrl);
        }, done);
    });

    it('should open a page to create a new visit dimension', function (done) {
        expect.screenshot('manage_new_visit_dimension_open').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.configure.visit');
        }, done);
    });

    it('should be possible to define name, active and extractions', function (done) {
        expect.screenshot('manage_new_visit_dimension_withdata').to.be.captureSelector('.pageWrap', function (page) {
            page.sendKeys(".editCustomDimension #name", 'My Custom Name');
            page.click('.editCustomDimension #active');

            page.sendKeys('.extraction.0 .pattern', 'myPattern_(.+)');

            page.click('.extraction.0 .icon-plus');
            page.sendKeys('.extraction.1 .pattern', 'second pattern_(.+)');

            page.click('.extraction.1 .icon-plus');
            page.sendKeys('.extraction.2 .pattern', 'thirdpattern_(.+)test');
        }, done);
    });

    it('should be possible to remove a defined extraction', function (done) {
        expect.screenshot('manage_new_visit_dimension_remove_an_extraction').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.extraction.1 .icon-minus');
        }, done);
    });

    it('should create a new dimension', function (done) {
        expect.screenshot('manage_new_visit_dimension_created').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.editCustomDimension .create');
        }, done);
    });

    it('should be able to open created dimension and see same data but this time with tracking instructions', function (done) {
        expect.screenshot('manage_edit_visit_dimension_verify_created').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.manageCustomDimensions .customdimension.7 .icon-edit');
        }, done);
    });

    it('should be possible to change an existing dimension', function (done) {
        expect.screenshot('manage_edit_visit_dimension_withdata').to.be.captureSelector('.pageWrap', function (page) {
            page.sendKeys(".editCustomDimension #name", 'ABC');
            page.click('.editCustomDimension #active');
            page.click('.extraction.0 .icon-minus');
        }, done);
    });

    it('should updated an existing dimension', function (done) {
        expect.screenshot('manage_edit_visit_dimension_updated').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.editCustomDimension .update');
        }, done);
    });

    it('should have actually updated values', function (done) {
        expect.screenshot('manage_edit_visit_dimension_verify_updated').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.manageCustomDimensions .customdimension.7 .icon-edit');
        }, done);
    });

    it('should go back to list when pressing cancel', function (done) {
        expect.screenshot('manage_edit_visit_dimension_cancel').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.editCustomDimension .cancel');
        }, done);
    });

    it('should disable configure button when no dimensions are left for a scope', function (done) {
        expect.screenshot('manage_configure_button_disabled').to.be.captureSelector('.pageWrap', function (page) {
            page.click('.configure.visit');
            page.click('.editCustomDimension #active');
            page.sendKeys(".editCustomDimension #name", 'Last Name');
            page.click('.editCustomDimension .create');
        }, done);
    });

    it('should be possible to create a new dimension via URL', function (done) {
        expect.screenshot('manage_create_via_url').to.be.captureSelector('.pageWrap', function (page) {
            page.load(manageUrl + "#?idDimension=0&scope=action");
        }, done);
    });

    it('should be possible to open an existing dimension via URL', function (done) {
        expect.screenshot('manage_edit_via_url').to.be.captureSelector('.pageWrap', function (page) {
            page.load(manageUrl + "#?idDimension=2&scope=visit");
        }, done);
    });

    /**
     * VISIT DIMENSION REPORTS MENU GROUPED
     */

    it('should group dimensions in menu once there are more than 3', function (done) {
        expect.screenshot('report_visit_mainmenu_grouped').to.be.captureSelector('#secondNavBar,.menuDropdown .items', function (page) {
            page.load(reportUrl + "&idDimension=2");
            page.click('#UserCountryMap_realtimeWorldMap + li .menuDropdown')
        }, done);
    });

});