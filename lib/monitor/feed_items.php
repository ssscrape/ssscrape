<?php

class FeedItemTable extends Table {

    function FeedItemTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'feed', 'tags', 'pub_date', 'fetch_date', 'title', 'summary', 'content', 'jobs', 'comments', 'link'));
        $this->set_field_option('id', 'sql-name', 'i.id');
        $this->set_field_option('feed', 'sql-name', 'i.feed_id');
        $this->set_field_option('pub_date', 'datetime-key');
        $this->set_field_option('title', 'expand', 100);
        $this->set_field_option('summary', 'expand', 200);
        $this->set_field_option('content', 'expand', 500);
        $this->set_field_option('content', 'dynamic', TRUE);
        $this->limit = 10;
        $this->process_options($params);
        $this->max_limit = 200;
    }

    function show() {

        $q = "SELECT i.id, i.guid, l.link link, lsrc.link link_src, m.url, i.feed_id feed, m.tags, i.title, i.summary, i.content_clean content, i.pub_date, i.fetch_date
              FROM ssscrape.ssscrape_feed_item i 
                  LEFT JOIN ssscrape_feed_metadata m ON i.feed_id = m.feed_id
                  LEFT JOIN ssscrape_feed_item_link l ON i.id = l.feed_item_id AND l.relation='alternate'
                  LEFT JOIN ssscrape_feed_item_link lsrc ON i.id = lsrc.feed_item_id AND lsrc.relation='source'
              ?where?";

        $this->run_query($q);
    }

    function display_feed($feed, $row) {
        if ($feed) {
            $feed = ax_a_href_title($row['url'], 
                                    $this->make_url(0, array('show'=>'feeds', 'id'=>$row['feed'])),
                                    "Show feed " . $row['feed']);
        }
        return $feed;
    }

    function display_link($link, $row) {
        if ($link) {
            $link = ax_a_href_title(ax_raw("&rarr;"), $link, "permalink");
        }
        if ($row['link_src']) {
            $link = ax_fragment($link, ax_raw(' '), ax_a_href_title(ax_raw("&rArr;"), $row['link_src'], "source link"));
        }
        $link = ax_fragment($link,
                            ax_a_href_title(ax_raw("<br/>t&rarr;"), "get_feed_item.php?id=".$row['id']."&what=content_clean", "extracted text content"),
                            ax_a_href_title(ax_raw("<br/>c&rarr;"), "get_feed_item.php?id=".$row['id']."&what=content_clean_html&raw", "cleaned HTML"),
                            ax_a_href_title(ax_raw("<br/>o&rarr;"), "get_feed_item.php?id=".$row['id']."&what=content", "original HTML")
        );                    
        return $link;
    }

    function check_link($guid, $row) {
        if ($guid and preg_match('/^http/', $guid)) {
            return true;
        }
        return false;
    }

    function display_content($content, $row) {
        return ax_span($content, array('onmouseover'=>'load_content(this, "get_feed_item.php", "id='.$row['id'].'&what=content_clean_html&ifempty=content"); this.onmouseover=null'));
    }

    function display_jobs($jobs, $row) {
        $id = $row['id'];
        $jobs = ax_a_href_title(ax_raw("log&rarr;"), 
                                $this->make_url(0, array('show'=>'jobLogs', 'args'=>"LIKE:% $id%", 'interval'=>'7 DAY')),
                                "show job logs for item $id");
        return $jobs;
    }

    function display_comments($comments, $row) {
        $id = $row['id'];
        $comments = ax_a_href_title(ax_raw("comments&rarr;"), 
                                $this->make_url(0, array('show'=>'comments', 'item'=>$id)),
                                "show comments for item $id");
        return $comments;
    }

    function check_content($content, $row) {
        if (!$content or !preg_match('/\S/', $content) or strlen($content)<100) {
            return false;
        } 
        return true;
    }

    function check_articles($articles, $row) {
        if ($articles < $row['items']) {
            return false;
        } 
        return true;
    }

}

?>
