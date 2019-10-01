<div id="importer_form__wrapper">

    <?php
        $exampleListTable = new Example_List_Table();
        $exampleListTable->prepare_items();
    ?>

    <div class="wrap">
        <form method="post" id="importer-berni">
            <?php $exampleListTable->search_box('Search', 'title'); ?>
            <?php $exampleListTable->display(); ?>
        </form>
    </div>

</div>