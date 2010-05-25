<?php

class TrackCountsTable extends Table {

    function TrackCountsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('site', 'tracks'));
        //$this->set_field_option('id', 'sql-name', 'f.id');
        //$this->set_field_option('url', 'sql-name', 'f.url');
        //$this->set_field_option('comments', 'num');
        //$this->set_field_option('kind', 'sql-name', 'm.kind');
        //$this->set_field_option('tags', 'sql-name', 'm.tags');
        //$this->set_field_option('c.pub_date', 'datetime-key');
        $this->process_options($params);
        //$this->max_limit = 200;
    }

    function show() {
        //$q = "SELECT f.id, f.url, f.title, m.kind, m.tags, count(c.feed_item_id) comments, f.mod_date FROM ssscrape_feed f LEFT JOIN ssscrape_feed_metadata m ON f.id = m.feed_id LEFT JOIN (SELECT i.feed_id, c.feed_item_id FROM ssscrape_feed_item i, ssscrape_feed_item_comment c WHERE ?temp-constraint? AND c.feed_item_id = i.id) c ON f.id = c.feed_id ?where? GROUP BY f.id";
        $q = "SELECT s.site_url AS site, i.feed_id, COUNT(*) AS tracks FROM shuffler_track s LEFT JOIN ssscrape_feed_item i ON s.feed_item_id = i.id WHERE ?temp-constraint? ?where? GROUP BY site_url";
        $this->run_query($q, 'sent');
    }

    function display_site($site, $row) {
      return ax_a_href_title($site, $this->make_url(0, array('show'=>'tracks', 'site'=>$row['feed_id'])), 'Show tracks');
    }
    
    function check_tracks($tracks, $row) {
        return ($tracks != 0);
    }
}

?>
