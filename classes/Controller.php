<?php

/**
 * The controllers.
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
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

namespace Realblog;

use stdClass;

/**
 * The controllers.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Controller
{
    /**
     * Initializes the plugin.
     *
     * @return void
     *
     * @global array The configuration of the plugin.
     */
    public function init()
    {
        global $plugin_cf;

        if ($plugin_cf['realblog']['auto_publish']) {
            $this->autoPublish();
        }
        if ($plugin_cf['realblog']['auto_archive']) {
            $this->autoArchive();
        }
        if ($plugin_cf['realblog']['rss_enabled']) {
            $this->emitAlternateRSSLink();
            if (isset($_GET['realblog_feed']) && $_GET['realblog_feed'] == 'rss') {
                $this->deliverFeed();
            }
        }
        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(true);
            }
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
    }

    /**
     * Returns whether the plugin administration is requested.
     *
     * @return bool
     *
     * @global string Whether the plugin administration is requested.
     */
    protected function isAdministrationRequested()
    {
        global $realblog;

        return isset($realblog) && $realblog == 'true';
    }

    /**
     * Handles the plugin administration.
     *
     * @return void
     */
    protected function handleAdministration()
    {
        $controller = new AdminController();
        $controller->dispatch();
    }

    /**
     * Emits the alternate RSS link.
     *
     * @return void
     *
     * @global string The (X)HTML for the head element.
     */
    protected function emitAlternateRSSLink()
    {
        global $hjs;

        $hjs .= tag(
            'link rel="alternate" type="application/rss+xml"'
            . ' href="./?realblog_feed=rss"'
        );
    }

    /**
     * Displays the realblog's topic with status = published.
     *
     * @param array  $showSearch  Whether to show the searchform.
     * @param string $realBlogCat FIXME
     *
     * @return string (X)HTML.
     *
     * @global array  The configuration of the plugins.
     */
    public function blog($showSearch = false, $category = 'all')
    {
        global $plugin_cf, $_Realblog_controller;

        $id = $this->getPgParameter('realblogID');
        $html = '';
        if (!isset($id)) {
            if ($showSearch) {
                $view = new SearchFormView($this->getYear());
                $html .= $view->render();
            }
            $order = ($plugin_cf['realblog']['entries_order'] == 'desc')
                ? -1 : 1;
            $limit = $plugin_cf['realblog']['entries_per_page'];
            $page = $_Realblog_controller->getPage();
            $search = $this->getPgParameter('realblog_search');
            $articleCount = DB::countArticlesWithStatus(array(1), $category, $search);
            $pageCount = ceil($articleCount / $limit);
            $page = min(max($page, 1), $pageCount);
            $articles = DB::findArticles(1, $limit, ($page-1) * $limit, $order, $category, $search);
            if ($search) {
                $html .= $this->renderSearchResults('blog', $articleCount);
            }
            $view = new ArticlesView($articles, $articleCount, $page, $pageCount);
            $html .= $view->render();
        } else {
            $html .= $this->renderArticle($id);
        }
        return $html;
    }

    /**
     * Renders a blog article.
     *
     * @param int $id An article ID.
     *
     * @return string (X)HTML.
     *
     * @global array  The headings of the pages.
     * @global int    The current page index.
     * @global string The page title.
     * @global string The value of the page's meta description.
     */
    protected function renderArticle($id)
    {
        global $h, $s, $title, $description;

        $article = DB::findById($id);
        if (isset($article)) {
            $title .= $h[$s] . " \xE2\x80\x93 " . $article->title;
            $description = $this->getDescription($article);
            $view = new ArticleView($id, $article, $this->getPage());
            return $view->render();
        }
    }

    /**
     * Displays the archived realblog topics.
     *
     * @param mixed $showSearch Whether to show the search form.
     *
     * @return string (X)HTML.
     */
    public function archive($showSearch = false)
    {
        $realblogID = $this->getPgParameter('realblogID');
        $html = '';
        if (!isset($realblogID)) {
            if ($showSearch) {
                $view = new SearchFormView($this->getYear());
                $html .= $view->render();
            }

            if ($search = $this->getPgParameter('realblog_search')) {
                $articles = DB::findArchivedArticlesContaining($search);
                $articleCount = count($articles);
                $html .= $this->renderSearchResults('archive', $articleCount);
            } else {
                $articleCount = DB::countArticlesWithStatus(array(2));
                $articles = array();
            }

            $view = new ArchiveView($articles, $articleCount);
            $html .= $view->render();
        } else {
            $html .= $this->renderArticle($realblogID);
        }
        return $html;
    }

    /**
     * Displays the realblog topics with a link to the blog page from the template.
     *
     * A page calling #cmsimple $output.=showrealblog();# must exist.
     * Options: realblog_page [required] : this is the page containing the
     *          showrealblog() function
     *
     * @param mixed $pageUrl A URL of a page where the blog is shown.
     *
     * @return string (X)HTML.
     *
     * @global array  The URLs of the pages.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     */
    public function link($pageUrl)
    {
        global $u, $plugin_cf, $plugin_tx;

        if (!in_array($pageUrl, $u)) {
            return '';
        }
        if ($plugin_cf['realblog']['links_visible'] <= 0) {
            return '';
        }
        $html = '<p class="realbloglink">'
            . $plugin_tx['realblog']['links_visible_text'] . '</p>';
        $articles = DB::findArticles(1, $plugin_cf['realblog']['links_visible']);
        if (!empty($articles)) {
            $html .= '<div class="realblog_tpl_show_box">';
            foreach ($articles as $article) {
                $html .= $this->renderArticleLink($article, $pageUrl);
            }
            $html .= '<div style="clear: both;"></div></div>';
        } else {
            $html .= $plugin_tx['realblog']['no_topics'];
        }
        return $html;
    }

    /**
     * Renders a link to an article.
     *
     * @param stdClass $article An article.
     * @param string   $pageURL The URL of the blog page.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderArticleLink(stdClass $article, $pageURL)
    {
        global $plugin_tx;

        $url = $this->url(
            $pageURL, $article->title, array(
                'realblogID' => $article->id
            )
        );
        return '<div class="realblog_tpl_show_date">'
            . date($plugin_tx['realblog']['date_format'], $article->date)
            . '</div>'
            . '<div class="realblog_tpl_show_title">'
            . '<a href="' . XH_hsc($url) . '">' . $article->title .'</a>'
            . '</div>';
    }

    /**
     * Returns a graphical hyperlink to the RSS feed.
     *
     * @return string (X)HTML.
     *
     * @global array  The paths of system files and folders.
     * @global array  The localization of the plugins.
     */
    public function feedLink()
    {
        global $pth, $plugin_tx;

        return '<a href="./?realblog_feed=rss">'
            . tag(
                'img src="' . $pth['folder']['plugins'] . 'realblog/images/rss.png"'
                . ' alt="' . $plugin_tx['realblog']['rss_tooltip'] . '" title="'
                . $plugin_tx['realblog']['rss_tooltip'] . '" style="border: 0;"'
            )
            . '</a>';

    }

    /**
     * Delivers the RSS feed.
     *
     * @return void
     */
    protected function deliverFeed()
    {
        global $plugin_cf;

        header('Content-Type: application/rss+xml; charset=UTF-8');
        $count = $plugin_cf['realblog']['rss_entries'];
        $view = new RSSFeed(DB::findFeedableArticles($count));
        echo $view->render();
        exit();
    }

    /**
     * Changes status to published when publishing date is reached.
     *
     * @return void
     */
    protected function autoPublish()
    {
        DB::autoChangeStatus('publishing_date', 1);
    }

    /**
     * Changes status to archived when archive date is reached.
     *
     * @return void
     */
    protected function autoArchive()
    {
        DB::autoChangeStatus('archiving_date', 2);
    }

    /**
     * Returns the meta description for an article.
     *
     * @param stdClass $article An article.
     *
     * @return string
     */
    protected function getDescription(stdClass $article)
    {
        return utf8_substr(
            html_entity_decode(
                strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'
            ),
            0, 150
        );
    }

    /**
     * Returns the value of a POST or GET parameter; <var>null</var> if not set.
     *
     * @param string $name A parameter name.
     *
     * @return string
     */
    public function getPgParameter($name)
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns the requested page number, and stores it in a cookie.
     *
     * @return int
     */
    public function getPage()
    {
        if (isset($_GET['realblog_page'])) {
            $page = (int) $_GET['realblog_page'];
            $_COOKIE['realblog_page'] = $page;
            setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_page'])) {
            $page = (int) $_COOKIE['realblog_page'];
        } else {
            $page = 1;
        }
        return $page;
    }

    /**
     * Returns the requested year, and stores it in a cookie.
     *
     * @return int
     */
    public function getYear()
    {
        if (isset($_GET['realblog_year'])) {
            $year = (int) $_GET['realblog_year'];
            $_COOKIE['realblog_year'] = $year;
            setcookie('realblog_year', $year, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_year'])) {
            $year = (int) $_COOKIE['realblog_year'];
        } else {
            $year = date('Y');
        }
        return $year;
    }

    /**
     * Returns a requested filter, and stores it in a cookie.
     *
     * @param int $num A filter number (1-3).
     *
     * @return bool
     */
    public function getFilter($num)
    {
        if (isset($_POST["realblog_filter$num"])) {
            $filter = ($_POST["realblog_filter$num"] == 'on');
            $_COOKIE["realblog_filter$num"] = $filter ? 'on' : '';
            setcookie("realblog_filter$num", $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE["realblog_filter$num"])) {
            $filter = ($_COOKIE["realblog_filter$num"] == 'on');
        } else {
            $filter = false;
        }
        return $filter;
    }

    /**
     * Constructs a front-end URL.
     *
     * @param string $pageUrl      A page URL.
     * @param string $articleTitle An article title.
     * @param array  $params       A map of names -> values.
     *
     * @return string
     */
    public function url($pageUrl, $articleTitle = null, $params = array())
    {
        global $sn;

        $replacePairs = array(
            //'realblogID' => 'id',
            //'realblog_page' => 'page'
        );
        $url = $sn . '?' . $pageUrl;
        if (isset($articleTitle)) {
            $url .= '&' . uenc($articleTitle);
        }
        ksort($params);
        foreach ($params as $name => $value) {
            $url .= '&' . strtr($name, $replacePairs) . '=' . $value;
        }
        return $url;
    }

    /**
     * Renders the search results.
     *
     * @param string $what  Which search results ('blog' or 'archive').
     * @param string $count The number of hits.
     *
     * @return string (X)HTML.
     *
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    protected function renderSearchResults($what, $count)
    {
        global $su, $plugin_tx;

        $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
        $search = $this->getPgParameter('realblog_search');
        $words = '"' . $search . '"';
        return '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>'
            . XH_hsc($words) . '</b></p>'
            . '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
            . $count . '</b></p>'
            . '<p><a href="' . XH_hsc($this->url($su)) . '"><b>'
            . $plugin_tx['realblog'][$key] . '</b></a></p>';
    }

    /**
     * Parses a date string and returns a timestamp.
     *
     * @param mixed $date A date string in ISO format.
     *
     * @return int
     */
    public function stringToTime($date)
    {
        $parts = explode('-', $date);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }

}

?>
