<?php

anewt_include('form');

class FormChoiceAutoSubmit extends AnewtFormControlChoice {
    function FormChoiceAutoSubmit($name) {
        parent::__construct($name);
    }

    function build_widget() {
        $out = parent::build_widget();
        $out->set_attribute('onchange', 'this.form.submit()');
        return $out;
    }

}

class Table {


    public $m; // monitor
    public $params;
    private $fields;
    private $field_opts;
    private $order_by = null;
    private $order_dir = null;
    private $limit = 100;
    public $max_limit = 500;
    private $offset;
    public $interval;
    private $time_field = null;
    private $stat;
    private $query_restriction;
    private $last_displayed_tags = "";

    function Table($monitor) {
        $this->m = $monitor;

    }

    function set_fields($fields) {
        $this->fields = $fields;
        $this->field_opts = array();
        foreach ($fields as $f) {
            $this->field_opts[$f] = array();
            if (str_contains($f, '_date') ) { 
                $this->field_opts[$f]['datetime'] = 1;
            }
        }
    }

    function set_field_option($field, $option, $value=1) {
        $this->field_opts[$field][$option] = $value;
        if ($option == 'datetime-key') {
            $this->time_field = $field;
            $this->field_opts[$field]['datetime'] = 1;
        }
    }

    function set_default_ordering($field, $order) {
        $this->order_by = $field;
        $this->order_dir = $order;
    }

    function process_options($params) {
        $this->params = $params;
        assert($this->fields);

        $this->order_by = array_get_default($params, 'order', $this->order_by);
        if (!in_array($this->order_by, $this->fields)) {
            $this->order_by = $this->fields[0];
        }

        $this->order_dir = array_get_default($params, 'orderdir', $this->order_dir);
        if (!in_array($this->order_dir, array('ASC', 'DESC'))) {
            if (isset($this->field_opts[$this->order_by]['datetime']) || $this->order_by == 'tags') { 
                $this->order_dir = 'DESC';
            } else {
                $this->order_dir = 'ASC';
            }
        }

        $this->limit = intval(array_get_default($params, 'max', $this->limit));
        if ($this->limit > $this->max_limit) {
            $this->limit = $this->max_limit;
        }

        $this->offset = array_get_default($params, 'offset', 0);
        $this->offset = intval($this->offset);
        if ($this->offset < 0) {
            $this->offset = 0;
        }

        $this->interval = array_get_default($params, 'interval', $this->m->interval);
        unset($this->params['interval']);
    }

    function make_url($keep_current_params, $params_add=null, $params_del=null) {
        if ($keep_current_params) {
            return $this->m->make_url($this->params, $params_add, $params_del);
        } else {
            return $this->m->make_url($params_add, null, $params_del);
        }
    }

    function make_table_header() {
        $r = &ax_tr_class(cycle());
        foreach($this->fields as $field) {
            if ($field == $this->order_by) {
                if ($this->order_dir == 'ASC') {
                    $reorder = ax_a_href_title(ax_raw("&darr;"), $this->make_url(1, array('orderdir'=>"DESC")), "order by $field descending");
                } else {
                    $reorder = ax_a_href_title(ax_raw("&uarr;"), $this->make_url(1, array('orderdir'=>"ASC")), "order by $field ascending");
                }
                $c = &ax_th_class(array($field,$reorder), 'ordered');
            } else {
                $c = &ax_th(ax_a_href_title($field, $this->make_url(1, array('order'=>$field), array('orderdir', 'offset')), "order by $field"));
            }
            if (isset($this->field_opts[$field]['num']) || method_exists($this, "inc_".$field)) {
                $this->stat[$field]['sum'] = 0;
            }
            $r->append_child($c);
        }
        return $r;
    }

    function make_table_rows(&$rows) {
        $result = array();
        foreach ($rows as $row) {
            $row['css_class'] = ''; 
            array_push($result, $this->make_one_row($row));
        }
        return $result;
    }

    function make_one_row(&$row) {
        $r = &ax_tr();
        $tr_class = cycle();
        foreach($this->fields as $field) {
            $value = array_get_default($row, $field, "?");
            $r->append_child($this->make_one_cell($field, $value, $row));
        }
        $r->set_attribute('class', $tr_class . $row['css_class']);
        return $r;
    }

