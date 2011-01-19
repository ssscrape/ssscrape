<?php

class TagsTable extends Table {

    function TagsTable($m, $params, $unused) {
        parent::Table($m);
        $this->set_fields(array('tags', 'feeds', 'items', 'pub_date'));
        #$this->set_field_option('interval', 'time');
        $this->set_default_ordering('items', 'DESC');
        #$this->set_field_option('pub_date', 'datetime-key');
        $this->process_options($params);
    }

    function show() {
        $q = 'SELECT `tags`, COUNT(DISTINCT m.feed_id) AS feeds, COUNT(i.id) AS items, MAX(i.pub_date) AS pub_date
              FROM ssscrape_feed_metadata m
                  LEFT JOIN (SELECT id, feed_id, pub_date
                             FROM ssscrape_feed_item
                             WHERE ?temp-constraint?) i
                  ON m.feed_id=i.feed_id
              ?where?    
              GROUP BY `tags`';

        $this->run_query($q, 'pub_date');
    }

    function display_tags($tags, &$row) {

        $f = ax_fragment();

        $tags = split(",", $tags);
        for ($i = 0; $i < count($tags); $i++) {
            $f->append_child(ax_a_href_title($tags[$i], 
                                             $this->make_url(0, array('show'=>'items', 'tags'=>"HAS:".$tags[$i])), 
                                             "Show items with tag " . $tags[$i]));
            if ($i < count($tags)-1) { 
                $f->append_child(ax_raw(","));
            }
        }
        return $f;
    }


}

?>
