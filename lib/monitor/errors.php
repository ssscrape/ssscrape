<?php

class ErrorsTable extends Table {

    function ErrorsTable($m, $params, $unused) {
        parent::Table($m, $params);
        $this->set_fields(array('jobs', 'message', 'type', 'start'));
        $this->set_field_option('start', 'datetime-key');
        $this->set_field_option('jobs', 'num');
        $this->process_options($params);
    }

    function show() {
        $q = 'SELECT `message`, `type`, count(*) AS jobs, `start` FROM ssscrapecontrol.ssscrape_job_log 
              WHERE `message` NOT LIKE "OK %" ?where-and? GROUP BY `message`, `type`';

        $this->run_query($q, 'start');
    }

    function display_jobs($jobs, $row) {
        $message = rtrim($row['message']); // blah
        $jobs = ax_a_href_title($jobs,
                                $this->make_url(0, array('show'=>'jobLogs', 
                                                         'message'=>"LIKE:$message%", 
                                                         'type'=>$row['type'],
                                                         'interval'=>$this->interval)),
                                "show job logs with message $message");
        return $jobs;
    }

}

?>
