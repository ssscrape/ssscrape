<?php

class TasksTable extends Table {

    function TasksTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'type', 'program', 'args', 'state', 'hostname', 'periodicity', 'latest_run', 'jobs', 'resource'));
        //$this->set_field_option('latest_run', 'datetime-key');
        //$this->set_field_option('periodicity', 'time');
        $this->set_field_option('id', 'sql-name', 't.id');
        $this->set_field_option('type', 'sql-name', 't.type');
        $this->set_default_ordering('latest_run', 'DESC');
        $this->set_field_option('program', 'search');
        $this->set_field_option('args', 'search');
        $this->process_options($params);
    }

    function count_task_types($table_name) {
        $db = DB::get_instance();
        $q = "SELECT `type`, COUNT(*) c FROM $table_name t ?where? group by `type`";
        $q = $this->prepare_query($q);
        $rows = $db->prepare_execute_fetch_all($q);
        $counts = array();
        foreach ($rows as $row) {
           array_push($counts, ax_a_href_title(sprintf("%s: %s", $row['type'], $row['c']),
                                                     $this->make_url(1, array('type'=>$row['type'])),
                                                     "show only " . $row['type'] . " tasks"), ", ");
        }
        array_unshift($counts, "Tasks per type: ");
        $this->m->append(ax_p($counts));


        $q = "SELECT `state`, COUNT(*) c FROM $table_name t ?where? group by `state`";
        $q = $this->prepare_query($q);
        $rows = $db->prepare_execute_fetch_all($q);
        $counts = array();
        foreach ($rows as $row) {
           array_push($counts, ax_a_href_title(sprintf("%s: %s", $row['state'], $row['c']),
                                                     $this->make_url(1, array('state'=>$row['state'])),
                                                     "show only " . $row['state'] . " tasks"), ", ");
        }
        array_unshift($counts, "Tasks per state: ");
        $this->m->append(ax_p($counts));
    }

    function show() {
        $this->count_task_types('ssscrapecontrol.ssscrape_task');

        $q = "SELECT t.id, t.type, program, args, state, hostname, periodicity, 
                     latest_run, resource_id resource, 
                     f.url, f.id feed_id, '' jobs                
              FROM ssscrapecontrol.ssscrape_task t 
                   LEFT JOIN ssscrape_feed f ON f.url=REPLACE(REPLACE(t.args, '-u ', ''), \"'\", '')
              ?where?";

        $this->m->append(ax_p(ax_a_href("Create new task", "editor/?show=tasks&id=NEW")));

        $this->run_query($q);
    }

    function display_id($id, $row) {
        return ax_a_href_title($id, Request::url(false) . "editor/?show=tasks&id=$id", "Edit task $id"); 
    }
    function display_periodicity($value, $row) {
        return AnewtDateTime::time($value);
    }

    function display_latest_run($value, $row) {
        return AnewtDateTime::format_if_today("%H:%M:%S", "%H:%M:%S (%d-%m-%Y)", $value);
    }

    function display_args($args, $row) {
        if (isset($row['url']) && isset($row['feed_id'])) {
            $url = $row['url'];
            $feed_id = $row['feed_id'];
            $args = ax_a_href_title($args, $this->make_url(0, array('show'=>'feeds', 'id'=>$feed_id)), "Show feed $feed_id"); 
        }

        return $args;
    }

    function check_state($state, $row) {
        return ($state == 'enabled');
    }

    function display_jobs($value, $row) {
        return ax_a_href_title(ax_raw("&rarr;"), $this->make_url(0, array('show'=>'jobLogs', 'task'=>$row['id'])), "Show job logs for task " . $row['id']); 
    }


}

?>
