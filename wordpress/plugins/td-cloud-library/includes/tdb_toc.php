<?php

/* ---------
---- TOC MENU FACTORY CLASS
--------- */
class tdb_toc_menu_factory {

    /**
     * Add a menu item
     *
     * @param string $slug
     * @param array $attributes
     * @return tdb_toc_menu_item
     */
    public function createItem( string $slug, array $attributes = [] ) {

        $item = new tdb_toc_menu_item($slug, $this);
        $this->setItemAttributes($item, $attributes);

        return $item;

    }




    /**
     * Set an item's attributes
     *
     * @param tdb_toc_menu_item $item
     * @param array $attributes
     * @return void
     */
    public function setItemAttributes( tdb_toc_menu_item $item, array $attributes ) {

        $attributes = array_merge(
            array(
                'label' => '',
                'level' => 0,
                'children' => array(),
                'render' => true
            ),
            $attributes
        );

        $item->setLabel($attributes['label'])
            ->setLevel($attributes['level'])
            ->addChildren($attributes['children'])
            ->setRender($attributes['render']);

    }

}





/* ---------
----TOC MENU ITEM CLASS
--------- */
class tdb_toc_menu_item {

    protected $slug;
    protected $label;
    protected $level;
    protected $parent;
    protected $children;
    protected $render;
    protected $menu;



    /**
     * Class constructor
     *
     * @param string $slug
     * @param tdb_toc_menu_factory $menu
     */
    public function __construct( string $slug, tdb_toc_menu_factory $menu ) {
        $this->slug = $slug;
        $this->menu = $menu;
    }



    /**
     * Set an item's parent
     *
     * @param tdb_toc_menu_item|null $parent
     * @return tdb_toc_menu_item
     */
    public function setParent( $parent = null ) {

        if( $parent === $this ) {
            throw new \InvalidArgumentException('Item cannot be a child of itself');
        }

        $this->parent = $parent;

        return $this;

    }


    /**
     * Get an item's parent
     *
     * @return tdb_toc_menu_item
     */
    public function getParent() {
        return $this->parent;
    }



    /**
     * Set an item's slug
     *
     * @param string $slug
     * @return tdb_toc_menu_item
     */
    public function setSlug( string $slug ) {
        $this->slug = $slug;

        return $this;
    }


    /**
     * Get an item's slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }



    /**
     * Set an item's label
     *
     * @param string $label
     * @return tdb_toc_menu_item
     */
    public function setLabel( string $label ) {
        $this->label = $label;

        return $this;
    }


    /**
     * Get an item's slug
     *
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }



    /**
     * Set an item's level
     *
     * @param int $level
     * @return tdb_toc_menu_item
     */
    public function setLevel( int $level ) {
        $this->level = $level;

        return $this;
    }


    /**
     * Get an item's level
     *
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }



    /**
     * Add a child to an item
     *
     * @param string $slug
     * @param array $attributes
     * @return tdb_toc_menu_item
     */
    public function addChild( string $slug, array $attributes = [] ) {

        $child = $this->menu->createItem($slug, $attributes);
        $child->setParent($this);

        $this->children[$child->getSlug()] = $child;

        return $child;

    }


    /**
     * Get an item's child
     *
     * @param string $slug
     * @return tdb_toc_menu_item
     */
    public function getChild( string $slug ) {
        return $this->children[$slug];
    }



    /**
     * Add multiple children to an item
     *
     * @param array $children
     * @param bool $append
     * @return tdb_toc_menu_item
     */
    public function addChildren( array $children, bool $append = false ) {

        if( $append ) {
            foreach ( $children as $child ) {
                $this->children[] = $child;
            }
        } else {
            $this->children = $children;
        }

        return $this;
    }


    /**
     * Get all children of an item
     *
     * @return array
     */
    public function getChildren() {
        return $this->children;
    }



    /**
     * Set whether an item should be rendered or not
     *
     * @param bool $render
     * @return tdb_toc_menu_item
     */
    public function setRender( $render ) {
        $this->render = $render;

        return $this;
    }



    /**
     * Get the render property of an item
     *
     * @return bool
     */
    public function getRender() {
        return $this->render;
    }



    /**
     * Check if an item has children
     *
     * @return bool
     */
    public function hasChildren() {
        return !empty($this->children);
    }



