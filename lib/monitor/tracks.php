<?php

class TracksTable extends Table {

  function TracksTable($m, $params, $unused) {
      parent::Table($m, $params);
      $this->set_fields(array('id', 'item', 'feed', 'anchor', 'artist', 'title', 'manual_tags', 'tags', 'method', 'sent', 'permalink', 'location', 'blog'));
      $this->set_field_option('item', 'sql-name', 'feed_item_id');
      $this->set_field_option('feed', 'sql-name', 'i.feed_id');      
      $this->set_field_option('posted', 'datetime');
      $this->set_field_option('sent', 'datetime-key');
      
      $this->set_default_ordering('sent', 'DESC');
      $this->process_options($params);
      $this->max_limit = 200;
  }
  
  function show() {

      $q = "SELECT
        s.id,
        s.feed_item_id AS item,
        i.feed_id AS feed,
        s.permalink,
        s.location,
        s.anchor,
        s.artist,
        s.title,
        s.tags,
        s.method,
        s.posted,
        s.sent,
        s.site_url,
        m.tags AS manual_tags
      FROM
        shuffler_track s
      LEFT JOIN
        ssscrape_feed_item i
      ON
        s.feed_item_id = i.id
      LEFT JOIN
        ssscrape_feed_metadata m
      ON
        i.feed_id = m.feed_id
      ?where?";

      $this->run_query($q);
  }

  function display_item($feed_item, $row) {
    return ax_a_href_title($feed_item, $this->make_url(1, array('item' => $feed_item)), "View tracks for this item");
  }
  
  function display_feed($feed, $row) {
    return ax_a_href_title($feed, $this->make_url(1, array('feed' => $feed)), 'Go to this site');
  }

  function display_permalink($permalink, $row) {
    return ax_a_href_title(ax_raw("&rarr;"), $permalink, 'Go to this permalink');
  }

  function display_location($location, $row) {
    return ax_a_href_title(ax_raw("&rarr;"), $location, 'Go to this sound location');
  }

  function display_blog($blog, $row) {
    return ax_a_href_title(ax_raw("&rarr;"), $row['sound_url'], 'Go to this blog');
  }
  
  function sum_tags() {
    $display_value = array(ax_raw("&sum;="), "0");
    return $display_value;
  }
}