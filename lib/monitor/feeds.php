<?php

class FeedTable extends Table {

    function FeedTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'url', 'title', 'task', 'periodicity', 'kind', 'tags', 'mod_date', 'items', 'errors', '2_weeks'));
        $this->set_field_option('id', 'sql-name', 'f.id');
        $this->set_field_option('url', 'sql-name', 'f.url');
        $this->set_field_option('items', 'num');
        $this->set_field_option('errors', 'num');
        $this->set_field_option('title', 'truncate', 30);
        $this->process_options($params);
    }

    function show() {
        $q = "SELECT f.id, f.url, f.title, t.id task, t.periodicity periodicity, t.state task_state, m.kind, m.tags, f.mod_date, 
                     count(i.feed_id) items, count(i.feed_id)-count(i.content_clean) errors, f.id 2_weeks
              FROM ssscrape.ssscrape_feed f 
                   LEFT JOIN ssscrape_feed_metadata m ON f.id=m.feed_id 
                   LEFT JOIN (SELECT feed_id, content_clean 
                              FROM ssscrape_feed_item 
                              WHERE ?temp-constraint?) i 
                        ON f.id=i.feed_id 
                   LEFT JOIN ssscrapecontrol.ssscrape_task t ON LOCATE(f.url, t.args)
              ?where?
              GROUP BY f.id";

        $this->m->append(ax_p(ax_a_href("Create new feed", "editor/?show=feeds&id=NEW")));

        $this->run_query($q, 'pub_date');
    }
    function display_id($id, $row) {
        return ax_a_href_title($id, Request::url(false) . "editor/?show=feeds&id=$id", "Edit feed $id");
    }


    function display_url($url, $row) {
        return ax_a_href_title($url, $url, "Go to " . $url);
    }

    function display_items($items, $row) {
        if ($items) {
            $items = ax_a_href_title($items, 
                                     $this->make_url(0, array('show'=>'items', 'feed'=>$row['id'])),
                                     "Show items for feed " . $row['id']);
        }
        return $items;
    }

    function check_items($items, $row) {
        if ($row['task_state'] != 'enabled') {
            # don't check anything unless the feed fetching task is enabled
            return true;
        }
        if ($items == 0) {
            return false;
        } 
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
        return ($row['task_state'] == 'enabled');
    }

    function display_periodicity($value, $row) {
        return AnewtDateTime::time($value);
    }


    function display_2_weeks($feed_id, $row) {
        #$plot_file = "plots/feed_statistics/$feed_id.png"; 
        $plot_file = "get_plot.php?feed=$feed_id";
        $descr = "Feed $feed_id: items per day in the previous 2 weeks";
        return ax_img_src_alt_title($plot_file, "?", $descr, array('height'=>16, 'width'=>76)); 
    }
}

?>
