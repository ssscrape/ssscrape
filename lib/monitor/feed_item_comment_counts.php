<?php

class FeedItemCommentCountsTable extends Table {

    function FeedItemCommentCountsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'url', 'title', 'comments', 'kind', 'tags', 'mod_date'));
        $this->set_field_option('id', 'sql-name', 'f.id');
        $this->set_field_option('url', 'sql-name', 'f.url');
        $this->set_field_option('comments', 'num');
        $this->set_field_option('kind', 'sql-name', 'm.kind');
        $this->set_field_option('tags', 'sql-name', 'm.tags');
        //$this->set_field_option('c.pub_date', 'datetime-key');
        $this->process_options($params);
        $this->max_limit = 200;
    }

    function show() {
        $q = "SELECT f.id, f.url, f.title, m.kind, m.tags, count(c.feed_item_id) comments, f.mod_date FROM ssscrape_feed f LEFT JOIN ssscrape_feed_metadata m ON f.id = m.feed_id LEFT JOIN (SELECT i.feed_id, c.feed_item_id FROM ssscrape_feed_item i, ssscrape_feed_item_comment c WHERE ?temp-constraint? AND c.feed_item_id = i.id) c ON f.id = c.feed_id ?where? GROUP BY f.id";

        $this->run_query($q, 'c`.`pub_date');
    }

    function check_comments($comments, $row) {
        return ($comments != 0);
    }
}

?>
