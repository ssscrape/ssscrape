<?php

class TracksTable extends Table {

  function TracksTable($m, $params, $unused) {
      parent::Table($m, $params);
      $this->set_fields(array('id', 'item', 'feed', 'anchor', 'artist', 'title', 'manual_tags', 'tags', 'method', 'sent', 'permalink', 'location', 'blog'));
      $this->set_field_option('item', 'sql-name', 'feed_item_id');
      $this->set_field_option('feed', 'sql-name', 'i.feed_id');      
      $this->set_field_option('title', 'sql-name', 's.title');  
      $this->set_field_option('blog', 'sql-name', 's.site_url');      
      $this->set_field_option('posted', 'datetime');
      $this->set_field_option('sent', 'datetime-key');
      $this->set_field_option('tags', 'sql-name', 's.tags');      
      $this->set_field_option('artist', 'search');
      $this->set_field_option('title', 'search');
      $this->set_field_option('blog', 'search');
      $this->set_default_ordering('sent', 'DESC');
      $this->process_options($params);
      $this->max_limit = 200;
      
      //$this->stat['tags']['sum'] = 0;
      $this->method_counts = array(
        'id3' => 0,
        'filename' => 0,
        'anchor' => 0
      );
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
    return ax_a_href_title(ax_raw("&rarr;"), $row['site_url'], 'Go to this blog');
  }
  
  function display_artist($artist, $row) {
    $lastfm_link = "http://www.last.fm/music/". urlencode($artist);
    return ax_a_href_title($artist, $lastfm_link, 'Go to this artist on last.fm');
  }
  
  function display_title($title, $row) {
    if ($row['artist'] != '') {
      $lastfm_link = "http://www.last.fm/music/". urlencode($row['artist']) ."/_/". urlencode($title);
      return ax_a_href_title($title, $lastfm_link, 'Go to this track on last.fm');      
    } else {
      return $title;
    }
  }
  
  function check_artist($artist, $row) {
      return ($artist != '');
  }

  function check_tags($tags, $row) {
      return ($tags != '');
  }
  
  function inc_tags($tags, $row) {
    return ($tags == '') ? 0 : 1;
  }
  
  function inc_artist($artist, $row) {
    return ($artist == '') ? 0 : 1;    
  }
  
  function inc_method($method, $row) {
    $this->method_counts[$method] += 1;
    return 0;
  }
  
  function sum_method() {
    $methods_sh = array(
      'id3' => 'id3',
      'filename' => 'file',
      'anchor' => 'anch',
      '' => ''
    );
    $display_values = array();
    foreach($this->method_counts as $method => $count) {
      $display_values[] = array(ax_raw("&sum; ". $methods_sh[$method] ."="), $count, ax_br());
      
    }
    return $display_values;
  }
}