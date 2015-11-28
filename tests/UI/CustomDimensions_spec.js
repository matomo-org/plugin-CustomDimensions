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

    var popupSelector = '.pageWrap,.ui-dialog:visible';

    function capturePageWrap (screenName, callback, done) {
        expect.screenshot(screenName).to.be.captureSelector('.pageWrap', callback, done);
    }

    function captureSelector (screenName, selector, callback, done) {
        expect.screenshot(screenName).to.be.captureSelector(selector, callback, done);
    }

    function closeOpenedPopover(page)
    {
        page.click('.ui-dialog:visible .ui-button-icon-primary.ui-icon-closethick:visible');
    }

    function triggerRowAction(page, labelToClick, nameOfRowActionToTrigger)
    {
        var rowToMatch = 'td.label:contains(' + labelToClick + '):first';

        page.mouseMove('table.dataTable tbody ' + rowToMatch);
        page.mouseMove(rowToMatch + ' a.'+ nameOfRowActionToTrigger + ':visible'); // necessary to get popover to display
        page.click(rowToMatch + ' a.' + nameOfRowActionToTrigger + ':visible');
    }

    before(function () {
        testEnvironment.pluginsToLoad = ['CustomDimensions'];
        testEnvironment.save();
    });

    /**
     * VISIT DIMENSION REPORTS
     */

    it('should show the report for the selected visit dimension', function (done) {
        capturePageWrap('report_visit', function (page) {
            page.load(reportUrl + '&idDimension=2');
        }, done);
    });

    it('should add a menu item for each active visit dimension', function (done) {
        captureSelector('report_visit_mainmenu', '#secondNavBar', function (page) {
            // we only capture a screenshot of a different part of the page, no need to do anything
        }, done);
    });

    it('should add visit dimensions to goals report', function (done) {
        captureSelector('report_goals_overview', '.reportsByDimensionView', function (page) {
            page.load( "?" + urlBase + "#" + generalParams + "&module=Goals&action=index");
            page.click('.reportsByDimensionView .dimension:contains(MyName1)');
        }, done);
    });

    /**
     * ACTION DIMENSION REPORTS
     */

    it('should show the report for the selected action dimension', function (done) {
        capturePageWrap('report_action', function (page) {
            page.load(reportUrl + '&idDimension=3');
        }, done);
    });

    it('should add a menu item for each active action dimension', function (done) {
        captureSelector('report_actions_mainmenu', '#secondNavBar', function (page) {
            // we only capture a screenshot of a different part of the page, no need to do anything
        }, done);
    });

    it('should offer only segmented visitor log and row action for first level entries', function (done) {
        capturePageWrap('report_actions_rowactions', function (page) {
            page.mouseMove('td.label:contains(en):first');
        }, done);
    });

    it('should be able to render insights', function (done) {
        capturePageWrap('report_action_insights', function (page) {
            page.click('.expandDataTableFooterDrawer');
            page.click('[data-footer-icon-id="insightsVisualization"]');
        }, done);
    });

    it('should show an error when trying to open an inactive dimension', function (done) {
        capturePageWrap('report_actions_inactive', function (page) {
            page.load(reportUrl + '&idDimension=4');
        }, done);
    });

    it('should be able to open segmented visitor log', function (done) {
        captureSelector('report_actions_segmented_visitorlog', popupSelector, function (page) {
            page.load(reportUrl + "&idDimension=3");
            triggerRowAction(page, 'en', 'actionSegmentVisitorLog');
        }, done);
    });

    it('should be able to open row evolution', function (done) {
        captureSelector('report_actions_rowevolution', popupSelector, function (page) {
            page.load(reportUrl + "&idDimension=3");
            triggerRowAction(page, 'en', 'actionRowEvolution');
        }, done);
    });

    it('should be able to show subtable and offer all row actions if scope is action', function (done) {
        capturePageWrap('report_action_subtable', function (page) {
            page.load(reportUrl + "&idDimension=3");
            page.click('.dataTable .subDataTable .value:contains(en):first');
            page.mouseMove('td.label:contains(en_US)');
        }, done);
    });

    it('should be able to show row evolution for subtable', function (done) {
        captureSelector('report_action_subtable_rowevolution', popupSelector, function (page) {
            triggerRowAction(page, 'en_US', 'actionRowEvolution');
        }, done);
    });

    it('should be able to show segmented visitor log for subtable', function (done) {
        captureSelector('report_action_subtable_segmented_visitor_log', popupSelector, function (page) {
            closeOpenedPopover(page);
            triggerRowAction(page, 'en_US', 'actionSegmentVisitorLog');
        }, done);
    });

    it('should be able to show transitions for subtable', function (done) {
        capturePageWrap('report_action_subtable_transitions', function (page) {
            closeOpenedPopover(page);
            triggerRowAction(page, 'en_US', 'actionTransitions');
        }, done);
    });

    /**
     * MANAGE CUSTOM DIMENSIONS
     */

    it('should load initial manange page', function (done) {
        capturePageWrap('manage_inital', function (page) {
            page.load(manageUrl);
        }, done);
    });

    it('should open a page to create a new visit dimension and not show extractions', function (done) {
        capturePageWrap('manage_new_visit_dimension_open', function (page) {
            page.click('.configure.visit');
        }, done);
    });

    it('should be possible to create new visit dimension', function (done) {
        capturePageWrap('manage_new_visit_dimension_created', function (page) {
            page.sendKeys(".editCustomDimension #name", 'My Custom Name');
            page.click('.editCustomDimension .create');
        }, done);
    });

    it('should open a page to create a new action dimension', function (done) {
        capturePageWrap('manage_new_action_dimension_open', function (page) {
            page.click('.configure.action');
        }, done);
    });

    it('should be possible to define name, active and extractions for scope action', function (done) {
        capturePageWrap('manage_new_action_dimension_withdata', function (page) {
            page.sendKeys(".editCustomDimension #name", 'My Action Name');
            page.click('.editCustomDimension #active');

            page.sendKeys('.extraction.0 .pattern', 'myPattern_(.+)');

            page.click('.extraction.0 .icon-plus');
            page.sendKeys('.extraction.1 .pattern', 'second pattern_(.+)');

            page.click('.extraction.1 .icon-plus');
            page.sendKeys('.extraction.2 .pattern', 'thirdpattern_(.+)test');
        }, done);
    });

    it('should be possible to remove a defined extraction', function (done) {
        capturePageWrap('manage_new_action_dimension_remove_an_extraction', function (page) {
            page.click('.extraction.1 .icon-minus');
        }, done);
    });

    it('should create a new dimension', function (done) {
        capturePageWrap('manage_new_action_dimension_created', function (page) {
            page.click('.editCustomDimension .create');
        }, done);
    });

    it('should be able to open created dimension and see same data but this time with tracking instructions', function (done) {
        capturePageWrap('manage_edit_action_dimension_verify_created', function (page) {
            page.click('.manageCustomDimensions .customdimension.8 .icon-edit');
        }, done);
    });

    it('should be possible to change an existing dimension', function (done) {
        capturePageWrap('manage_edit_action_dimension_withdata', function (page) {
            page.sendKeys(".editCustomDimension #name", 'ABC');
            page.click('.editCustomDimension #active');
            page.click('.editCustomDimension #casesensitive');
            page.click('.extraction.0 .icon-minus');
        }, done);
    });

    it('should updated an existing dimension', function (done) {
        capturePageWrap('manage_edit_action_dimension_updated', function (page) {
            page.click('.editCustomDimension .update');
        }, done);
    });

    it('should have actually updated values', function (done) {
        capturePageWrap('manage_edit_action_dimension_verify_updated', function (page) {
            page.click('.manageCustomDimensions .customdimension.8 .icon-edit');
        }, done);
    });

    it('should go back to list when pressing cancel', function (done) {
        capturePageWrap('manage_edit_action_dimension_cancel', function (page) {
            page.click('.editCustomDimension .cancel');
        }, done);
    });

    it('should disable configure button when no dimensions are left for a scope', function (done) {
        capturePageWrap('manage_configure_button_disabled', function (page) {
            page.click('.configure.visit');
            page.click('.editCustomDimension #active');
            page.sendKeys(".editCustomDimension #name", 'Last Name');
            page.click('.editCustomDimension .create');
        }, done);
    });

    it('should be possible to create a new dimension via URL', function (done) {
        capturePageWrap('manage_create_via_url', function (page) {
            page.load(manageUrl + '#?idDimension=0&scope=action');
        }, done);
    });

    it('should be possible to open an existing visit dimension via URL', function (done) {
        capturePageWrap('manage_edit_via_url', function (page) {
            page.load(manageUrl + '#?idDimension=5&scope=action');
        }, done);
    });

    /**
     * VISIT DIMENSION REPORTS MENU GROUPED
     */

    it('should group dimensions in menu once there are more than 3', function (done) {
        captureSelector('report_visit_mainmenu_grouped', '#secondNavBar,.menuDropdown .items', function (page) {
            page.load(reportUrl + "&idDimension=2");
            page.click('#UserCountryMap_realtimeWorldMap + li .menuDropdown')
        }, done);
    });

});