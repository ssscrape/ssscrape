<?php

anewt_include('xhtml');
anewt_include('form');
anewt_include('database');

class TaskForm extends AnewtForm {
    function TaskForm($args) {
        parent::__construct();
                            
        /* General form setup and test some properties */
                                            
        $this->setup('task-form', ANEWT_FORM_METHOD_POST, Request::url(true));
                                                            
        //$this->set('error', 'This is a general form error.');
        //$this->set('description', 'This is the form\'s description.');

        $ctl = &new AnewtFormControlText('id');
        $ctl->set('label', 'Task id:');
        $ctl->set('secondary-label', '(assigned automatically)');
        $ctl->set('readonly', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('state');
        $ctl->set('label', 'State:');
        $ctl->add_option_value_label('enabled', 'enabled');
        $ctl->add_option_value_label('disabled', 'disabled');
        $ctl->set('value', 'enabled');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('type');
        $ctl->set('label', 'Type:');
        $ctl->set('secondary-label', '(e.g.: fetch, index)');
        $ctl->set('value', 'fetch');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('program');
        $ctl->set('label', 'Program:');
        $ctl->set('secondary-label', '(e.g.: feedworker.py)');
        $ctl->set('size', 100);
        $ctl->set('value', 'feedworker.py');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('args');
        $ctl->set('label', 'Arguments:');
        $ctl->set('secondary-label', '(arguments that will be passed to the program)');
        $ctl->set('size', 100);
        $ctl->set('value', '-u \'...\'');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('hostname');
        $ctl->set('label', 'Host restriction:');
        $ctl->set('secondary-label', '(allow:HOSTNAME or deny:HOSTNAME)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('periodicity');
        $ctl->set('label', 'Periodicity:');
        $ctl->set('secondary-label', '(interval to run jobs for this task; e.g., 00:15:00 for "every 15 minutes")');
        $ctl->set('value', '23:59:00');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlChoice('autoperiodicity');
        $ctl->set('label', 'Auto-adjust periodicity:');
        $ctl->add_option_value_label('enabled', 'enabled');
        $ctl->add_option_value_label('disabled', 'disabled');
        $ctl->set('disabled', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('hour');
        $ctl->set('label', 'Hour:');
        $ctl->set('secondary-label', '(specific hour to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('minute');
        $ctl->set('label', 'Minute:');
        $ctl->set('secondary-label', '(specific minute to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('second');
        $ctl->set('label', 'Second:');
        $ctl->set('secondary-label', '(specific second to run jobs for this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('latest_run');
        $ctl->set('label', 'Latest run:');
        $ctl->set('secondary-label', '(datatime when a job was last run for the task)');
        $ctl->set('readonly', true);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('resource_id');
        $ctl->set('label', 'Resource id:');
        $ctl->set('secondary-label', '(id of the resource assigned to this task)');
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlText('data');
        $ctl->set('label', 'Extra data:');
        $ctl->set('secondary-label', '(anything that might in principle be used by the program running jobs)');
        $ctl->set('size', 100);
        $this->add_control($ctl);

        $ctl = &new AnewtFormControlButtonSubmit('submit');
        $ctl->set('label', 'Save task');
        $this->add_control($ctl);

    }

    function get_data($id) {
        $q = "SELECT * FROM ssscrapecontrol.ssscrape_task WHERE id=?str?";
        $db = DB::get_instance();
        $data = $db->prepare_execute_fetch($q, $id);
        
        if ($data) {  
            $data['periodicity'] = AnewtDateTime::time($data['periodicity']);
            $data['latest_run'] = AnewtDateTime::sql($data['latest_run']);

            $this->fill($data);
            return true;
        }

        return false;
    }

    function save_data() {
        $values = $this->get_control_values();
        $db = DB::get_instance();
        $sql_values = array();
        foreach ($values as $name => $value) {
            if (!$this->get_control($name)->get('readonly') and 
                    !$this->get_control($name)->get('disabled') and 
                    $name != 'submit') { 
                if ($value == '') {
                    $value = 'NULL';
                } else {
                    $value = $db->backend->escape_string($value) . " ";
                }
                array_push($sql_values, $db->backend->escape_column_name($name) . "=$value");
            }
        }
        if ($values['id'] != '') {
            $sql = "UPDATE ssscrapecontrol.ssscrape_task SET ";
        } else {
            $sql = "INSERT INTO ssscrapecontrol.ssscrape_task SET ";
        }
        $sql .= implode(", ", $sql_values);
        if ($values['id'] != '') {
            $sql .= " WHERE `id`=" . $db->backend->escape_string($values['id']);
        }
        $db->prepare_execute($sql);
        if ($values['id'] == '') {
            $data = $db->prepare_execute_fetch("SELECT LAST_INSERT_ID()");
            $id = array_pop($data);
            $this->set_control_value('id', $id);
        }


        return "Executing: " . $sql;
    }
                                                                                            
                                                                                                
}

?>
