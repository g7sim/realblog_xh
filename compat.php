<?php

/**
 * Backward compatibility.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * Renders the published articles.
 *
 * @param string $options     An option string (options: showsearch).
 * @param string $realBlogCat A category.
 *
 * @return string (X)HTML.
 */
function showrealblog($options = null, $realBlogCat = 'all')
{
    $includesearch = false;
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        switch (strtolower($arguments['showsearch'])) {
        case '0':
        case 'false':
            $includesearch = false;
            break;
        case '1':
        case 'true':
            $includesearch = true;
            break;
        }
    }
    return Realblog_blog($includesearch, $realBlogCat);
}

/**
 * Renders the archived articles.
 *
 * @param string $options An option string (options: showsearch).
 *
 * @return string (X)HTML.
 */
function showrealblogarchive($options = null)
{
    $includesearch = false;
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        $argument = strtolower($arguments['showsearch']);
        switch ($argument) {
        case '0':
        case 'false':
            $includesearch = false;
            break;
        case '1':
        case 'true':
            $includesearch = true;
            break;
        }
    }
    return Realblog_archive($includesearch);
}

/**
 * Renders the published topics with a link to the blog page from the template.
 *
 * @param string $options An option string (options: realblogpage).
 *
 * @return string (X)HTML.
 */
function realbloglink($options)
{
    $realblog_page = '';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['realblogpage'])) {
        $realblog_page = $arguments['realblogpage'];
    }
    return Realblog_link($realblog_page);
}

/**
 * Renders a hyperlink to the newsfeed.
 *
 * @return string (X)HTML.
 */
function realblog_rss_adv()
{
    return Realblog_feedLink();
}

/**
 * A dummy function for categories.
 *
 * @return void
 */
function rbCat()
{
    return;
}

/**
 * Restricts access to the comments form to members.
 *
 * @return void
 *
 * @global array The configuration of the plugins.
 */
function commentsMembersOnly()
{
    global $plugin_cf;

    $plugin_cf['realblog']['comments_form_protected'] = 'true';
}

?>
