;jQuery(document).ready(function ($) {

    $('.taxexempt :checkbox').click(function () {
        if ($(this).is(':checked')) {
            $('.taxexempt.textbox').removeClass('hidden');
        }
        else {
            $('.taxexempt.textbox').addClass('hidden');
            jQuery('.taxexempt.textbox .input-text').val('');
        }
    });

});