<?php

class EnclosuresTable extends Table {

    function EnclosuresTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'feed', 'feed_item', 'link', 'title', 'description', 'mime', 'pub_date', 'mod_date'));
        $this->set_field_option('pub_date', 'sql-name', 'e.pub_date');
        $this->set_field_option('pub_date', 'datetime-key');
        $this->set_field_option('feed', 'sql-name', 'i.feed');
        $this->set_field_option('feed_item', 'sql-name', 'e.feed_item_id');
        //$this->set_field_option('periodicity', 'time');
        //$this->set_field_option('id', 'sql-name', 'e.id');
        //$this->set_field_option('type', 'sql-name', 't.type');
        $this->set_field_option('title', 'truncate', 30);
        $this->set_field_option('description', 'truncate', 30);
        
        $this->set_default_ordering('id', 'DESC'); 
        $this->process_options($params);
    }
    
    function show() {
      $q = "SELECT
              e.id, i.feed, e.feed_item_id AS feed_item, e.link, e.title, e.description, e.mime, e.pub_date, e.mod_date 
            FROM
              `ssscrape_enclosure` e
            LEFT JOIN (SELECT `id` AS `orig_id`, `feed_id` AS `feed` FROM 
              `ssscrape_feed_item`) i
            ON
              i.orig_id = e.feed_item_id
            ?where?";
      $this->run_query($q, 'e.pub_date');
    }
    
    function display_link($link, $row) {
      return ax_a_href_title("link", $link, "Go to " . $link);
    }
    
    function display_feed($feed, $row) {
      return ax_a_href_title($feed, $this->make_url(1, array('feed' => $feed)), "View enclosures for this feed");
    }

    function display_feed_item($feed_item, $row) {
      return ax_a_href_title($feed_item, $this->make_url(1, array('feed_item' => $feed_item)), "View enclosures for this item");
    }

}