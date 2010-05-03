<?php

class JobLogsTable extends JobsTable {

    function JobLogsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('id', 'task', 'type', 'program', 'args', 'state', 'hostname', 'message', 'output', 'exit', 'start', 'duration', 'resource'));
        $this->set_field_option('start', 'datetime-key');
        $this->set_field_option('duration', 'time');
        $this->set_field_option('output', 'expand', 40);
        $this->set_field_option('message', 'expand', 40);
        $this->set_field_option('resource', 'sql-name', 'resource_id');
        $this->set_field_option('task', 'sql-name', 'task_id');
        $this->set_default_ordering('start', 'DESC');
        $this->process_options($params);
    }

    function show() {
        $this->count_job_types('ssscrapecontrol.ssscrape_job_log');

        $q = 'SELECT `id`, `task_id` `task`, `type`, `program`, `args`, `state`, `hostname`, `message`, `output`, 
                     `exit_code` `exit`, `start`, TIMEDIFF(`end`,`start`) as duration, `resource_id` `resource`
              FROM ssscrapecontrol.ssscrape_job_log
              ?where?';

        $this->run_query($q);
    }

    function check_output($output, $row) {
        if (preg_match('/\S/', $output)) {
            return false;
        }
        return true;
    }


}

?>
