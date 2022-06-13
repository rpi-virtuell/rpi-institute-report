jQuery(document).ready(()=>{

    /***
     * erzwingt, dass nur ein Jahr ausgewählt werden kann
     */
    jQuery(window).click(()=>{

        if(wp.data.select('core/editor').getCurrentPost().vintage != wp.data.select('core/editor').getEditedPostAttribute('vintage')){


            var curr= wp.data.select('core/editor').getCurrentPost().vintage[0];
            wp.data.select('core/editor').getEditedPostAttribute('vintage').forEach((value,index)=>{

                if(curr != value){
                     wp.data.dispatch('core/editor').editPost({'vintage':[value]});

                    wp.data.select('core/editor').getCurrentPost().vintage[0]=value;

                    wp.apiFetch( { path: 'wp/v2/vintage/'+value } ).then( ( tax ) => {
                        //console.log( tax.name );
                        title = wp.data.select('core/editor').getCurrentPost().title.split(' : ');
                        var start  = title.length-1;
                        title.splice(start,1);
                        title.push(tax.name);
                        wp.data.dispatch('core/editor').editPost({'title':title.join(' : ')});
                    } );

                 }
            });


        }
    });
});
