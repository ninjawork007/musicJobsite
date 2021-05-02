/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(function () {
    $('#anchoredNav').addClass("affix-top").each(function (){
        var $self = $(this);
        var offsetFn = function () {
            var $$ = $('.home-header');
            var h = 0;
            $$.each(function () { h+=$(this).outerHeight();});
            return h;
        }
        $self.affix({offset: {top: offsetFn}});
    });
    
    $('.scroll-prompt').on('click', function() {
        $("html, body").animate({ scrollTop: $('#nav').offset().top }, 1000, 'swing');
    });
});


