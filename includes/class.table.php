<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * =============================================================================
 * Berni Call Backs class
 * =============================================================================
 * @description Create a new table class that will extend the WP_List_Table
 * @subpackage Berni
 * 
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 * @since 1.0
 * TODO: make pagination AJAX
 */
class Example_List_Table extends WP_List_Table {

    /**
     * -------------------------------------------------------------------------
     * Prepare the items for the table to process
     * -------------------------------------------------------------------------
     * @method prepare_items
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function prepare_items($search='') {

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();

        usort( $data, array( &$this, 'sort_data' ) );

        $per_page = 150;
        $currentPage = $this->get_pagenum();

        if(isset($_REQUEST['s']) && $_REQUEST['s'] != ''){
            $data = $this->filterResult($data, $_REQUEST['s']);
        }

        $total_items = count($data);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $data = array_slice($data,(($currentPage-1)*$per_page),$per_page);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;

    }

    /**
     * -------------------------------------------------------------------------
     * Method to filter data by searches
     * -------------------------------------------------------------------------
     * @method filterResult
     * @access public
     * @param $data array of objects
     * @param $search_string - Search string
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function filterResult($data,  $search_string){

        foreach($data as $key => $value){

            $id = stripos($value['id'], $search_string);
            $name = stripos($value['name'], $search_string);
            $vendor = stripos($value['vendor'], $search_string);
            $vendor_code = stripos($value['vendor_code'], $search_string);
            
            if (
                $id === false && 
                $name === false && 
                $vendor === false && 
                $vendor_code === false
            )
                unset ($data[$key]);

        }

        return $data;

    }

    /**
     * -------------------------------------------------------------------------
     * Override the parent columns method. 
     * -------------------------------------------------------------------------
     * @description Defines the columns to use in your listing table
     * @method get_columns
     * @access public
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function get_columns() {
        
        $columns = array(
            'status'      => 'Selected',
            'id'          => 'ID',
            'picture'     => 'Preview',
            'vendor'      => 'Vendor',
            'vendor_code' => 'Code',
            'name'        => 'Title',
            'available'   => 'Available',
            'category'    => 'Category',
            'price'       => 'price',
        );
        return $columns;
        
    }
    
    /**
     * -------------------------------------------------------------------------
     * Define which columns are hidden
     * -------------------------------------------------------------------------
     * @method get_hidden_columns
     * @access public
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function get_hidden_columns() {

        return array();

    }

    /**
     * -------------------------------------------------------------------------
     * Define the sortable columns
     * -------------------------------------------------------------------------
     * @method get_sortable_columns
     * @access public
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function get_sortable_columns() {

        return array(
            'id' => array('id', false), 
            'status' => array('status', false)
        );

    }
    
    /**
     * -------------------------------------------------------------------------
     * Get the table data
     * -------------------------------------------------------------------------
     * @method table_data
     * @access public
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    private function table_data() {

        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';
        $table_berni_images = $wpdb->prefix.'berni_images';
        $data = array();

        $data = $wpdb->get_results(
            "SELECT 
                $table_berni.berni_id as id, 
                $table_berni_images.picture, 
                $table_berni.name, 
                $table_berni.available, 
                $table_berni.vendor, 
                $table_berni.vendor_code, 
                $table_berni.category, 
                $table_berni.status,
                $table_berni.price
             FROM $table_berni 
             LEFT JOIN $table_berni_images 
             ON $table_berni.berni_id = $table_berni_images.berni_id
             GROUP BY $table_berni.berni_id", ARRAY_A
        );

        return $data;

    }
    
    /**
     * -------------------------------------------------------------------------
     * Define what data to show on each column of the table
     * -------------------------------------------------------------------------
     * @method column_default
     * @access public
     * @param  Array  $item - Data
     * @param  String $column_name - Current column name
     * 
     * @return Mixed
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function column_default( $item, $column_name ) {

        switch( $column_name ) {
            case 'picture':
                return '<img src="'.$item[ $column_name ].'">';
            case 'category':

                $cats = get_categories([
                    'hide_empty' => 0,
                    'taxonomy'   => 'product_cat'
                ]);

                $selector = '<select class="category-update" 
                                     data-id="'.$item["id"].'">';
                foreach($cats as $category){
                    $default = '';
                    if($item[ $column_name ] == $category->term_id){
                        $default = 'selected="selected"';
                    }
                    $selector .= '<option '.$default.' value="'.$category->term_id.'">'.$category->name.'</option>';
                }

                $selector .= '</select>';

                return $selector;
            case 'status':
                return '<input data-id="'.$item["id"].'"
                               class="offer-select" 
                               type="checkbox" 
                               value="'.$item[ $column_name ].'"' . checked( 
                    1, $item[ $column_name ], false 
                ).'>';
            case 'price':
                return $item[ $column_name ].' UAH';
            case 'available':
            case 'id':
            case 'vendor':
            case 'vendor_code':
            case 'name':
            case 'name':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }

    }
    
    /**
     * -------------------------------------------------------------------------
     * Allows you to sort the data by the variables set in the $_GET
     * -------------------------------------------------------------------------
     * @method column_default
     * @access public
     * 
     * @return Mixed
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    private function sort_data( $a, $b ) {

        $orderby = 'id';
        $order = 'asc';
        
        if(!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }
        
        if(!empty($_GET['order'])) {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc') {
            return $result;
        }
        return -$result;

    }
    
    /**
     * -------------------------------------------------------------------------
     * Display extra navigation
     * -------------------------------------------------------------------------
     * @method extra_tablenav
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function extra_tablenav($which) {

        submit_button('Import Selected', 'button', '', false, array(
            'name' => 'import',
            'id'   => 'import-selected'
        ));
        
        echo '<br><br><input type="button" class="button" id="check-all" name="check-all" value="Select\Unselect All" data-value="1">';
    }

}

?>