<?php

class JobsTable extends Table {

    function JobsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'task', 'type', 'program', 'args', 'state', 'attempts', 'message', 'output', 'scheduled', 'start', 'resource'));
        $this->set_field_option('start', 'datetime');
        $this->set_field_option('scheduled', 'datetime');
        $this->set_field_option('message', 'expand', 20);
        $this->set_field_option('output', 'expand', 20);
        $this->set_field_option('attempts', 'num');
        $this->set_field_option('output', 'search');
        $this->set_field_option('args', 'search');
        $this->set_field_option('program', 'search');
        $this->set_field_option('message', 'search');        
        $this->set_default_ordering('scheduled', 'ASC');
        $this->process_options($params);
    }

    function count_job_types($table_name) {
        $db = DB::get_instance();
        $q = "SELECT `type`, COUNT(*) c FROM $table_name ?where? group by `type`";
        $q = $this->prepare_query($q);
        $rows = $db->prepare_execute_fetch_all($q);
        $cnt = 0;
        $counts = array();
        foreach ($rows as $row) {
           array_push($counts, ", ", ax_a_href_title(sprintf("%s: %s", $row['type'], $row['c']),
                                                     $this->make_url(1, array('type'=>$row['type'])),
                                                     "show only " . $row['type'] . " jobs"));
           $cnt += $row['c'];
        }
        array_unshift($counts, "Found $cnt jobs");

        $this->m->append(ax_p($counts));
    }

    function show() {
        $this->count_job_types('ssscrapecontrol.ssscrape_job');

        $q = 'SELECT `id`, `task_id` `task`, `type`, `program`, `args`, `state`, `attempts`,
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
                                          $this->make_url(0, array('show'=>'items', 'id'=>$matches[2])),
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
}

?>