    function make_one_cell($field, $value, &$row) {
        $classes = array();
        if (method_exists($this, "check_".$field)) {
            if (!call_user_func(array($this, "check_".$field), $value, $row)) {
                $classes['error'] = 1;
            }
        }
        if (isset($this->field_opts[$field]['truncate'])) {
            $value = str_truncate($value, $this->field_opts[$field]['truncate']);
        }
        if (isset($this->field_opts[$field]['datetime']) and $value) {
            $value = AnewtDateTime::sql($value);
        }
        if (isset($this->field_opts[$field]['time']) and $value) {
            $value = AnewtDateTime::time($value);
        }
        if (isset($this->field_opts[$field]['num'])) {
            $classes['number'] = 1;
            $this->stat[$field]['sum'] += intval($value);
        } else if (method_exists($this, "inc_".$field)) {
            $incr_value = call_user_func(array($this, "inc_".$field), $value, &$row);
            $this->stat[$field]['sum'] += intval($incr_value);
        }
        $display_value = $value;
        if (method_exists($this, "display_".$field)) {
            $display_value = call_user_func(array($this, "display_".$field), $value, &$row);
        }
        $expand = array_get_default($this->field_opts[$field], 'expand', "0"); 
        if ($expand > 0 && is_string($value) && strlen($value) > 0) {
            $display_value = ax_a(array(str_truncate($value, $expand, "...", true),
                                        ax_span($display_value)),
                                  array('class'=>'expand'));
        }
        $c = &ax_td($display_value);
        foreach ($classes as $class => $val) {
            $c->add_class($class);
        }
        return $c;
    }

    function make_table_footer() {
        $r = &ax_tr_class("footer");
        foreach($this->fields as $field) {
            $display_value = "";
            $attrs = array();
            if (method_exists($this, "sum_".$field)) {
                $display_value = call_user_func(array($this, "sum_".$field));
                $attrs['class'] = 'number';
            } else if (isset($this->field_opts[$field]['num']) || method_exists($this, "inc_".$field)) {
                $display_value = array(ax_raw("&sum;="), $this->stat[$field]['sum']);
                $attrs['class'] = 'number';
            }
            $c = &ax_td($display_value, $attrs);
            $r->append_child($c);
        }
        return $r;
    }

    function make_results_summary($nrows, $more_results) {
        $nav = array();

        if ($this->offset > 0) {
            $prev_offset = $this->offset - $this->limit;
            if ($prev_offset < 0) {
                $prev_offset = 0;
            }
            array_push($nav, ax_a_href(ax_raw("&laquo; Previous " . $this->limit . " results"), 
                                       $this->make_url(1, array('offset'=>$prev_offset))));

            array_push($nav, " | ");
        }

        array_push($nav, $nrows . " results");

        if ($more_results) {
            array_push($nav, " | ");
            array_push($nav, ax_a_href(ax_raw("Next " . $this->limit . " results &raquo;"), 
                                      $this->make_url(1, array('offset'=>$this->offset+$this->limit))));
        }
        $nav = &ax_p($nav);
        return $nav;
    }

    function make_interval_selection($current_interval) {
        $form = new AnewtForm();
        $form->setup('interval', ANEWT_FORM_METHOD_GET, $this->make_url(0));
       
        foreach ($this->params as $name => $val) {
            if ($name != 'interval') {
                $c = &new AnewtFormControlHidden($name);
                $c->set('value', $val);
                $form->add_control($c);
            }
        }
        $c = &new FormChoiceAutoSubmit('interval');
        $c->set('label', 'Interval:');
        $c->set('threshold', 3);
        $c->add_option_value_label('1 HOUR', '1 hour');
        $c->add_option_value_label('1 DAY', '1 day');
        $c->add_option_value_label('3 DAY', '3 days');
        $c->add_option_value_label('7 DAY', '1 week');
        $c->add_option_value_label('31 DAY', '1 month');
        $c->add_option_value_label('92 DAY', '3 months');
        $c->add_option_value_label('*', 'any time');
        $c->set('value', $this->interval);
        $form->add_control($c);
                                        
        $fr = &new AnewtFormRendererDefault();
        $fr->set_form($form);
        return $fr;
    }

