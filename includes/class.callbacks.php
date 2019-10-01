<?php

/**
 * =============================================================================
 * Berni Call Backs class
 * =============================================================================
 * 
 * @subpackage Berni
 * 
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 * @since 1.0
 */
class CallBacks{

    /**
     * -------------------------------------------------------------------------
     * Print the Section text
     * -------------------------------------------------------------------------
     * @method printSectionInfo
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public static function printSectionInfo() {

        print 'Enter your settings below:';

    }

    /**
     * -------------------------------------------------------------------------
     * Get the settings option array and print one of its values
     * -------------------------------------------------------------------------
     * @method berniUrlCallback
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public static function berniUrlCallback($data) {

        printf(
            '<input type="url" id="berni_url" 
                    name="berni_option[berni_url]" 
                    class="regular-text code" value="%s" />',
            isset($data[0]->options['berni_url']) ? 
            esc_attr($data[0]->options['berni_url']) : ''
        );

    }

    /**
     * -------------------------------------------------------------------------
     * Get the settings option array and print one of its values
     * -------------------------------------------------------------------------
     * @method berniExchangeCallback
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public static function berniExchangeCallback($data) {

        printf(
            '<input type="number" id="exchange_rate" 
                    name="berni_option[exchange_rate]" 
                    class="regular-text code" value="%s" />',
            isset($data[0]->options['exchange_rate']) ? 
            esc_attr($data[0]->options['exchange_rate']) : ''
        );

    }

    /**
     * -------------------------------------------------------------------------
     * Get the settings option array and print one of its values
     * -------------------------------------------------------------------------
     * @method markUpCallback
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public static function markUpCallback($data) {

        printf(
            '<input type="text" id="mark_up" 
                    name="berni_option[mark_up]" 
                    class="regular-text code" value="%s" />',
            isset($data[0]->options['mark_up']) ? 
            esc_attr($data[0]->options['mark_up']) : ''
        );

    }


    /**
     * -------------------------------------------------------------------------
     * Get the settings option array and print one of its values
     * -------------------------------------------------------------------------
     * @method availableCallback
     * @access public
     * 
     * @return Void
     * @author <panevnyk.roman@gmail.com>
     * @since 1.0
     */
    public static function availableCallback($data){

        printf(
            '<input type="checkbox" id="available" 
                    name="berni_option[available]" 
                    class="regular-text code"
                    value="1"' . checked( 
                        1, 
                        $data[0]->options['available'], 
                        false 
                    ) . '/>',
            isset($data[0]->options['available']) ? 
            esc_attr($data[0]->options['available']) : ''
        );

    }

    

}