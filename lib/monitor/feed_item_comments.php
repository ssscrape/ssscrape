<?php

class FeedItemCommentsTable extends Table {

    function FeedItemCommentsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'item', 'feed', 'tags', 'pub_date', 'mod_date', 'content', 'author', 'jobs', 'link'));
        $this->set_field_option('item', 'sql-name', 'feed_item_id');
        $this->set_field_option('feed', 'sql-name', 'i.feed_id');
        //$this->set_field_option('item', 'sql-name', 'c.feed_item_id');
        $this->set_field_option('pub_date', 'datetime-key');
        $this->set_field_option('title', 'expand', 50);
        $this->set_field_option('content', 'expand', 90);
        $this->set_default_ordering('pub_date', 'DESC');
        $this->process_options($params);
        $this->max_limit = 200;
        $this->interval = '*';
    }

    function show() {
        $q = "SELECT c.id, c.feed_item_id item, c.pub_date, c.mod_date, c.comment content, c.author, c.guid url, i.feed_id feed, m.tags tags 
              FROM ssscrape_feed_item_comment c 
                  LEFT JOIN ssscrape_feed_item i ON c.feed_item_id=i.id 
                  LEFT JOIN ssscrape_feed_metadata m ON i.feed_id=m.feed_id
              ?where?";
        
        $this->run_query($q, 'pub_date');
    }

    function display_item($item, $row) {
        if ($item) {
            $item = ax_a_href_title($row['item'], 
                                    $this->make_url(0, array('show'=>'items', 'id'=>$row['item'])),
                                    "Show item " . $row['item']);
        }
        return $item;
    }

    function display_link($guid, $row) {
        if ($guid and preg_match('/^http/', $guid)) {
            $guid = ax_a_href_title(ax_raw("&rarr;"), $guid, "permalink");
        }
        return $guid;
    }

    function check_link($guid, $row) {
        if ($guid and preg_match('/^http/', $guid)) {
            return true;
        }
        return false;
    }

    function display_jobs($jobs, $row) {
        $id = $row['item'];
        $jobs = ax_a_href_title(ax_raw("log&rarr;"), 
                                $this->make_url(0, array('show'=>'jobLogs', 'type' => 'LIKE:comments', 'args'=>"LIKE:% $id %", 'interval'=>'7 DAY')),
                                "show job logs for comments of item $id");
        return $jobs;
    }

    function check_content($content, $row) {
        if (!$content or !preg_match('/\S/', $content) or strlen($content)<10) {
            return false;
        } 
        return true;
    }

}

?>
