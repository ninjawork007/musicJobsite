window.Gleam = [];

function getGleamData($element) {
    var value = $element.data('value');
    var gleamData = [$element.data('name')];
    if (value) {
        gleamData.push(value);
    }

    return gleamData;
}

(function(d, t){
    var key = 'Wb6nW';
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = "https://gleam.io/"+key+"/trk.js"; s.parentNode.insertBefore(g, s);
}(document, "script"));

$(function () {
    $('body').on('click', 'a.gleam-btn', function (event) {
        event.preventDefault();
        var $btn = $(this);
        try {
            Gleam.push(getGleamData($btn), ['callback', function () {
                console.log('callback!');
                window.location.href = $btn.attr('href');
            }]);
        } catch (e) {
            console.error('Could not push gleam event: ' + e.toString());
            window.location.href = $btn.attr('href');
        }
        setTimeout(function () {
            window.location.href = $btn.attr('href');
        }, 500);
    });

    var gleamData = [];

    $('.gleam-data-layer[data-name]').each(function () {
        gleamData.push(getGleamData($(this)));
    });

    if (gleamData.length > 0) {
        Gleam.push(...gleamData);
    }
});