    /**
     * Check whether an item should be rendered or not
     *
     * @return bool
     */
    public function isRender() {
        return $this->render;
    }

}





/* ---------
---- TOC UTILS CLASS
--------- */
class tdb_toc {

    /**
     * Get menu
     *
     * @param string $content
     * @param int $topLevel
     * @param int $depth
     * @param array $exclude
     * @return tdb_toc_menu_item
     */
    static function getMenu( string $content, int $topLevel = 1, int $depth = 6, array $exclude = [] ) {

        $menu_factory = new tdb_toc_menu_factory();
        $menu = $menu_factory->createItem('TOC', array('render' => false));

        if( $content == '' ) {
            return $menu;
        }


        /* -- Parse the content and extract the headings -- */
        $headings = self::extractHeadings($content, $topLevel, $depth, $exclude);

        if( empty( $headings ) ) {
            return $menu;
        }


        /* -- Set the first item as the last item added -- */
        $lastItem = $menu;


        /* -- Go through each heading and add it to the menu -- */
        foreach ( $headings as $heading ) {
            $heading_slug = $heading['slug'];
            $heading_label = $heading['label'];
            $heading_level = $heading['level'];

            /* -- Build the new TOC menu item -- */
            $menu_item = array(
                'slug' => $heading_slug,
                'label' => $heading_label,
                'level' => $heading_level,
            );

            /* -- Determine the parent item and add the new item accordingly -- */
            if ( $heading_level == 1 ) {
                $parent = $menu;
            } else if ( $heading_level == $lastItem->getLevel() ) {
                $parent = $lastItem->getParent();
            } else if( $heading_level > $lastItem->getLevel() ) {
                $parent = $lastItem;
                for ($i = $lastItem->getLevel(); $i < ($heading_level - 1); $i++) {
                    $parent = $parent->addChild('');
                    $parent->setLevel($i + 1);
                    $parent->setRender(false);
                }
            } else {
                $parent = $lastItem->getParent();
                while ($parent->getLevel() > $heading_level - 1) {
                    $parent = $parent->getParent();
                }
            }

            $lastItem = $parent->addChild($heading_slug, $menu_item);

        }


        return $menu;

    }




    /**
     * Create a dummy menu
     *
     * @return tdb_toc_menu_item
     */
    static function getDummyMenu() {

        $menu_factory = new tdb_toc_menu_factory();
        $menu = $menu_factory->createItem('TOC', array('render' => false));

        $item1 = $menu->addChild('example-1', array(
            'slug' => 'example-1',
            'label' => 'Example 1',
            'level' => 1
        ));

        $item2 = $item1->addChild('example-2', array(
            'slug' => 'example-2',
            'label' => 'Example 2',
            'level' => 2
        ));

        $item3 = $item2->addChild('example-3', array(
            'slug' => 'example-3',
            'label' => 'Example 3',
            'level' => 3
        ));

        $item4 = $item3->addChild('example-4', array(
            'slug' => 'example-4',
            'label' => 'Example 4',
            'level' => 4
        ));

        $item5 = $item4->addChild('example-5', array(
            'slug' => 'example-5',
            'label' => 'Example 5',
            'level' => 5
        ));

        $item6 = $item5->addChild('example-6', array(
            'slug' => 'example-6',
            'label' => 'Example 6',
            'level' => 6
        ));

        $item7 = $item2->addChild('example-7', array(
            'slug' => 'example-7',
            'label' => 'Example 7',
            'level' => 2
        ));

        $item8 = $item7->addChild('example-8', array(
            'slug' => 'example-8',
            'label' => 'Example 8',
            'level' => 3
        ));

        $item9 = $menu->addChild('example-9', array(
            'slug' => 'example-9',
            'label' => 'Example 9',
            'level' => 1
        ));

        return $menu;

    }




    /**
     * Get menu html
     *
     * @param tdb_toc_menu_item $menu_item
     * @param bool $hierarchical
     * @return string
     */
    static function getMenuHTML( tdb_toc_menu_item $menu_item, bool $hierarchical = true ) {

        $buffy = '';

        if( $hierarchical ) {
            $buffy .= self::renderMenu($menu_item, $hierarchical);
        } else {
            $buffy .= '<ul>';
            $buffy .= self::renderMenu($menu_item, $hierarchical);
            $buffy .= '</ul>';
        }

        return $buffy;

    }




