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
        $db->prepare_execute('SET CHARACTER SET utf8');
        return $db;
}


$db = init_db();

if (isset($_GET['id']) && isset($_GET['what'])) {
    $id = intval($_GET['id']);
    $field = preg_replace('/\W/', 'X', $_GET['what']);

    $query = "select `" . $field . "` as res from ssscrape_feed_item where id=" . $id;
    $res = $db->prepare_execute_fetch($query);
    $res = array_pop($res);


    if (!$res && isset($_GET['ifempty'])) {
        $field = preg_replace('/\W/', 'X', $_GET['ifempty']);

        $query = "select `" . $field . "` as res from ssscrape_feed_item where id=" . $id;
        $res = $db->prepare_execute_fetch($query);
        $res = array_pop($res);
    }

    if (!isset($_GET['raw'])) {
        # In case $res is HTML, remove all scripts, CSS links and images
        #$res = preg_replace('/(<\/?)(link|script|style|iframe|img)\b/i', '${1}$2-DISABLED', $res);
        $res = preg_replace("/\s*(<script\\b.*?(?<!\/)\s*>.*?<\/script>)/sie", '"<span style=\"font-size: 8px; color: gray\" title=\"".htmlspecialchars("$1")."\">&lt;script&gt;</span>"', $res);
        
        $tag  = 'link|script|style|iframe|img';
        $res = preg_replace("/\s*(<\/?($tag)\\b)([^>]*)(>)/ie", '"<span style=\"font-size: 8px; color: gray\" title=\"".htmlspecialchars("$1$3$4")."\"><tt>".htmlspecialchars("$1"."$4")."</tt></span>"', $res);
    }

    header("Content-Type: text/html; charset=UTF-8");
    echo $res;
}

#header("Location: ");

?>