    function prepare_query($q, $field_name = null) {
        $where = array();
        foreach ($this->fields as $f) {
            if (isset($this->params[$f])) {
                $val = $this->params[$f];
                $f = array_get_default($this->field_opts[$f], 'sql-name', $f);
                if (str_has_prefix($val, 'HAS:')) {
                    $val = preg_replace('/^HAS:/', '', $val);
                    array_push($where, sprintf("FIND_IN_SET('%s', %s)", 
                                               mysql_real_escape_string($val), $f));
                } elseif (str_has_prefix($val, 'LIKE:')) {
                    $val = preg_replace('/^LIKE:/', '', $val);
                    array_push($where, sprintf("%s LIKE '%s'", 
                                               $f, mysql_real_escape_string($val)));
                } else {
                    if (!is_numeric($val)) {
                        $val = "'" . mysql_real_escape_string($val) . "'";
                    }
                    array_push($where, sprintf("%s=%s", $f, $val));
                } 
            }
        }
        if ($this->time_field and $this->interval != '' and $this->interval != '*') {
            array_push($where, sprintf("(`%s` > NOW() - INTERVAL %s)", $this->time_field, $this->interval));  
        }
        $where = implode(' AND ', $where);
        $this->query_restriction = $where;
        if (str_contains($q, '?where?')) {
            if ($where) { $where = "WHERE $where"; }
            $q = str_replace('?where?', $where, $q);
            #$q = str_replace('?where?', '', $q);
            #die($qqq);
        } elseif (str_contains($q, '?where-and?')) {
            if ($where) { $where = "AND $where"; }
            $q = str_replace('?where-and?', $where, $q);
        }
        if (str_contains($q, '?temp-constraint?')) {
            $q = str_replace('?temp-constraint?', 
                             ($this->interval != '*') ? sprintf("(%s > NOW() - INTERVAL %s)", $field_name, $this->interval) : '1',
                             $q);  
        }

        $q = $q . " ORDER BY `" . $this->order_by . "` " . $this->order_dir . " LIMIT " . ($this->limit+1) . " OFFSET " . $this->offset;
        //print "$q<br />\n";
        return $q;
    }

    function message($msg) {
        $this->m->append(ax_p($msg)); 
    }

    function count($q) {
        $db = DB::get_instance();
        $cnt = $db->prepare_execute_fetch($this->prepare_query($q));
        $cnt = array_pop($cnt);
        return $cnt;
    }


    function run_query($q, $extra_field = null) {
        $q = $this->prepare_query($q, $extra_field);
        $m = $this->m;

        $db = DB::get_instance();
        $rows = $db->prepare_execute_fetch_all($q);

        $more_results = false;
        if (count($rows) == $this->limit+1) {
            $more_results = true;
            array_pop($rows); # remove the extra row
        }

        cycle(array('bg1', 'bg0'));
        $this->stat = array();

        $descr = array(); 
        if ($this->query_restriction) {
            array_push($descr, "Condition: " . $this->query_restriction);
        }
        if ($this->interval) {
            array_push($descr, $this->make_interval_selection($this->interval));
        }
        $m->append(ax_p($descr));

        $table = ax_table_class('results');
        $table->append_child($this->make_table_header());
        $table->append_children($this->make_table_rows($rows));
        $table->append_child($this->make_table_footer());
        $m->append($table);

        $m->append($this->make_results_summary(count($rows), $more_results));

        $m->append(ax_p(array(ax_raw('Generated with query:<br /><textarea class="query">'), $q, ax_raw('</textarea>'))));
    }


    function display_tags($tags, &$row) {

        if ($tags != $this->last_displayed_tags) {
            $row['css_class'] .= " first-row-in-group"; 
        }
        $this->last_displayed_tags = $tags;

        $f = ax_fragment();

        $tags = split(",", $tags);
        for ($i = 0; $i < count($tags); $i++) {
            $f->append_child(ax_a_href_title($tags[$i], 
                                             $this->make_url(1, array('tags'=>"HAS:".$tags[$i])), 
                                             "Only show rows with tag " . $tags[$i]));
            if ($i < count($tags)-1) { 
                $f->append_child(ax_raw(","));
            }
        }
        return $f;
    }

    function display_task($task, $row) {
        $task = ax_a_href_title($task, 
                                $this->make_url(0, array('show'=>'tasks', 'id'=>$task)),
                                "Show task " . $task);
        return $task;
    }

    function display_kind($kind, $row) {
        $kind = ax_a_href_title($kind,
                                $this->make_url(1, array('kind'=>$kind)),
                                "Only show rows with kind $kind.");
        return $kind; 
    }

    function display_resource($resource, $row) {
        $resource = ax_a_href_title($resource,
                                    $this->make_url(0, array('show'=>'resources','id'=>$resource)),
                                    "Only show rows with resource $resource.");
        return $resource;
    }
}

?>
