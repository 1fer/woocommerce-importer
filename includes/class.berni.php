<?php

if ( ! class_exists( 'CallBacks' ) ) {
    require_once( BERNI_INC . 'class.callbacks.php' );
}

if ( ! class_exists( 'BerniTable' ) ) {
    require_once( BERNI_INC . 'class.table.php' );
}


/**
 * =============================================================================
 * Berni Page class
 * =============================================================================
 * @description All important function for Import page.
 * 
 * @subpackage Berni
 * 
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 * @since 1.0
 */
class BerniPage {

    // Holds the values to be used in the fields callbacks
    public $options;

    const GOOGLE    = 'https://translate.googleapis.com/translate_a/single';
    const LANG_FROM = 'ru';
    const LANG_TO   = 'fi';

    /**
     * -------------------------------------------------------------------------
     * Lets Start Up
     * -------------------------------------------------------------------------
     * @description Press here all action and filters if needed
     * @method __construct
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function __construct() {

        add_action('admin_menu',               array($this, 'addPluginPage'));
        add_action('admin_init',               array($this, 'pageInit'));
        add_action('admin_notices',            array($this, 'berniNotificate'));
        add_action('admin_enqueue_scripts',    array($this, 'scripts'));
        add_action('wp_ajax_importer',         array($this, 'runImport'));
        add_action('wp_ajax_dynamic_update',   array($this, 'dynamicUpdate'));
        add_action('wp_ajax_dynamic_update_all',   array($this, 'dynamicUpdateAll'));
        add_action('wp_ajax_update_cat',       array($this, 'updateCat'));

        add_action('wp_ajax_create_products',  array($this, 'createProducts'));

    }

    /**
     * ------------------------------------------------------------------------- 
     * Ajax create product
     * -------------------------------------------------------------------------
     * @method updateCat
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function updateCat(){

        $cat_id = absint($_POST['cat_id']);
        $id     = absint($_POST['id']);

        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';

        $wpdb->update($table_berni, array(
            'category' => $cat_id,
        ), array(
            'berni_id'   => $id,
        ));

        wp_die();
    }

    /**
     * ------------------------------------------------------------------------- 
     * GOOGLE translater hack ;)
     * -------------------------------------------------------------------------
     * @method translate
     * @access private
     * 
     * @return String
     * @author <panevnyk.roman@gmail.com>
     * @since 2.0
     */
    private function translate($string, $to = false){
        
        $string = eval("return " .file_get_contents(
            self::GOOGLE.'?client=gtx&sl='.self::LANG_FROM.'&tl='.$to == false ? self::LANG_TO : $to.'&dt=t&q='
            .urlencode(
                $string
            )
        ). ";");
        return $string[0][0][0];
    }
    /**
     * ------------------------------------------------------------------------- 
     * Ajax create product
     * -------------------------------------------------------------------------
     * @method createProducts
     * @access public
     * 
     * @return Int
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function createProducts(){

        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';
        $products = array();

        $this->options = get_option( 'berni_option' );

        $products = $wpdb->get_results(
            "SELECT 
                berni_id,
                price,
                name,
                available,
                description,
                category,
                status
             FROM $table_berni
             WHERE status = 1", ARRAY_A
        );

        foreach($products as $product){

            $post_id = $this->getProductIdByBerni($product['berni_id']);

            if(!get_post($post_id)){


                $post_data = array(
                    'post_title'    => sanitize_text_field(
                        $this->translate($product['name']) ?: $product['name']
                    ),
                    'post_type'     => 'product',
                    'post_status'   => 'publish',
                    'post_content'  => sanitize_text_field(
                        $this->translate($product['description']) ?: $product['description']
                    )
                );

                $post_id = wp_insert_post( $post_data );


                $price = round($product['price']/$this->options['exchange_rate'],2);
                $price = $price + ($price * ($this->options['mark_up']/100));

                // Update SKU (berni id)
                update_post_meta($post_id, '_sku', $product['berni_id']);

                // Update price
                update_post_meta($post_id , '_regular_price', $price);
                update_post_meta($post_id , '_price', $price);

                // Update category
                if($product['category']){
                    wp_set_object_terms(
                        $post_id, 
                        get_term($product['category'])->slug, 
                        'product_cat', 
                        true
                    );
                }

                $image_list = $this->attachImage($post_id, $product['berni_id']);

                // Thumbnail
                set_post_thumbnail(
                    $post_id,
                    $image_list[0]
                );

                $image_list = implode(',', $image_list);

                // Gallery
                update_post_meta(
                    $post_id,
                    '_product_image_gallery',
                    $image_list
                );

                $this->updateParam($post_id, $product['berni_id']);

            } else {

                $price = round($product['price']/$this->options['exchange_rate'],2);
                $price = $price + ($price * ($this->options['mark_up']/100));

                // Update price
                update_post_meta($post_id , '_regular_price', $price);
                update_post_meta($post_id , '_price', $price);
                
                // Update category
                if($product['category']){
                    wp_set_object_terms(
                        $post_id, 
                        get_term($product['category'])->slug, 
                        'product_cat', 
                        true
                    );
                }

                $this->updateParam($post_id, $product['berni_id']);
            }

        }


    }
    public function rusToLat($textcyr){

        $cyr = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];
        $lat = [
            'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
            'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
        ];

        return str_replace($cyr, $lat, $textcyr);

    }

    /**
     * ------------------------------------------------------------------------- 
     * Update Param
     * -------------------------------------------------------------------------
     * @method updateParam
     * @access public
     * 
     * @return Int $post_id
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function updateParam($post_id, $product_id){

        global $wpdb;

        $table_berni_params = $wpdb->prefix . 'berni_params';

        $params = $wpdb->get_results(
            "SELECT 
                param_name,
                param_value
             FROM $table_berni_params
             WHERE berni_id = $product_id", ARRAY_A
        );
        $att = array();
        foreach($params as $param){

        
            $param_name  = str_replace(' ', '_', strtolower(
                $this->rusToLat($param['param_name'])
            ));


            $param_value = $this->rusToLat($param['param_value']);

            if(!is_term($param_name, 'product_cat')){
                $this->create_product_attribute($param_name);
                $ok = wp_insert_term(
                    $param_name,
                    'product_cat',
                    array(
                        'description'=> '',
                        'slug' => $param_name,
                        'parent' => false
                    )
                );
            }
            

            $ok = wp_set_object_terms( 
                $post_id, 
                $param_value, 
                'pa_'.$param_name, 
                true 
            );
            $att = array_merge(array(
                'pa_'.$param_name => array(
                   'name'        => 'pa_'.$param_name,
                   'value'       => $param_value,
                   'is_visible'  => '1',
                   'is_taxonomy' => '1'
                )
            ), $att);

        }
        print_r($att);
        update_post_meta( $post_id, '_product_attributes', $att);

    }

    /**
     * ------------------------------------------------------------------------- 
     * Get post by by SKU value
     * -------------------------------------------------------------------------
     * @method getProductIdByBerni
     * @access public
     * 
     * @return Int
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function getProductIdByBerni($berni_id){

        global $wpdb;

        $product_id = $wpdb->get_var( 
            $wpdb->prepare( 
                "SELECT post_id 
                FROM $wpdb->postmeta 
                WHERE meta_key='_sku' 
                AND meta_value='%s' 
                LIMIT 1", $berni_id
                ) 
        );
        return $product_id;
    }
    /**
     * ------------------------------------------------------------------------- 
     * Upload Images
     * -------------------------------------------------------------------------
     * @method attachImage
     * @access public
     * 
     * @return Array
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function attachImage($post_id, $product_id){

        global $wpdb;

        $table_berni_images = $wpdb->prefix . 'berni_images';

        $pictures = $wpdb->get_results(
            "SELECT 
                picture
             FROM $table_berni_images
             WHERE berni_id = $product_id", ARRAY_A
        );

        foreach($pictures as $image_url){

            $image_url = $image_url['picture'];

            $filename = explode('/', $image_url);
            $filename = $filename[count($filename) - 1];

            $query = "SELECT ID FROM {$wpdb->posts} WHERE post_title='$filename'";
            $res = $wpdb->get_var($query);
            $count = boolval($res);

            if($count !== false){
                $pictures_id[] = $res;
                continue;
            }

            $upload_dir = wp_upload_dir();
    
            $image_data = file_get_contents( $image_url );
    
            $filename = basename( $image_url );
    
            if ( wp_mkdir_p( $upload_dir['path'] ) ) {
                $file = $upload_dir['path'] . '/' . $filename;
            }
            else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }
    
            file_put_contents( $file, $image_data );
    
            $wp_filetype = wp_check_filetype( $filename, null );
    
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name( $filename ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
    
            $attach_id = wp_insert_attachment( $attachment, $file );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            $pictures_id[] = $attach_id;

        }

        return $pictures_id;

    }


    /**
     * ------------------------------------------------------------------------- 
     * Ajax Dynamic selector
     * -------------------------------------------------------------------------
     * @method dynamicUpdate
     * @access public
     * 
     * @return Int
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function dynamicUpdate(){

        $id = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);

        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';

        // Insert or Update Offer
        $exist = $wpdb->update($table_berni, array(
            'status'            => !$status,
        ), array(
            'berni_id'   => $id,
        ));

        return $exist;

    }

    public function dynamicUpdateAll(){

        $ids = explode(',', sanitize_text_field($_REQUEST['id']));
        $status = intval($_REQUEST['status']);

        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';

        // Insert or Update Offer
        foreach($ids as $id){

            $wpdb->update($table_berni, array(
                'status'     => $status,
            ), array(
                'berni_id'   => $id,
            ));

        }

        echo !$status;

        wp_die();
    }

    /**
     * ------------------------------------------------------------------------- 
     * Ajax initialization of importer
     * -------------------------------------------------------------------------
     * @method runImport
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function runImport(){

        $this->options = get_option('berni_option');
        
        file_put_contents(
            BERNI_CACHE . 'berni.yml', 
            file_get_contents($this->options['berni_url'])
        );

        $data = simplexml_load_file(BERNI_CACHE . 'berni.yml');
        
        if($this->offersImporter(
            $data, 
            isset($this->options['available']) ? 
            $this->options['available'] : false)){

            require_once( BERNI_TEMPLATES . 'importer_form.php' );

        }
        
        wp_die();

    }

    /**
     * ------------------------------------------------------------------------- 
     * create_product_attribute
     * -------------------------------------------------------------------------
     * @method create_product_attribute
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     * TODO: refactoring
     */
    function create_product_attribute( $label_name ){
        global $wpdb;
    
        $slug = sanitize_title( $label_name );
    
        if ( strlen( $slug ) >= 28 ) {
            return new WP_Error('invalid_product_attribute_slug_too_long', sprintf( __( 'Name "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_reserved_name', sprintf( __( 'Name "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $label_name ) ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_already_exists', sprintf( __( 'Name "%s" is already in use. Change it, please.', 'woocommerce' ), $label_name ), array( 'status' => 400 ) );
        }
    
        $data = array(
            'attribute_label'   => $label_name,
            'attribute_name'    => $slug,
            'attribute_type'    => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public'  => 0, // Enable archives ==> true (or 1)
        );
    
        $results = $wpdb->insert( 
            "{$wpdb->prefix}woocommerce_attribute_taxonomies", 
            $data 
        );
    
        if ( is_wp_error( $results ) ) {
            return new WP_Error( 'cannot_create_attribute', $results->get_error_message(), array( 'status' => 400 ) );
        }
    
        $id = $wpdb->insert_id;
    
        do_action('woocommerce_attribute_added', $id, $data);
    
        wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
    
        delete_transient('wc_attribute_taxonomies');
    }

    /**
     * -------------------------------------------------------------------------
     * Method to insert propucts to database
     * -------------------------------------------------------------------------
     * @method offersImporter
     * @access public
     * 
     * @return Boolen
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     * TODO: find why wpdb->delete('column' => "> 'DATE'") dont work
     */
    public function offersImporter($shop, $filter){


        global $wpdb;
        $table_berni = $wpdb->prefix.'berni';
        $table_berni_images = $wpdb->prefix.'berni_images';
        $table_berni_params = $wpdb->prefix . 'berni_params';
        $begin_time = date("Y-m-d H:i:s");

        foreach($shop->shop->offers->offer as $offer){
   
            $id = absint($offer['id']->__toString());
            $price = sanitize_text_field($offer->price->__toString());
            $name = sanitize_text_field($offer->name->__toString());
            $description = sanitize_text_field($offer->description->__toString());
            $available = ($offer['available']->__toString() == 'false') ? 0 : 1;
            $vendor = sanitize_text_field($offer->vendor->__toString());
            $vendor_code = sanitize_text_field($offer->vendorCode->__toString());

            // Remove unavailabel offers
            if($offer['available']->__toString() == 'false' && $filter){
                $wpdb->delete($table_berni, array( 
                    'berni_id'   => $id,
                ));
                $wpdb->delete($table_berni_images, array( 
                    'berni_id'   => $id,
                ));
                continue;                
            }

            // Insert or Update Offer
            $exist = $wpdb->update($table_berni, array(
                'price'             => $price,
                'name'              => $name,
                'description'       => $description,
                'vendor'            => $vendor,
                'vendor_code'       => $vendor_code,
                'available'         => $available,
                'date_modificate'   => date("Y-m-d H:i:s")
            ), array(
                'berni_id'   => $id,
            ));

            if(!$exist){
                $wpdb->insert($table_berni, array(
                    'berni_id'          => $id,
                    'price'             => $price,
                    'name'              => $name,
                    'description'       => $description,
                    'vendor'            => $vendor,
                    'vendor_code'       => $vendor_code,
                    'available'         => $available,
                    'status'            => 0,
                    'date_create'       => date("Y-m-d H:i:s"),
                    'date_modificate'   => date("Y-m-d H:i:s")
                ));
            }

            // Delete and insert Offer images
            $wpdb->delete($table_berni_images, array( 
                'berni_id'   => $id,
            ));

            foreach($offer->picture as $picture){
                $picture = $picture->__toString();
                $wpdb->insert($table_berni_images, array(
                    'berni_id'          => $id,
                    'picture'           => $picture,
                    'date_create'       => date("Y-m-d H:i:s"),
                    'date_modificate'   => date("Y-m-d H:i:s")
                ));

            }

            foreach($offer->param as $param){

                $param_name  = $param['name']->__toString();
                $param_value = $param->__toString();

                $wpdb->insert($table_berni_params, array(
                    'berni_id'          => $id,
                    'param_name'        => $param_name,
                    'param_value'       => $param_value,
                    'date_create'       => date("Y-m-d H:i:s"),
                    'date_modificate'   => date("Y-m-d H:i:s")
                ));
            }
            
        }

        // TODO: find why wpdb->delete('column' => "> 'DATE'") dont work
        $sql = "DELETE FROM $table_berni WHERE date_modificate < '$begin_time'";
        $wpdb->query($sql);

        $sql = "DELETE FROM $table_berni_images WHERE date_modificate < '$begin_time'";
        $wpdb->query($sql);

        $sql = "DELETE FROM $table_berni_params WHERE date_modificate < '$begin_time'";
        $wpdb->query($sql);
        
        return true;
    }

    /**
     * ------------------------------------------------------------------------- 
     * Add options page
     * -------------------------------------------------------------------------
     * @description Create Page in admin menu
     * @method addPluginPage
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function addPluginPage() {

        add_menu_page(
            'Berni importer', 
            'Berni importer', 
            'manage_options', 
            'berni_importer',
            array( $this, 'createAdminPage' )
        );

    }

    /**
     * ------------------------------------------------------------------------- 
     * Options page callback
     * -------------------------------------------------------------------------
     * @description Include page forms
     * @method createAdminPage
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function createAdminPage() {
        
        // Set class property
        $this->options = get_option( 'berni_option' );

        require_once( BERNI_TEMPLATES . 'settings.php' );
        
        require_once( BERNI_TEMPLATES . 'importer_form.php' );

    }

    /**
     * -------------------------------------------------------------------------
     * Register and add settings
     * -------------------------------------------------------------------------
     * @description Include page forms
     * @method pageInit
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function pageInit() {

        register_setting(
            'berni_option_group',                           // Option group
            'berni_option',                                 // Option name
            array( $this, 'sanitize' )                      // Sanitize
        );

        add_settings_section(
            'berni_settings_section',                       // ID
            __('Berni Settings', 'berni'),                  // Title
            array('CallBacks', 'printSectionInfo'),         // Callback
            'berni_importer'                                // Page
        );  

        add_settings_field(
            'berni_url',                                    // ID
            __('Origin vendor Url', 'berni'),               // Title
            array('CallBacks', 'berniUrlCallback'),         // Callback
            'berni_importer',                               // Page
            'berni_settings_section',                       // Section    
            array($this)                                    // Arguments      
        );

        add_settings_field(
            'exchange_rate',                                // ID
            __('Exchange rate (UAH TO EUR)', 'berni'),      // Title
            array('CallBacks', 'berniExchangeCallback'),    // Callback
            'berni_importer',                               // Page
            'berni_settings_section',                       // Section   
            array($this)                                    // Arguments          
        );

        add_settings_field(
            'mark_up',                                      // ID
            __('Percent of mark-up (%)', 'berni'),          // Title
            array('CallBacks', 'markUpCallback'),           // Callback
            'berni_importer',                               // Page
            'berni_settings_section',                       // Section   
            array($this)                                    // Arguments          
        );

        add_settings_field(
            'available',                                    // ID
            __('Import only available', 'berni'),           // Title
            array('CallBacks', 'availableCallback'),        // Callback
            'berni_importer',                               // Page
            'berni_settings_section',                       // Section   
            array($this)                                    // Arguments          
        );

    }

    /**
     * -------------------------------------------------------------------------
     * Sanitize each setting field as needed
     * -------------------------------------------------------------------------
     * @description Save fields
     * @method sanitize
     * @access public
     * @param Array $input Contains all settings fields as array keys
     * 
     * @return Mixed
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function sanitize( $input ) {

        $new_input = array();

        if( isset( $input['berni_url'] ) )
            $new_input['berni_url'] = sanitize_text_field($input['berni_url']);

        if( isset( $input['exchange_rate'] ) )
            $new_input['exchange_rate'] = absint($input['exchange_rate']);

        if( isset( $input['mark_up'] ) )
            $new_input['mark_up'] = sanitize_text_field($input['mark_up']);
            
        if( isset( $input['available'] ) )
            $new_input['available'] = absint($input['available']);

        return $new_input;
        
    }


    /**
     * -------------------------------------------------------------------------
     * Notification on Berni Settings page
     * -------------------------------------------------------------------------
     * @description Event on save fields
     * @method sanitize
     * @access public
     * 
     * @return Mixed
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function berniNotificate(){
            
        if(
            (isset($_GET['page']) && $_GET['page'] == 'berni_importer') && 
            (isset($_GET['settings-updated']) && $_GET['settings-updated'] == true)
        ){
            add_settings_error(
                'berni-notices', 
                'settings-updated', 
                __('Settings saved. Now you can run importer.', 'berni'), 
                'updated' 
            );
        }
            
        settings_errors( 'berni-notices' );
            
    }

    /**
     * -------------------------------------------------------------------------
     * Method to include all scripts
     * -------------------------------------------------------------------------
     * @method scripts
     * @access public
     * 
     * @return Mixed
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public function scripts() {

        if((isset($_GET['page']) && $_GET['page'] == 'berni_importer')){

            wp_enqueue_script(
                'jqeury', 
                BERNI_JS.'jquery.js'
            );

            wp_enqueue_script(
                'functions', 
                BERNI_JS.'functions.js'
            );

            wp_localize_script( 'functions', 'berni', 
                array(
                    'url' => admin_url('admin-ajax.php')
                )
            );

            wp_enqueue_style(
                'berni-style', 
                BERNI_CSS.'style.css'
            );
        }

    }


}

if( is_admin() )
    $my_settings_page = new BerniPage();





// $orderby = 'name';
// $order = 'asc';
// $hide_empty = false ;
// $cat_args = array(
//     'orderby'    => $orderby,
//     'order'      => $order,
//     'hide_empty' => $hide_empty,
// );
 
// $product_categories = get_terms( 'product_cat', $cat_args );
 
// if( !empty($product_categories) ){
//     echo '
 
// <ul>';
//     foreach ($product_categories as $key => $category) {
//         echo '
 
// <li>';
//         echo '<a href="'.get_term_link($category).'" >';
//         echo $category->name;
//         echo '</a>';
//         echo '</li>';
//     }
//     echo '</ul>
// ';

// }