<?php

class TrackDayCountsTable extends Table {

    function TrackDayCountsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('posted_when', 'tracks'));
        $this->set_field_option('posted_when', 'datetime');
        $this->process_options($params);
    }

    function show() {
        //$q = "SELECT f.id, f.url AS feed, COUNT(s.feed_id) AS tracks FROM ssscrape_feed f LEFT JOIN (SELECT i.feed_id FROM ssscrape_feed_item i, shuffler_track s WHERE ?temp-constraint? AND s.feed_item_id = i.id) s ON f.id = s.feed_id ?where? GROUP BY f.id";
        $q = "SELECT DATE(posted) AS posted_when, YEAR(posted) AS posted_year, MONTH(posted) AS posted_month, DAY(posted) AS posted_day, COUNT(*) AS tracks FROM shuffler_track WHERE ?temp-constraint? ?where? GROUP BY posted_year, posted_month, posted_day";
        //print $q;
        $this->run_query($q, 'posted');
    }

    function display_day($day, $row) {
      
    } 
       
    function check_tracks($tracks, $row) {
        return ($tracks != 0);
    }
}

?>
