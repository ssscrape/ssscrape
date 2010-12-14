<?php

class JobsTable extends Table {

    function JobsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'task', 'type', 'program', 'args', 'state', 'hostname', 'attempts', 'message', 'output', 'scheduled', 'start', 'resource', 'actions'));
        $this->set_field_option('start', 'datetime');
        $this->set_field_option('scheduled', 'datetime');
        $this->set_field_option('message', 'expand', 20);
        $this->set_field_option('output', 'expand', 20);
        $this->set_field_option('attempts', 'num');
        $this->set_field_option('resource', 'sql-name', 'resource_id');
        $this->set_field_option('actions', 'flushright');
        $this->set_default_ordering('scheduled', 'ASC');
        $this->process_options($params);
    }

    function count_jobs_by($table_name, $attr, $print_total_count) {
        $db = DB::get_instance();
        $q = "SELECT `$attr` as val, COUNT(*) c FROM $table_name ?where? group by `$attr`";
        $q = $this->prepare_query($q);
        $rows = $db->prepare_execute_fetch_all($q);
        $cnt = 0;
        $counts = array();
        array_push($counts, "By $attr: ");
        foreach ($rows as $row) {
           array_push($counts, ax_a_href_title(sprintf("%s: %s, ", $row['val'] ? $row['val'] : '""', $row['c']),
                                                     $this->make_url(1, array($attr=>$row['val'])),
                                                     "show only " . $row['val'] . " jobs"));
           $cnt += $row['c'];
        }
        array_push($counts, ax_raw("<br/>"));
        if ($print_total_count) {
            array_unshift($counts, ax_raw("Total: $cnt jobs found<br/>"));
        }

        $this->m->append(ax_fragment($counts));
    }

    function show() {
        $this->count_jobs_by('ssscrapecontrol.ssscrape_job', 'type', TRUE);
        $this->count_jobs_by('ssscrapecontrol.ssscrape_job', 'state', FALSE);
        $this->count_jobs_by('ssscrapecontrol.ssscrape_job', 'hostname', FALSE);

        $q = 'SELECT `id`, `task_id` `task`, `type`, `program`, `args`, `state`, `hostname`, `attempts`,
                     `message`, `output`, `scheduled`, `start`, `resource_id` `resource`
              FROM ssscrapecontrol.ssscrape_job
              ?where?';

        $this->run_query($q);
    }

    function display_output($output, $row) {
        return ax_pre($output);
    }

    function display_args($args, $row) {
        if (preg_match('/^(.*)-i ([0-9]+)(\b.*)$/', $args, $matches)) {
            $args = array($matches[1],
                          ax_a_href_title("-i ".$matches[2], 
                                          $this->make_url(0, array('show'=>'items', 'id'=>$matches[2], 'interval'=>'*')),
                                          "show feed item ".$matches[2]),
                          $matches[3]);
        }
        return $args;
    }

    function display_hostname($hostname, $row) {
        $hostname = ax_a_href_title($hostname,
                                    $this->make_url(1, array('hostname' => $hostname)),
                                    "show only jobs executed on host ". $hostname);
        return $hostname;
    }

    function display_actions($action, $row) {
        $action = ax_a_href_title(ax_img_src("img/delete.png"),
                                  "editor/?action=delete&what=job&id=" . $row['id'] . '&url=' . urlencode($this->make_url(1)),
                                  "remove job " . $row['id'] . " from queue");
        return $action;
    }

}

?>
