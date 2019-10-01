"use strict";
var App = function($) {

    let paginationFilter = {
        init : function($){

            // Paginations
            if($("#importer-berni .next-page, #importer-berni .prev-page").length) {
                var search_string = $("#title-search-input").val();
                if(search_string != "") {
                    $("#importer-berni .next-page, #importer-berni .prev-page, .last-page, .first-page").each(function() {
                        var url = new URL(this.href);
                        url.searchParams.set('s', search_string);
                        this.href = url;
                    });
                } else {
                    $("#importer-berni .next-page, #importer-berni .prev-page, .last-page, .first-page").each(function() {
                        var url = new URL(this.href);
                        url.searchParams.delete('s');
                        this.href = url;
                    });
                }
            }
        }
    }

    let renderBerni = {
        init : function($){
            /**
             * -----------------------------------------------------------------
             * Pre import products
             * -----------------------------------------------------------------
             * @action importer
             */
            $('body').on('click', '#render-berni', function(e){
                e.preventDefault();
                $.ajax({
                    url: berni.url,
                    type: 'POST',
                    data: 'action=importer',
                    beforeSend: function(data){
                        $('#berni_uploader').show();
                    },
                    success: function(data) {
                        $('#berni_uploader').hide();
                        $('#importer_form__wrapper').html(data);
                        alert('Loading successfully completed');
                    },
                    error : function(){
                        document.location.reload(true);
                    }
                });

            })
        }
    }

    let searchFinter = {
        init : function($){
            // Search products in table
            $('body').on('click', '#search-submit', function(e){
                e.preventDefault();
                var search_string = $("#title-search-input").val();
                var url = new URL(window.location);
                url.searchParams.delete('paged');
                url.searchParams.set('s', search_string);
                window.location = url;
            })
            /**
             * -----------------------------------------------------------------
             * Create products
             * -----------------------------------------------------------------
             * @action create_products
             */
            $('body').on('click', '#import-selected', function(e){
                let callback = () => {

                    console.log('FIRE!');
                    e.preventDefault();
                    $.ajax({
                        url: berni.url,
                        type: 'POST',
                        data: 'action=create_products',
                        beforeSend: function(data){
                            $('#berni_uploader').show();
                        },
                        success: function(data) {
                            console.log('SUCCESS!');
                            $('#berni_uploader').hide();
                            alert('Loading successfully completed');
                        },
                        error: function(){
                            console.log('ERROR!');
                            callback();
                        }
                    });

                }
                callback();
                
            })

        }
    }

    let dynamicUpdates = {
        init : function($){
            /**
             * -----------------------------------------------------------------
             * Update select field
             * -----------------------------------------------------------------
             * @action dynamic_update
             */
            $('body').on('click', '.offer-select', function(){
                $.ajax({
                    url: berni.url,
                    type: 'POST',
                    data: 'action=dynamic_update&id='+$(this).attr("data-id")+'&status='+$(this).val(),
                    success: function( data ) {
                        if(data == 1){
                            $(this).prop('checked', true);
                        } else {
                            $(this).prop('checked', false);
                        }
                    }
                });
            })

            $('body').on('click', '#check-all', function(){
                
                var values = Array();
                $('.offer-select').each(function(){
                    values.push($(this).attr("data-id"));
                });

                $.ajax({
                    url: berni.url,
                    type: 'POST',
                    data: 'action=dynamic_update_all&id='+values+'&status='+$(this).attr("data-value"),
                    beforeSend: function(data){
                        $('#berni_uploader').show();
                    },
                    success: function( data ) {

                        $('#berni_uploader').hide();
                        console.log(Boolean(data), Number(data));
                        $('.offer-select').prop('checked', !Boolean(data));

                        $('#check-all').attr("data-value", Number(data));
                    }
                });

            })
            /**
             * -----------------------------------------------------------------
             * Update category field
             * -----------------------------------------------------------------
             * @action update_cat
             */
            $('body').on('change', '.category-update', function(){
                $.ajax({
                    url: berni.url,
                    type: 'POST',
                    data: 'action=update_cat&cat_id='+$(this).children("option:selected").val()+'&id='+$(this).attr("data-id"),
                    success: function( data ) {
                        
                    }
                });
            })
        }
    }

    return {
        init : function($){
            paginationFilter.init($);
            renderBerni.init($);
            searchFinter.init($);
            dynamicUpdates.init($);
        }
    }

}();

$(document).ready(function($){
    App.init($);
})