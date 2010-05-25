<?php

anewt_include('page');
anewt_include('database');

require('utils.php');
require('home.php');
require('table.php');
require('feeds.php');
require('feed_items.php');
require('feed_item_comments.php');
require('feed_item_comment_counts.php');
require('enclosures.php');
require('tracks.php');
require('tracks_counts.php');
require('tasks.php');
require('jobs.php');
require('job_logs.php');
require('resources.php');
require('errors.php');

class SsscrapeMonitor extends AnewtPage
{
    public $interval = "1 DAY"; // default interval

    public $pages = array(
        array(
            'name'=>'home',
            'class'=>'HomePage',
            'descr'=>'Ssscrape monitor'
        ),
        array(
            'name'=>'feeds',
            'class'=>'FeedTable',
            'descr'=>'Basic statistics for feeds'
        ),
        array(
            'name'=>'items',
            'class'=>'FeedItemTable',
            'descr'=>'Information about feeds items'
        ),
        array(
            'name'=>'comments',
            'class'=>'FeedItemCommentsTable',
            'descr'=>'Information about comments',
            'parent'=>'comments'
        ),
        array(
            'name'=>'commentCounts',
            'class'=>'FeedItemCommentCountsTable',
            'descr'=>'Basic statistics for comments',
            'tab'=>False,
            'parent'=>'comments'
        ),
        array(
            'name'=>'enclosures',
            'class'=>'EnclosuresTable',
            'descr'=>'Information about enclosures'
        ),
        array(
            'name'=>'tracks',
            'class'=>'TracksTable',
            'descr'=>'Information about tracks',
            'parent' => 'tracks'
        ),
        array(
            'name'=>'trackCounts',
            'class'=>'TrackCountsTable',
            'descr'=>'Basic statistics about tracks',
            'tab' => false,
            'parent' => 'tracks'
        ),
        array(
            'name'=>'tasks',
            'class'=>'TasksTable',
            'descr'=>'Periodic tasks'
        ),
        array(
            'name'=>'jobs',
            'class'=>'JobsTable',
            'descr'=>'Jobs in the job queue'
        ),
        array(
            'name'=>'jobLogs',
            'class'=>'JobLogsTable',
            'descr'=>'Jobs in the log table'
        ),
        array(
            'name'=>'resources',
            'class'=>'ResourcesTable',
            'descr'=>'Resources shared by jobs'
        ),
        array(
            'name'=>'errors',
            'class'=>'ErrorsTable',
            'descr'=>'Errors in job execution'
        ),
    );

    function SsscrapeMonitor()
    {
        AnewtPage::__construct();
 
        /* Provide a list of blocks */
        $this->set('blocks', array('header', 'content', 'footer'));
 
        /* Set some default values */
        $this->set('title', 'Ssscrape monitor');
        $this->set('default-block', 'content');

        $this->add_stylesheet_href('style.css');

        $this->init_db();

    }

    function build_header() {
        return ax_img_src_alt(IMG_URL . "ssscrape-logo-horizontal.png", "Ssscrape monitor");
    }
 
    function build_footer() {
        return ax_p('Contact: jijkoun@uva.nl');
    }

    function make_url($params, $params_add=null, $params_del=null) {
        if ($params_add) {
            foreach ($params_add as $key => $val) {
                $params[$key] = $val;
            }
        }
        if ($params_del) {
            foreach ($params_del as $key) {
                unset($params[$key]);
            }
        }
        return Request::url(false) . '?' . http_build_query($params);
    }

    function get_page_info($page_name) {
        foreach($this->pages as $page) {
            if ($page['name'] == $page_name) {
                return $page;
            }
        }
    }

    function init_db() {
        $conf = read_config();
        # FIXME: should be read from ssscrape's local.conf  
        #$db_settings = array(
        #    'hostname' => 'grabber',
        #    'username' => 'ssscrape',
        #    'password' => 'S3crape;',
        #    'database' => 'ssscrape',
        #    'charset' => 'utf8',
        #    'use_unicode' => true,
        #    );

        if (array_key_exists('database-web', $conf)) {
            $section = 'database-web';
        } else {
            $section = 'database-workers';
        }
        $db_settings = array(
            'hostname' => $conf[$section]['hostname'],
            'username' => $conf[$section]['username'], 
            'password' => $conf[$section]['password'], 
            'database' => $conf[$section]['database'], 
            'charset' => 'utf8',
            'use_unicode' => true,
            );
        $db = &DB::get_instance('mysql', $db_settings);
        $db->prepare_execute('SET NAMES "UTF8"');
        unset($db);

    }

    function display_menu($show, $parent = null) {
        $menu = array();
        foreach($this->pages as $page) {
            $attrs = array();
            if ($page['name'] == $show) {
                $attrs['class'] = 'current'; 
            }
            if ($parent) {
                $tab = array_key_exists('parent', $page) ? ($page['parent'] == $parent) : False;
            } else {
                $tab = array_key_exists('tab', $page) ? $page['tab'] : True;
            }
            if ($tab) { 
                array_push($menu, ax_li(ax_a_href_title(ucfirst($page['name']), 
                                                    $this->make_url(array('show'=>$page['name'])),
                                                    $page['descr'],
                                                    $attrs)));
            }
        }
        return $menu;
    }

    function display($args) {
        $show = array_get_default($args, 'show', 'home');
        $page = null;
        foreach($this->pages as $page) {
            if ($page['name'] == $show) {
                break;
            }
        }
        if (!$page) {
            $show = 'home';
            $main_show = 'home';
        } else {
            $main_show = array_key_exists('parent', $page) ? $page['parent'] : $show;
        }
        $args['show'] = $show;

        $menu = $this->display_menu($main_show);
        $this->append(ax_ul_items($menu, array('id'=>'tablist')));

        
        foreach($this->pages as $page) {
            if ($page['name'] == $show) {
                // show header first
                $this->append(ax_h2($page['descr']));
                // then submenu
                $submenu = $this->display_menu($show, $main_show);
                $this->append(ax_ul_items($submenu, array('id'=>'subtablist')));

                // and finally the page
                $display = new $page['class']($this, $args, $this->pages);
                $display->show();
            }
        }
    }
}
 

function run_monitor() { 
    $m = &new SsscrapeMonitor();
    $m->display($_GET);
    $m->flush();
}

?>
