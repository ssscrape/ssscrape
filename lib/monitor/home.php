<?php            
            
class HomePage {

    public $m;
    public $pages;

    function HomePage($m, $params, $pages) {
        $this->m = $m;
        $this->pages = $pages;
    }

    function show() {
        $items = array();
        foreach ($this->pages as $page) {
            if ($page['name'] != 'home') {
                $item = &ax_li(array(
                                ax_a_href(ucfirst($page['name']), $this->m->make_url(array('show'=>$page['name']))),
                                ": " . $page['descr']
                ));
                array_push($items, $item);
            }
        }
        $this->m->append(ax_ul($items));

    }
}

?>
