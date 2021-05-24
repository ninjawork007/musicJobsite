function closeModal($wrapper) {
    $wrapper.addClass('hhm-hidden');
}

$(document).ready(function () {
    var $hintModalWrapper = $('#hint-modal-wrap');
    var $hintModal = $('#helpful-hint-modal');

    $hintModal.find('.skip-btn').click(function (e) {
        e.preventDefault();
        closeModal($hintModalWrapper);
    });

    $hintModal.find('.disable-btn').click(function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).data('href'),
            success: function () {
                closeModal($hintModalWrapper);
            }
        });
    });

    $hintModalWrapper.click(function (e) {
        if ($(e.target).is($hintModalWrapper)) {
            closeModal($hintModalWrapper);
        }
    })

});