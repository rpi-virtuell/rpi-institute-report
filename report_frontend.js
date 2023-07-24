jQuery('#vintage').on("submit",(e)=>{
    var jears = [];
    var data = jQuery('#vintage').serializeArray();
    for (const j of data){
        jears.push(j.value);
    }
    location.href='?vintage=' + jears.join(',');
    return false;
});

jQuery(document).ready(()=>{
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.size>0 &&  urlParams.get('vintage')!==''){
        const jears = urlParams.get('vintage').split(',');
        for(const jear of jears){
            jQuery('#'+jear).prop('checked', true);
        }

    }else{
        const dateobj = new Date();
        const year = dateobj.getFullYear();
        jQuery('#'+year).prop('checked', true);
    }
});
