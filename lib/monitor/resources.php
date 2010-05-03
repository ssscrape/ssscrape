<?php

class ResourcesTable extends Table {

    function ResourcesTable($m, $params, $unused) {
        parent::Table($m);
        $this->set_fields(array('id', 'name', 'interval', 'latest_run'));
        $this->set_field_option('latest_run', 'datetime-key');
        $this->set_field_option('interval', 'time');
        $this->set_default_ordering('latest_run', 'DESC');
        $this->process_options($params);
    }

    function show() {
        $q = 'SELECT `id`, `name`, `interval`, `latest_run`
              FROM ssscrapecontrol.ssscrape_resource ?where?';

        $this->run_query($q);
    }


}

?>
