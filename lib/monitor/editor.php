<?php

anewt_include('xhtml');
anewt_include('form');
anewt_include('form/renderer/default');


require('monitor.php');
require('editor/task.php');
require('editor/feed.php');

class SsscrapeEditor extends SsscrapeMonitor
{

    public $forms = array(
        array(
            'name'=>'tasks',
            'class'=>'TaskForm',
            'descr'=>'Editing task'
        ),
        array(
            'name'=>'feeds',
            'class'=>'FeedForm',
            'descr'=>'Editing feed'
        ),
    );


    function SsscrapeEditor()
    {
        AnewtPage::AnewtPage();
        //SsscrapeMonitor::SsscrapeMonitor();
 
        /* Provide a list of blocks */
        $this->set('blocks', array('header', 'content', 'footer'));
 
        /* Set some default values */
        $this->set('title', 'Ssscrape editor');
        $this->set('default-block', 'content');

        $this->add_stylesheet_href('../style.css');

        $this->init_db();

    }

    function build_header() {
        return ax_img_src_alt(IMG_URL . "ssscrape-logo-horizontal.png", "Ssscrape monitor");
    }
 
    function build_footer() {
        return ax_p('Contact: jijkoun@uva.nl');
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

        // Go to monitor if no id is specified
        $id = array_get_default($args, 'id', '');
        if ($id == '') {
            header('Location: ../?' . http_build_query($args));
            exit;
        }
        
        // Display menu
        $menu = $this->display_menu($main_show);
        $this->append(ax_ul_items($menu, array('id'=>'tablist')));

        
        foreach($this->forms as $form) {
            if ($form['name'] == $show) {
                break;
            }
        }
        if (!$form or $form['name'] != $show) {
            return;
        }

        $title = $form['descr'];
        $form = new $form['class']($args);
        if ($form->get_data($id)) {
            // Editing existing entity
            $title .= " $id";
        } else {
            // Adding new entity
            $form->set_control_value('id', '');
            $id = "";
            unset($args['id']);
            $title .= " (new)";
        }

        if (Request::is_post()) {
            $form->fill($_POST);
            $msg = $form->save_data();
            $id = $form->get_control_value('id');
            $form->get_data($id);
            if ($msg != "") {
                $this->append(ax_p($msg));
            }

        }

        $fr = new AnewtFormRendererDefault();
        $fr->set_form($form);
        
        $this->append(ax_h2($title));
        $this->append($fr);

          
    }
}
 
function run_editor() {
    $m = &new SsscrapeEditor();
    $action = array_get_default($_GET, 'action', 'show');
    if ($action == 'show') {
        $m->display($_GET);
        $m->flush();
    } elseif ($action == 'delete') {
        $db = DB::get_instance();
        $what = array_get_default($_GET, 'what', '');
        $id = array_get_default($_GET, 'id', null);
        $url = array_get_default($_GET, 'url', dirname(dirname($_SERVER['PHP_SELF'])));
        $url = "http://" . $_SERVER['HTTP_HOST'] . $url;
        if ($what == 'job' && !is_null($id)) {
            $db->prepare_execute("INSERT INTO ssscrapecontrol.ssscrape_job_log SELECT * FROM ssscrapecontrol.ssscrape_job WHERE id=?str?", $id);
            $db->prepare_execute("DELETE FROM ssscrapecontrol.ssscrape_job WHERE id=?str?", $id);
        }
        header("Location: $url");
    }

}
 
?>
