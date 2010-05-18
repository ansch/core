<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Search
 * @author Patrick Kellum
 * @author Stefano Garuti (ported to pnAPI)
 */

/**
* Main user function
*
* This function is the default function. Call the function to show the search form.
*
* @author Stefano Garuti
* @return string HTML string templated
*/
function search_user_main()
{
    // Security check will be done in form()
    return search_user_form();
}

/**
* Generate complete search form
*
* Generate the whole search form, including the various plugins options.
* It uses the Search API's getallplugins() function to find plugins.
*
* @author Patrick Kellum
* @author Stefano Garuti
*
* @return string HTML string templated
*/
function search_user_form($vars = array())
{
    // Security check
    if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // get parameter from input
    $vars['q'] = strip_tags(FormUtil::getPassedValue('q', '', 'REQUEST'));
    $vars['searchtype'] = FormUtil::getPassedValue('searchtype', SessionUtil::getVar('searchtype'), 'REQUEST');
    $vars['searchorder'] = FormUtil::getPassedValue('searchorder', SessionUtil::getVar('searchorder'), 'REQUEST');
    $vars['numlimit'] = ModUtil::getVar('Search', 'itemsperpage', 25);
    $vars['active'] = FormUtil::getPassedValue('active', SessionUtil::getVar('searchactive'), 'REQUEST');
    $vars['modvar'] = FormUtil::getPassedValue('modvar', SessionUtil::getVar('searchmodvar'), 'REQUEST');


    // this var allows the headers to not be displayed
    if (!isset($vars['titles']))
      $vars['titles'] = true;

    // set some defaults
    if (!isset($vars['searchtype']) || empty($vars['searchtype'])) {
        $vars['searchtype'] = 'AND';
    }
    if (!isset($vars['searchorder']) || empty($vars['searchorder'])) {
        $vars['searchorder'] = 'newest';
    }

    // reset the session vars for a new search
    SessionUtil::delVar('searchtype');
    SessionUtil::delVar('searchorder');
    SessionUtil::delVar('searchactive');
    SessionUtil::delVar('searchmodvar');

    // get all the search plugins
    $search_modules = ModUtil::apiFunc('Search', 'user', 'getallplugins');

    if (count($search_modules) > 0) {
        $plugin_options = array();
        foreach($search_modules as $mods) {
            // as every search plugins return a formatted html string
            // we assign it to a generic holder named 'plugin_options'
            // maybe in future this will change
            // we should retrieve from the plugins an array of values
            // and formatting it here according with the module's template
            // we have also to provide some trick to assure the 'backward compatibility'

            $plugin_options[$mods['title']] = ModUtil::apiFunc($mods['title'], 'search', 'options', $vars);
        }
        // Create output object
        $pnRender = & pnRender::getInstance('Search');
        // add content to template
        $pnRender->assign($vars);
        $pnRender->assign('plugin_options', $plugin_options);

        // Return the output that has been generated by this function
        return $pnRender->fetch('search_user_form.htm');
    } else {
        // Create output object
        $pnRender = & pnRender::getInstance('Search');
        // Return the output that has been generated by this function
        return $pnRender->fetch('search_user_noplugins.htm');
    }
}

