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
    var reportUrl = "?" + urlBase + "#?" + generalParams;

    var reportUrlDimension2 = reportUrl + "&category=General_Visitors&subcategory=customdimension2";
    var reportUrlDimension3 = reportUrl + "&category=General_Actions&subcategory=customdimension3";
    var reportUrlDimension4 = reportUrl + "&category=General_Actions&subcategory=customdimension4";

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
            page.load(reportUrlDimension2);
        }, done);
    });

    it('should add a menu item for each active visit dimension', function (done) {
        captureSelector('report_visit_mainmenu', '#secondNavBar', function (page) {
            // we only capture a screenshot of a different part of the page, no need to do anything
        }, done);
    });

    it('should add visit dimensions to goals report', function (done) {
        captureSelector('report_goals_overview', '.reportsByDimensionView', function (page) {
            page.load( "?" + urlBase + "#?" + generalParams + "&category=Goals_Goals&subcategory=General_Overview");
            page.click('.reportsByDimensionView .dimension:contains(MyName1)');
        }, done);
    });

    /**
     * ACTION DIMENSION REPORTS
     */

    it('should show the report for the selected action dimension', function (done) {
        capturePageWrap('report_action', function (page) {
            page.load(reportUrlDimension3);
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
            page.click('[data-footer-icon-id="insightsVisualization"]');
        }, done);
    });

    it('should show an error when trying to open an inactive dimension', function (done) {
        expect.page("").contains(".pageWrap:contains('This page does not exist')", function (page) {
            page.load(reportUrlDimension4);
        }, done);
    });

    it('should be able to open segmented visitor log', function (done) {
        captureSelector('report_actions_segmented_visitorlog', popupSelector, function (page) {
            page.load(reportUrlDimension3);
            triggerRowAction(page, 'en', 'actionSegmentVisitorLog');
        }, done);
    });

    it('should be able to open row evolution', function (done) {
        captureSelector('report_actions_rowevolution', popupSelector, function (page) {
            page.load(reportUrlDimension3);
            triggerRowAction(page, 'en', 'actionRowEvolution');
        }, done);
    });

    it('should be able to show subtable and offer all row actions if scope is action', function (done) {
        capturePageWrap('report_action_subtable', function (page) {
            page.load(reportUrlDimension3);
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


});