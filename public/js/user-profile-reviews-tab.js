$(function () {
    var $reviewPills = $('.reviews-pills a[data-toggle=pill]');
    $reviewPills.click(function () {
        var $toggle = $(this);
        var $toggles = $toggle.parent().find('a[data-toggle=pill]');
        $toggles.removeClass('active');
        $toggle.addClass('active');
        $toggles.each(function () {
            var $target = $($(this).attr('href'));
            $target.hide();
        });
        $($toggle.attr('href')).show();
    });
});