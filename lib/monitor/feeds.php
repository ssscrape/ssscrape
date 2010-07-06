<?php

class FeedTable extends Table {

    function FeedTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'url', 'title', 'task', 'kind', 'tags', 'mod_date', 'items', 'tracks', 'errors', '2_weeks'));
        $this->set_field_option('id', 'sql-name', 'f.id');
        $this->set_field_option('url', 'sql-name', 'f.url');
        $this->set_field_option('items', 'num');
        $this->set_field_option('errors', 'num');
        $this->set_field_option('title', 'truncate', 30);
        
        $this->set_field_option('url', 'search');
        $this->set_field_option('title', 'search');
        
        $this->process_options($params);
    }

    function get_disabled_tasks() {
      $q = "SELECT args FROM ssscrapecontrol.ssscrape_task WHERE state = 'disabled'";
      $db = DB::get_instance();
      $rows = $db->prepare_execute_fetch_all($q);
      $disabled_tasks = array();
      foreach($rows as $row) {
        $disabled_tasks[$row['args']] = 1; // dumy value
      }
      return $disabled_tasks;
    }
    
    function show() {
        $this->disabled_tasks = $this->get_disabled_tasks();
        
        $q = "SELECT f.id, f.url, f.title, '0' task, 'enabled' task_state, m.kind, m.tags, f.mod_date, 
                     '0' items, '0' errors, f.id 2_weeks
              FROM ssscrape.ssscrape_feed f 
                   LEFT JOIN ssscrape_feed_metadata m ON f.id=m.feed_id 
              ?where?";
        $this->m->append(ax_p(ax_a_href("Create new feed", "editor/?show=feeds&id=NEW")));

        $this->run_query($q);
    }
    function display_id($id, $row) {
        return ax_a_href_title($id, Request::url(false) . "editor/?show=feeds&id=$id", "Edit feed $id");
    }


    function display_url($url, $row) {
        return ax_a_href_title($url, $url, "Go to " . $url);
    }

    function display_items($items, $row) {
        $items = ax_a_href_title(ax_raw('&rarr;'), 
                                 $this->make_url(0, array('show'=>'items', 'feed'=>$row['id'])),
                                 "Show items for feed " . $row['id']);
        return $items;
    }

    function check_items($items, $row) {
        if ($row['task_state'] != 'enabled') {
            # don't check anything unless the feed fetching task is enabled
            return true;
        }
        /*
        if ($items == 0) {
            return false;
        } */
        return true;
    }

    function check_errors($errors, $row) {
        if ($row['kind'] != 'full' && $errors != 0) {
            return false;
        } 
        return true;
    }

    function display_errors($errors, $row) {
        if ($row['kind'] != 'full' && $errors != 0) {
            # if something looks wrong, make a link to problematic items 
            if ($errors) {
                $errors = ax_a_href_title($errors, 
                                         $this->make_url(0, array('show'=>'items', 'feed'=>$row['id'], 'order'=>'content', 'interval' => $this->interval)),
                                         "Show problematic items for feed " . $row['id']);
            }
        } else {
            $errors = "";
        }
        return $errors;
    }


    function check_task($task, $row) {
        if (array_key_exists("-u '". $row['url'] ."'", $this->disabled_tasks)) {
          return false;
        }
        return ($row['task_state'] == 'enabled');
    }

    function display_task($task, $row) {
      return ax_a_href_title(ax_raw('&rarr;'), $this->make_url(0, array('show' => 'tasks', 'args' => 'LIKE:%'. $row['url'] .'%')), 'Go to this site');            
    }
    
    function display_2_weeks($feed_id, $row) {
            $plot_file = "plots/feed_statistics/$feed_id.png"; 
            if (file_exists($plot_file)) {
                $descr = "Feed $feed_id: items per day in the previous 2 weeks";
                return ax_img_src_alt_title($plot_file, $descr, $descr); 
            } else {
                return "";
            }
    }
    
    function display_tracks($tracks, $row) {
      return ax_a_href_title(ax_raw('&rarr;'), $this->make_url(0, array('show' => 'tracks', 'feed' => $row['id'])), 'Go to this site');      
    }
}

?>
