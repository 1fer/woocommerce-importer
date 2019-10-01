<div class="wrap">
    <h1><?= __('Product importer from Berni to Woocommerce', 'berni') ?></h1>
    <form method="post" action="options.php" id="berni-settings">


    <?php

        // This prints out all hidden setting fields
        
        settings_fields( 'berni_option_group' );

        do_settings_sections( 'berni_importer' );

        submit_button('Save changes', 'button button-primary', '', false, array(
            'name' => 'changes'
        ));
        submit_button('Pre-Import', 'button button-primary', '', false, array(
            'name' => 'import',
            'id'   => 'render-berni'
        ));

    ?>


    </form>
</div>

<div id="berni_uploader">
<div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
<span class="berni_uploader__title">it`s can take from 1 to 5 minutes<br>please wait...</span>
</div>