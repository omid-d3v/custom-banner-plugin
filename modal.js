jQuery(document).ready(function($) {
    // Open the modal when the button is clicked
    $('#open-modal').on('click', function() {
        $('#banner-modal').show();
    });

    // Close the modal when the form is submitted
    $('#add-banner-form').on('submit', function() {
        $('#banner-modal').hide();
    });
});
jQuery(document).ready(function($) {
    $("#custom_banner_tags").select2();
});