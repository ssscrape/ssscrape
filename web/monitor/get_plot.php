<?php


define('MONITOR_DIR', dirname(__FILE__) . "/../../lib/monitor/");

function init_db() {
        require_once("../../lib/ext/anewt/anewt.lib.php");
        require(MONITOR_DIR . 'utils.php');
        anewt_include('database');

        $conf = read_config();
        $section = 'database-web';
        $db_settings = array(
            'hostname' => $conf[$section]['hostname'],
            'username' => $conf[$section]['username'],
            'password' => $conf[$section]['password'],
            'database' => $conf[$section]['database'],
            'charset' => 'utf8',
            'use_unicode' => true,
        );
        $db = &DB::get_instance('mysql', $db_settings);
        return $db;
}


if (isset($_GET['feed'])) {
    $feed_id = intval($_GET['feed']);
    $redraw = isset($_GET['redraw']);


    $plot_file = "plots/feed-$feed_id.png";

    # Generate plot if it does not exists or is too old
    $max_plot_age_in_seconds = 60 * (60 + rand(-15, 15)); // 1 hour (randomize +/- 15 min)
    if (!file_exists($plot_file) || $redraw || time() - filemtime($plot_file) > $max_plot_age_in_seconds) {
        $db = init_db();

        $query = "SELECT count(*) AS c FROM ssscrape_feed_item " .
                 "WHERE pub_date < NOW() AND pub_date >= CURRENT_DATE - INTERVAL 14 DAY " .
                 "GROUP BY DATE(pub_date), feed_id " .
                 "HAVING feed_id=$feed_id " .
                 "ORDER BY pub_date";

        $rows = $db->prepare_execute_fetch_all($query);
        $counts = array();
        $max = 1;
        $max_i = 0;
        $min = 1000000;
        $min_i = 0;
        $i = 0;
        foreach ($rows as $row) {
            $count = $row['c'];
            array_push($counts, $count);

            if ($count > $max && $i < count($rows) - 1) {
                $max = $count;
                $max_i = $i;
            }
            if ($count < $min && $i < count($rows) - 1) {
                $min = $count;
                $min_i = $i;
            }
            $i++;
        }
        # Last count will be displayed separately as a dot
        $last_count = array_pop($counts);
        if ($min == 1000000) {
            $min = 0;
        }

        if (count($rows)) {
            $last_position = sprintf("%.1f", $last_count / $max);
            $padding_left = array('_', '_', '_', '_');
            $counts = array_merge($padding_left, $counts);
            $min_i += count($padding_left);
            $max_i += count($padding_left);
            $plot_url = "http://chart.apis.google.com/chart?cht=ls&chco=909090&chs=76x16&chds=0," . ceil($max * 1.1) . "&chf=bg,s,00000000";
            $plot_url .= "&chd=t:" . implode(',', $counts) . ",_";
            $plot_url .= "&chm=@t$min,FF0000,0,0:0.45,9,0,lt::0" . 
                             "|o,FF0000,0,$min_i,2" .
                             "|@t$max,005000,0,0:0.55,9,0,lb::0" . 
                             "|o,005000,0,$max_i,2" .
                             "|@o,0000F0,0,1:$last_position,2,0,rv:-1:0";

            system("curl " . escapeshellarg($plot_url) . " 2>/dev/null > " . escapeshellarg($plot_file));
            #system("echo " . escapeshellarg($plot_url) . " 2>/dev/null > " . escapeshellarg($plot_file));
        } else {
            # Empty plot
            system("cat img/dot.gif > " . escapeshellarg($plot_file));
        }

    }

    #$plot_url = "http://chart.apis.google.com/chart?cht=ls&chco=A0A0A0&chd=t:60,40,78,82,34,45,67,140,0,60,30&chs=76x16&chm=t34,FF0000,0,4,9,0,ht::1|t140,0000F0,0,7,9,0,ht";

    #$plot_file = "plots/" . preg_replace('/\W+/', '.', $plot_url);
    #$plot_file = "plots/" . md5($plot_url);

    header("Content-Type: image/png");
    $expires = 60 * 60; // 1 hour
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
    readfile($plot_file);

} else {
    header("HTTP/1.0 404 Not Found");
}

#header("Location: ");

?>