/**
* Perform the search then show the results
*
* This function includes all the search plugins, then call every one passing
* an array that contains the string to search for, the boolean operators.
*
* @author Patrick Kellum
* @author Stefano Garuti
* @author Mark West
* @author Jorn Wildt
*
* @return string HTML string templated
*/
function search_user_search()
{
    // Security check
    if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // get parameter from HTTP input
    $vars = array();
    $vars['q'] = strip_tags(FormUtil::getPassedValue('q', '', 'REQUEST'));
    $vars['searchtype'] = FormUtil::getPassedValue('searchtype', SessionUtil::getVar('searchtype'), 'REQUEST');
    $vars['searchorder'] = FormUtil::getPassedValue('searchorder', SessionUtil::getVar('searchorder'), 'REQUEST');
    $vars['numlimit'] = ModUtil::getVar('Search', 'itemsperpage', 25);
    $vars['page'] = (int)FormUtil::getPassedValue('page', 1, 'REQUEST');

    // $firstpage is used to identify the very first result page
    // - and to disable calls to plugins on the following pages
    $vars['firstPage'] = !isset($_REQUEST['page']);

    // The modulename exists in this array as key, if the checkbox was filled
    $vars['active'] = FormUtil::getPassedValue('active', SessionUtil::getVar('searchactive'), 'REQUEST');

    // All formular data from the modules search plugins is contained in:
    $vars['modvar'] = FormUtil::getPassedValue('modvar', SessionUtil::getVar('searchmodvar'), 'REQUEST');

    if (empty($vars['q'])) {
        LogUtil::registerError (__('Error! You did not enter any keywords to search for.'));
        return pnRedirect(ModUtil::url('Search', 'user', 'main'));
    }

    // set some defaults
    if (!isset($vars['searchtype']) || empty($vars['searchtype'])) {
        $vars['searchtype'] = 'AND';
    } else {
        SessionUtil::setVar('searchtype', $vars['searchtype']);
    }
    if (!isset($vars['searchorder']) || empty($vars['searchorder'])) {
        $vars['searchorder'] = 'newest';
    } else {
        SessionUtil::setVar('searchorder', $vars['searchorder']);
    }
    if (!isset($vars['active']) || !is_array($vars['active']) || empty($vars['active'])) {
        $vars['active'] = array();
    } else {
        SessionUtil::setVar('searchactive', $vars['active']);
    }
    if (!isset($vars['modvar']) || !is_array($vars['modvar']) || empty($vars['modvar'])) {
        $vars['modvar'] = array();
    } else {
        SessionUtil::setVar('searchmodvar', $vars['modvar']);
    }

    // Create output object and check caching
    $pnRender = & pnRender::getInstance('Search');
    $pnRender->cache_id = md5($vars['q'] . $vars['searchtype'] . $vars['searchorder'] . UserUtil::getVar('uid')) . $vars['page'];
    // check if the contents are cached.
    if ($pnRender->is_cached('search_user_results.htm')) {
        return $pnRender->fetch('search_user_results.htm');
    }

    $result = ModUtil::apiFunc('Search', 'user', 'search', $vars);

    // Get number of chars to display in search summaries
    $limitsummary = ModUtil::getVar('Search', 'limitsummary');
    if (empty($limitsummary)) {
        $limitsummary = 200;
    }

    $pnRender->assign('resultcount', $result['resultCount']);
    $pnRender->assign('results', $result['sqlResult']);
    $pnRender->assign(ModUtil::getVar('Search'));
    $pnRender->assign($vars);
    $pnRender->assign('limitsummary', $limitsummary);

    // log the search if on first page
    if ($vars['firstPage']) {
        ModUtil::apiFunc('Search', 'user', 'log', $vars);
    }

    // Return the output that has been generated by this function
    return $pnRender->fetch('search_user_results.htm');
}


/**
 * display a list of recent searches
 *
 * @author Jorg Napp
 */
function Search_user_recent()
{
    // security check
    if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters from whatever input we need.
    $startnum = (int)FormUtil::getPassedValue('startnum', null, 'GET');

    // we need this value multiple times, so we keep it
    $itemsperpage = ModUtil::getVar('Search', 'itemsperpage');

    // get the
    $items = ModUtil::apiFunc('Search', 'user', 'getall', array('startnum' => $startnum, 'numitems' => $itemsperpage, 'sortorder' => 'date'));

    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $pnRender = & pnRender::getInstance('Search');

    // assign the results to the template
    $pnRender->assign('recentsearches', $items);

    // assign the values for the smarty plugin to produce a pager in case of there
    // being many items to display.
    $pnRender->assign('pager', array('numitems'     => ModUtil::apiFunc('Search', 'user', 'countitems'),
                                     'itemsperpage' => $itemsperpage));

    // Return the output that has been generated by this function
    return $pnRender->fetch('search_user_recent.htm');
}
