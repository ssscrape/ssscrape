<?php

class TrackCountsTable extends Table {

    function TrackCountsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('feed', 'tracks'));
        $this->process_options($params);
    }

    function show() {
        $q = "SELECT f.id, f.url AS feed, COUNT(s.feed_id) AS tracks FROM ssscrape_feed f LEFT JOIN (SELECT i.feed_id FROM ssscrape_feed_item i, shuffler_track s WHERE ?temp-constraint? AND s.feed_item_id = i.id) s ON f.id = s.feed_id ?where? GROUP BY f.id";
        $this->run_query($q, 'sent');
    }

    function display_feed($feed, $row) {
      return ax_a_href_title($feed, $this->make_url(0, array('show'=>'items', 'feed'=>$row['id'])), 'Show feed items');
    }

    function display_tracks($tracks, $row) {
      return ax_a_href_title($tracks, $this->make_url(0, array('show'=>'tracks', 'feed'=>$row['id'])), 'Show feed tracks');
    }
    
    function check_tracks($tracks, $row) {
        return ($tracks != 0);
    }
}

?>
