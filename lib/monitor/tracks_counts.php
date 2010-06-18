<?php

class TrackCountsTable extends Table {

    function TrackCountsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('blog', 'feed', 'tracks'));
        $this->set_field_option('blog', 'sql-name', 's.site_url');      
        $this->set_field_option('feed', 'sql-name', 'f.url');      
        $this->set_field_option('feed', 'search');
        $this->set_default_ordering('s.site_url', 'ASC');
        $this->process_options($params);
    }

    function count_feeds() {
      $q = "SELECT COUNT(DISTINCT i.feed_id) AS num FROM ssscrape_feed_item i, shuffler_track s WHERE s.feed_item_id = i.id AND ?temp-constraint? -- ";
      //$num_active_feeds = $this->count($this->prepare_query($q, 'sent'));
      $db = DB::get_instance();
      $cnt = $db->prepare_execute_fetch($this->prepare_query($q, 'sent'));
      $num_active_feeds = array_pop($cnt);
      
      $num_feeds = $this->count("SELECT COUNT(*) FROM `ssscrape_feed` -- ");
      $num_feeds_pct = round(($num_active_feeds * 100) / $num_feeds);
      $this->m->append(ax_p("$num_active_feeds feeds out of $num_feeds have tracks in this period ($num_feeds_pct %)"));      
    }
    
    function show() {
        $this->count_feeds();

        $q = "SELECT f.id, f.url AS feed, COUNT(s.feed_id) AS tracks, s.site_url AS blog FROM ssscrape_feed f LEFT JOIN (SELECT i.feed_id, s.site_url FROM ssscrape_feed_item i, shuffler_track s WHERE ?temp-constraint? AND s.feed_item_id = i.id) s ON f.id = s.feed_id ?where? GROUP BY f.id";

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
    
    function display_blog($blog, $row) {
      if ($row['tracks'] > 0) {
        $img_src = "http://www.google.com/s2/favicons?domain=". urlencode(parse_url($row['blog'], PHP_URL_HOST));
        return ax_a_href_title(ax_img_src($img_src, array('class' => 'icon')), $row['blog'], 'Go to this blog');
      }
    }
}

?>
