$('.addon-item').on('click', function (e) {
    if ($(this).hasClass('selected')) {
        $('[type=checkbox]', this).iCheck('uncheck');
    }
    else {
        $('[type=checkbox]', this).iCheck('check');
    }
});

// Reload page state if user navigates back in browser.
$(function () {
    if (parseInt($('.js-upgrade-to-pro-check').val()) === 1) {
        $('form#publish-form .btn-upgrade-pro').trigger('click');
    }

    if ($('#check-private')[0].checked) {
        $('#check-private').trigger('ifChecked');
    }
})

$('form#publish-form .btn-upgrade-pro').click(function (event) {
    event.preventDefault();
    localStorage.setItem('uuid', $('#publish-form').data('uuid'));
    window.location = $(this).attr('href');
})

$('.addon-item [type=checkbox]').on('ifChecked', function (e) {
    updateCalculator();
});

$('.addon-item [type=checkbox]').on('ifUnchecked', function (e) {
    updateCalculator();
});

function updateCalculator() {
    var isPro = $('form#publish-form').hasClass('subscribed');
    var couldSave      = 0;
    var upgrades       = 0;
    var vocalizrFee    = getCalcRow('vocalizr-fee');
    var contestBudget  = getCalcRow('contest-budget');
    var proVocalizrFee = getCalcRow('vocalizr-fee', 'save');
    var walletAmount   = getCalcRow('wallet', 'wallet');

    if (vocalizrFee && vocalizrFee - proVocalizrFee > 0) {
        couldSave += vocalizrFee - proVocalizrFee;
    }

    $('.addon-item').each(function (index, element) {
        var $item = $(element);

        var freePrice = parseFloat($item.data('free-price')) || 0;
        var proPrice  = parseFloat($item.data('pro-price')) || 0;

        if (!$item.find('[type=checkbox]')[0].checked) {
            $item.removeClass('selected');
            return;
        }

        $item.addClass('selected');

        if (isPro) {
            upgrades += proPrice;
        } else {
            upgrades += freePrice;
            couldSave += freePrice - proPrice;
        }
    });

    setCalcRow('upgrades', upgrades);
    var totalAmount       = upgrades + contestBudget + vocalizrFee;
    var transactionAmount = Math.max(totalAmount - walletAmount, 0);
    var walletTransactionAmount = totalAmount - transactionAmount;
    setCalcRow('transaction-fee', transactionAmount > 0 ? (transactionAmount * 0.036 + 0.3) : 0);
    setCalcRow('wallet', walletTransactionAmount > 0 ? walletTransactionAmount : 0);

    $('#could-save-amount').text(couldSave);

    if (isPro) {
        $('#subscription-promo').addClass('hide');
        $('#could-save').addClass('hide');
    } else if (couldSave > 0) {
        $('#subscription-promo').addClass('hide');
        $('#could-save').removeClass('hide');
    } else {
        $('#subscription-promo').removeClass('hide');
        $('#could-save').addClass('hide');
    }

    recalculateTotal();
}

updateCalculator();

function setCalcRow(title, value, dataAttr) {
    var $item = $('.fee-calculator .calculator-item[data-role=' + title + ']');
    var $number = $item.find('.calculator-value .number');

    value = formatPrice(value);

    if (dataAttr !== undefined) {
        $number.data(dataAttr, value);
    } else {
        $item.removeClass('hide');
        $number.text(value);
    }
}

function formatPrice(value) {
    value = Math.round(value * 100) / 100;

    if (value - parseInt(value) === 0) {
        return parseInt(value);
    }

    if ((value * 100) % 10 === 0) {
        return value.toFixed(1);
    }

    return value.toFixed(2);
}

function getCalcRow(title, dataAttr) {
    var $el = $('.fee-calculator .calculator-item[data-role=' + title + '] .calculator-value .number');
    var val = 0;

    if (dataAttr !== undefined) {
        val = $el.data(dataAttr);
    } else {
        val = $el.text();
    }

    return parseFloat(val) || 0;
}

function recalculateTotal() {
    var total = 0;
    $('.fee-calculator .calculator-item:not(.hide,.ignore) .calculator-value .number').each(function (index, element) {
        var $element = $(element);
        var val = parseFloat($element.text()) || 0;
        total += val;
    })

    if (total > 0) {
        $('#payment').removeClass('hide');
        $('#publish').addClass('hide');
    } else {
        $('#payment').addClass('hide');
        $('#publish').removeClass('hide');
    }

    $('#total-price').text(formatPrice(total));
}

$('#check-private').on('ifChecked', function (e) {
    $('.public-upgrades').slideUp('fast', function () {
        $('.public-upgrades [type="checkbox"]').iCheck('uncheck');
    });
});
$('#check-private').on('ifUnchecked', function (e) {
    $('.public-upgrades').slideDown('fast');
});
