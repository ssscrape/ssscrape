<?php
if (isset($_GET['url']) and preg_match('/^http/', $_GET['url'])) {
    print file_get_contents(urldecode($_GET['url']));
} else {
    print "Quit hacking ...";
}
