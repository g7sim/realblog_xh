<?php

/**
 * The interface for comment plugins.
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
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The interface for comment plugins.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
interface Realblog_CommentsBridge
{
    /**
     * Returns the number of comments on a certain topic.
     *
     * @param string $topic A topic ID.
     *
     * @return int
     */
    static public function count($topic);

    /**
     * Handles the comment functionality of a certain topic.
     *
     * Normally returns the comments of the topic, but has to cater for adding
     * of new comments, and other advanced functionality the comments plugin may
     * offer.
     *
     * Usually, you can simply return the result of calling the comments
     * plugin's plugin call, e.g. <code>return comments($topic)</code>.
     *
     * @param string $topic A topic ID.
     *
     * @return string (X)HTML.
     */
    static public function handle($topic);

    /**
     * Returns the URL for editing comments on a certain topic. Returns false,
     * if there is no sensible URL.
     *
     * @param string $topic A topic ID.
     *
     * @return string
     */
    static public function getEditUrl($topic);
}

?>
