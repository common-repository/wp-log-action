jQuery(document).ready(function($){
    $(".wpla-filters .datepicker").datepicker();
    $("#doaction").on("click", function(e){
        $(this).closest("form").attr("method", "post");
    })
});