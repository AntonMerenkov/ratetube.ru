$(function() {
    /**
     * Форма поиска
     */
    $('#search a').click(function(e) {
        e.preventDefault();

        if ($('#search input').val() != '' && $('#search').hasClass('active'))
            $('#search form').submit();
        else
            $('#search').toggleClass('active');

        if ($('#search').hasClass('active'))
            $('#search').find('input').focus();
    });

    $('.widget-top-videos .flexslider').flexslider({
        animation: "slide",
        selector: '.video-list .video-item',
        controlsContainer: $(".widget-top-videos .control-nav"),
        customDirectionNav: $(".widget-top-videos .navigation a")
    });

    /**
     * Отображение заголовка для канала и поиска
     */
    $('.widget-top-channels .channel-item').click(function() {
        $('#channel-info').toggleClass('hidden');
    });

    $('#search form').submit(function() {
        $('#search').toggleClass('active');

        $('#search-info').find('.query').text($('#search input').val());
        $('#search-info').find('input').val($('#search input').val());
        $('#search-info').removeClass('hidden');

        return false;
    });
});