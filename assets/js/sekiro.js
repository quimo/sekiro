jQuery(document).ready(function($) {
    
    console.log('Plugin boilerplate loaded...');
    
    jQuery.ajax({
        type: "POST",
        url: init_ajax.url,
        data: { 
          action: 'hello_world_ajax'
        },
        dataType: "json"
    })
    .done(function(response) {
        console.log(response);
    })
    .fail(function(){
        console.log('failed');
    });
});