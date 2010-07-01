<?php

anewt_include('xhtml');
anewt_include('form');
anewt_include('database');

class FeedForm extends AnewtForm {

    public $default_t_args = '-u \'Feed URL\'';

    function FeedForm($args) {
        parent::__construct();
                            
        /* General form setup and test some properties */
                                            
        $this->setup('feed-form', ANEWT_FORM_METHOD_POST, Request::url(true));
                                                            
        //$this->set('error', 'This is a general form error.');
        //$this->set('description', 'This is the form\'s description.');

        
        $ctl = &new AnewtFormControlText('id');
        $ctl->set('label', 'Feed id:');
        $ctl->set('secondary-label', '(assigned automatically)');
        $ctl->set('readonly', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('url');
        $ctl->set('label', 'Feed URL:');
        $ctl->set('secondary-label', '(should point to an RSS/Atom feed)');
        $ctl->set('size', 100);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('m_kind');
        $ctl->set('label', 'Kind:');
        $ctl->add_option_value_label('full', 'full');
        $ctl->add_option_value_label('partial', 'partial (permalinks will be fetched for items)');
        $ctl->set('value', 'partial');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('m_partial_args');
        $ctl->set('label', 'Permalink fetching args:');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('m_tags');
        $ctl->set('label', 'Tags:');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('m_language');
        $ctl->set('label', 'Language:');
        $ctl->set('secondary-label', '(optional)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('m_class');
        $ctl->set('label', 'Class:');
        $ctl->add_option_value_label('text', 'text');
        $ctl->add_option_value_label('audio', 'audio');
        $ctl->add_option_value_label('video', 'video');
        $ctl->set('value', 'audio');
        $this->add_control($ctl);

        
        $ctl = &new AnewtFormControlText('t_id');
        $ctl->set('label', 'Task id:');
        $ctl->set('secondary-label', '(assigned automatically)');
        $ctl->set('readonly', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('t_state');
        $ctl->set('label', 'State:');
        $ctl->add_option_value_label('enabled', 'enabled');
        $ctl->add_option_value_label('disabled', 'disabled');
        $ctl->set('value', 'enabled');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_type');
        $ctl->set('label', 'Type:');
        $ctl->set('secondary-label', '(e.g.: fetch, index)');
        $ctl->set('value', 'fetch');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_program');
        $ctl->set('label', 'Program:');
        $ctl->set('secondary-label', '(e.g.: feedworker.py)');
        $ctl->set('size', 100);
        $ctl->set('value', 'feedworker.py');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_args');
        $ctl->set('label', 'Arguments:');
        $ctl->set('secondary-label', '(assigned automatically, unless modified manually)');
        $ctl->set('size', 100);
        $ctl->set('value', $this->default_t_args);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_hostname');
        $ctl->set('label', 'Host restriction:');
        $ctl->set('secondary-label', '(allow:HOSTNAME or deny:HOSTNAME)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_periodicity');
        $ctl->set('label', 'Periodicity:');
        $ctl->set('secondary-label', '(interval to run jobs for this task; e.g., 00:15:00 for "every 15 minutes")');
        $ctl->set('value', '00:23:59');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('t_autoperiodicity');
        $ctl->set('label', 'Auto-adjust periodicity:');
        $ctl->add_option_value_label('enabled', 'enabled');
        $ctl->add_option_value_label('disabled', 'disabled');
        $ctl->set('disabled', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_hour');
        $ctl->set('label', 'Hour:');
        $ctl->set('secondary-label', '(specific hour to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_minute');
        $ctl->set('label', 'Minute:');
        $ctl->set('secondary-label', '(specific minute to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_second');
        $ctl->set('label', 'Second:');
        $ctl->set('secondary-label', '(specific second to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_latest_run');
        $ctl->set('label', 'Latest run:');
        $ctl->set('secondary-label', '(datatime when a job was last run for the task)');
        $ctl->set('readonly', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_resource_id');
        $ctl->set('label', 'Resource id:');
        $ctl->set('secondary-label', '(id of the resource assigned to this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('t_data');
        $ctl->set('label', 'Extra data:');
        $ctl->set('secondary-label', '(anything that might in principle be used by the program running jobs)');
        $ctl->set('size', 100);
        $this->add_control($ctl);

        $fieldset = new AnewtFormFieldset('feed-auto');
        $fieldset->set('label', 'Extracted from the feed automatically');

        $ctl = &new AnewtFormControlText('title');
        $ctl->set('label', 'Feed title:');
        $ctl->set('size', 50);
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('description');
        $ctl->set('label', 'Description:');
        $ctl->set('size', 100);
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('language');
        $ctl->set('label', 'Language:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('copyright');
        $ctl->set('label', 'Copyright:');
        $ctl->set('readonly', true);
        $ctl->set('size', 100);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('type');
        $ctl->set('label', 'Type:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('class');
        $ctl->set('label', 'Class:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('favicon');
        $ctl->set('label', 'Flavicon:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('encoding');
        $ctl->set('label', 'Encoding:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('pub_date');
        $ctl->set('label', 'Publication date:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $ctl = &new AnewtFormControlText('mod_date');
        $ctl->set('label', 'Last modified:');
        $ctl->set('readonly', true);
        $fieldset->add_control($ctl);

        $this->add_fieldset($fieldset);

        $fieldset = new AnewtFormFieldset('feed-notes');
        $fieldset->set('label', 'Notes');

        $ctl = &new AnewtFormControlTextMultiline('m_notes');
        $ctl->set('label', 'Notes:');
        $fieldset->add_control($ctl);

        $this->add_fieldset($fieldset);
        
        $ctl = &new AnewtFormControlButtonSubmit('submit');
        $ctl->set('label', 'Save feed');
        $this->add_control($ctl);

    }

    function get_data($id) {
        $q = "SELECT * FROM ssscrape.ssscrape_feed WHERE id=?str?";
        $db = DB::get_instance();
        $data = $db->prepare_execute_fetch($q, $id);
        
        if ($data) {  
            $data['pub_date'] = AnewtDateTime::sql($data['pub_date']);
            $data['mod_date'] = AnewtDateTime::sql($data['mod_date']);

            $q = "SELECT * FROM ssscrape.ssscrape_feed_metadata WHERE feed_id=?str?";
            $metadata = $db->prepare_execute_fetch($q, $id);
            if ($metadata) {  
                foreach ($metadata as $name => $value) {
                    $data['m_' . $name] = $value;
                }
            }

            $q = "SELECT * FROM ssscrapecontrol.ssscrape_task WHERE LOCATE(?str?, args)";
            $task_data = $db->prepare_execute_fetch($q, "'" . $data['url'] . "'");
            if ($task_data) {  
                $task_data['periodicity'] = AnewtDateTime::time($task_data['periodicity']);
                $task_data['latest_run'] = AnewtDateTime::sql($task_data['latest_run']);
                foreach ($task_data as $name => $value) {
                    $data['t_' . $name] = $value;
                }
            }

            $this->fill($data);
            return true;
        }

        

        return false;
    }

    function save_data() {
        $values = $this->get_control_values();
        $db = DB::get_instance();
        
        $msg = "Executing: ";

        $hostname = parse_url($values['url'], PHP_URL_HOST);
        if (!$hostname) {
            return "Incorrect URL: " . $values['url'];
        }

        if (!preg_match('/^[0-9]+$/', $values['id'])) {
            $values['id'] = '';
        }

        $new_feed = false;
        if ($values['id'] == '') { 
            # Adding new feed.
            # First, check if a feed with this URL already exists
            $new_feed = true;
            $row = $db->prepare_execute_fetch("SELECT id FROM ssscrape.ssscrape_feed WHERE url=?str?", $values['url']);
            $id = $row ? array_pop($row) : '';
            if ($id != '') {
                return "Error: feed with URL " . $values['url'] . " already exists (id=" . $id . ")";
            } else {
                # Create new feed
                $db->prepare_execute("INSERT INTO ssscrape.ssscrape_feed SET url=?str?", $values['url']);
                $data = $db->prepare_execute_fetch("SELECT LAST_INSERT_ID()");
                $id = array_pop($data);
                $values['id'] = $id;
                $this->set_control_value('id', $id);
            }
        }    

        # Fix default parameters
        if ($values['t_args'] == $this->default_t_args) {
            $args = "-u '" . $values['url'] . "'";
            $values['t_args'] = $args;
            $this->set_control_value('t_args', $args);
        }

        if ($values['t_resource_id'] == '') {
            # Check/add resource
            $row = $db->prepare_execute_fetch("SELECT id FROM ssscrapecontrol.ssscrape_resource WHERE name=?str?", $hostname);
            $resource_id = $row ? array_pop($row) : '';
            if ($resource_id == '') {
                # Create new resource
                $sql = "INSERT INTO ssscrapecontrol.ssscrape_resource SET name=?str?, latest_run=0";
                $db->prepare_execute($sql, $hostname);
                $row = $db->prepare_execute_fetch("SELECT LAST_INSERT_ID()");
                $resource_id = array_pop($row);
                $msg .= $sql . "[" . $hostname . "]; ";
            }
            $values['t_resource_id'] = $resource_id;
            $this->set_control_value('t_resource_id', $resource_id);
        }

        # Separate feed metadata parameters and task parameters
        $feed_metadata_sql_values = array();
        $task_sql_values = array();
        foreach ($values as $name => $value) {
            if (!$this->get_control($name)->get('readonly') and 
                    !$this->get_control($name)->get('disabled') and 
                    $name != 'submit') { 
                if ($value == '') {
                    $value = 'NULL';
                } else {
                    $value = $db->backend->escape_string($value) . " ";
                }

                if (strpos($name, 'm_') === 0 || $name == 'url') {
                    $sql_value = $db->backend->escape_column_name(preg_replace('/^m_/', '', $name)) . "=$value";
                    array_push($feed_metadata_sql_values, $sql_value);
                } elseif (strpos($name, 't_') === 0 && $name != 't_id') {
                    $sql_value = $db->backend->escape_column_name(preg_replace('/^t_/', '', $name)) . "=$value";
                    array_push($task_sql_values, $sql_value);
                }
            }
        }

        # Add/update feed metadata
        $row = array_values($db->prepare_execute_fetch("SELECT COUNT(*) FROM ssscrape.ssscrape_feed_metadata WHERE feed_id=?str?", $values['id']));
        if ($row[0] == 0) {
            $sql = "INSERT INTO ssscrape.ssscrape_feed_metadata SET " . implode(", ", $feed_metadata_sql_values) . ", feed_id=?str?";
        } else {
            $sql = "UPDATE ssscrape.ssscrape_feed_metadata SET " . implode(", ", $feed_metadata_sql_values) . " WHERE feed_id=?str?";
        }
        $db->prepare_execute($sql, $values['id']);
        $msg .= $sql . '[' . $values['id'] . ']; ';


        # Add/update task
        if ($values['t_id'] == '') {
            $sql = "INSERT INTO ssscrapecontrol.ssscrape_task SET " . implode(", ", $task_sql_values);
            $db->prepare_execute($sql);
            $msg .= $sql;
        } else {
            $sql = "UPDATE ssscrapecontrol.ssscrape_task SET " . implode(", ", $task_sql_values) . " WHERE id=?str?";
            $db->prepare_execute($sql, $values['t_id']);
            $msg .= $sql . "[" . $values['t_id'] . "]";
        }

        return $msg;
    }
                                                                                            
                                                                                                
}

?>
