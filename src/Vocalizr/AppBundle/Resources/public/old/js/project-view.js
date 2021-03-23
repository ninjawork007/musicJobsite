$(function() {
    $('#form-bid-amount').blur(function () {
           $(this).toNumber();
           if ($(this).val() < 0) {
               $(this).val('');
               return;
           }
           $(this).formatCurrency({symbol: ''});
           
    });
});