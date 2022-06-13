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
                 }
            });


        }
    });
});