    /**
     * Get dummy menu html
     *
     * @param bool $hierarchical
     * @return string
     */
    static function getDummyMenuHTML( bool $hierarchical = true ) {

        $dummy_menu = self::getDummyMenu();

        return self::getMenuHTML($dummy_menu, $hierarchical);

    }




    /**
     * Render menu html
     *
     * @param tdb_toc_menu_item $menu_item
     * @param bool $hierarchical
     * @return string
     */
    static function renderMenu( tdb_toc_menu_item $menu_item, bool $hierarchical = true ) {

        $buffy = '';

        if( $menu_item->hasChildren() ) {
            if( $hierarchical ) {
                $buffy .= '<ul>';
            }
            $buffy .= self::renderMenuItemChildren($menu_item, $hierarchical);
            if( $hierarchical ) {
                $buffy .= '</ul>';
            }
        }

        return $buffy;

    }




    /**
     * Render menu item children html
     *
     * @param tdb_toc_menu_item $menu_item
     * @param bool $hierarchical
     * @return string
     */
    static function renderMenuItemChildren( tdb_toc_menu_item $menu_item, bool $hierarchical = true ) {

        $buffy = '';

        foreach ( $menu_item->getChildren() as $menu_item_child ) {
            $buffy .= self::renderMenuItem($menu_item_child, $hierarchical);
        }

        return $buffy;

    }




    /**
     * Render menu item html
     *
     * @param tdb_toc_menu_item $menu_item
     * @param bool $hierarchical
     * @return string
     */
    static function renderMenuItem( tdb_toc_menu_item $menu_item, bool $hierarchical = true ) {

        $buffy = '';

        if( $menu_item->isRender() ) {
            $buffy .= '<li>';
            $buffy .= '<a href="#' . $menu_item->getSlug() . '">' . $menu_item->getLabel() . '</a>';

            $buffy .= self::renderMenu($menu_item, $hierarchical);
            $buffy .= '</li>';
        } else {
            $buffy .= self::renderMenuItemChildren($menu_item, $hierarchical);
        }

        return $buffy;

    }




    /**
     * Extract the headings from a content
     *
     * @param string $content
     * @param int $topLevel
     * @param int $depth
     * @param array $exclude
     * @return array
     */
    static function extractHeadings( string $content, int $topLevel = 1, int $depth = 6, array $exclude = [] ) {

        $headings_list = array();
        $headings_slugs = array();

        $allowed_headings = array_slice([0, 1, 2, 3, 4, 5, 6], $topLevel, $depth);
        $allowed_headings = array_diff($allowed_headings, $exclude);


        $pattern = '#(?P<full_heading><(?P<heading_tag>h\d)(?P<heading_classes>[^>]*)(?P<heading_extra>[^>]*)>(?P<heading_contents>.*?)<\/h\d>)#i';
        preg_match_all( $pattern, $content, $headings, PREG_SET_ORDER );

        if( $headings ) {

            foreach ( $headings as $heading ) {

                /* -- Get the heading level -- */
                $heading_tag = $heading['heading_tag'];
                $heading_level = (int)filter_var($heading_tag, FILTER_SANITIZE_NUMBER_INT);

                if( !in_array($heading_level, $allowed_headings) ) {
                    continue;
                }


                /* -- Build the heading slug (used like an ID) -- */
                $heading_label = strip_tags( $heading['heading_contents'] );

                if( empty( $heading_label ) ) {
                    continue;
                }

                $heading_slug = mb_strtolower(
                    preg_replace('/([?]|\p{P}|\s)+/u', '-', strip_tags(str_replace("&nbsp;", " ", $heading_label)))
                );

                $heading_slug = str_replace(array("-8211", "-amp", "-8217"), array("", "", ""), trim($heading_slug, '-'));
                $heading_slug_new = $heading_slug;
                if ( in_array($heading_slug, $headings_slugs) ) {
                    $heading_slug_new = $heading_slug . '-' . count(array_keys($headings_slugs, $heading_slug));
                }

                $headings_slugs[] = $heading_slug;


                /* -- Build the heading info -- */
                $headings_list[] = array(
                    'slug' => $heading_slug_new,
                    'label' => $heading_label,
                    'level' => $heading_level,
                    'full_heading' => $heading['full_heading'],
                    'tag' => $heading_tag,
                    'extra' => $heading['heading_extra']
                );

            }

        }


        return $headings_list;

    }

}