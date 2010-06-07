<?php

function &ax_table_class($class) {
    $res = &new AnewtXHTMLTable(array('class'=>$class));
    return $res;
}
function &ax_tr($attrs=null) {
    $res = &new AnewtXHTMLTableRow($attrs);
    return $res;
}
function &ax_tr_class($class) {
    $res = &new AnewtXHTMLTableRow(array('class'=>$class));
    return $res;
}
function &ax_td($content, $attrs=null) {
    $res = &new AnewtXHTMLTableCell($content, $attrs);
    return $res;
}
function &ax_th($content, $attrs=null) {
    $res = &new AnewtXHTMLTableHeaderCell($content, $attrs);
    return $res;
}
function &ax_th_class($content, $class) {
    $res = &new AnewtXHTMLTableHeaderCell($content, array('class'=>$class));
    return $res;
}

# cycle through values at each call (except initial, when array of possible values is given)
function cycle($values = null) {
    global $cycle_values, $cycle_cur;

    if ($values) {
        $cycle_values = $values;
        $cycle_cur = 0;
        return null;
    } elseif ($cycle_values) {
        $ret = $cycle_values[$cycle_cur];
        $cycle_cur++;
        if ($cycle_cur >= count($cycle_values)) {
            $cycle_cur = 0;
        }
        return $ret;
    } else {
        return null;
    }
}

# get the path to the conf/ directory
function get_conf_dir() {
    return realpath(dirname(__FILE__) .'/../../conf/');
}

# parse an .ini file
function parse_ini($f, $conf = array()) {
    $lines = @file($f);
    $_section = False;
    # if a file stopped being read for some reason ...
    if (!is_array($lines)) {
      $lines = array();
    }
    foreach ($lines as $line) {
        if (preg_match('/^\s*\[([^\]]+)\]/', $line, $matches)) {
            $section = trim($matches[1]);
            $conf[$section] = array();
            $_section = $section;
        } else if (preg_match('/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*)$/i', $line, $matches)) {
            $k = trim($matches[1]);
            $v = trim($matches[2]);
            if ($_section) {
                $conf[$_section][$k] = $v;
            } else {
                $conf[$k] = $v;
            }
        }
    }
    return $conf;
}

function read_config() {
    $conf = array();
    $env = $_SERVER["RAILS_ENV"];
    $env_conf = $env ? "$env.conf" : "development.conf";
    $conf_files = array('default.conf', $env_conf, 'local.conf');
    $conf_dir = get_conf_dir();
    foreach($conf_files as $conf_file) {
        $conf = parse_ini("$conf_dir/$conf_file", $conf);
    }
    return $conf;
}

?>
