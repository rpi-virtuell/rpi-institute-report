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
    urlParams = new URLSearchParams(window.location.search);
    jears = urlParams.get('vintage').split(',');
    for(const jear of jears){
    jQuery('#'+jear).prop('checked', true);
}
});
