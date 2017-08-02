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

    /**
     * Индикатор обновления
     */

    const updateTime = 10000;

    $('#refresh-progress').on('circle-animation-end', function(event) {
        if ($('#refresh-progress').circleProgress('value') == 1) {
            $('#refresh-progress').circleProgress({
                value: 0,
                animation: { duration: 0, easing: "swing" }
            });
            setTimeout(function() {
                $('#refresh-progress').circleProgress({
                    value: 1,
                    animationStartValue: 0,
                    animation: { duration: updateTime, easing: "swing" }
                });
            }, 50);

            console.log('update');
            //updateStatistics();
        }
    });

    $('#refresh-progress').circleProgress({
        startAngle: -Math.PI / 6 * 3,
        value: 1,
        size: 58,
        fill: {
            color: "#67c1f5"
        },
        emptyFill: "rgba(0, 0, 0, .2)",
        animation: { duration: updateTime, easing: "swing" }
    });

    $('#refresh-control').click(function() {
        $(this).toggleClass('paused');

        if ($(this).hasClass('paused')) {
            $('#refresh-progress').circleProgress({
                value: $('#refresh-progress').circleProgress('progressValue'), // circleProgress модифицирован
                animation: { duration: 0, easing: "swing" }
            });
        } else {
            $('#refresh-progress').circleProgress({
                value: 1,
                animationStartValue: $('#refresh-progress').circleProgress('progressValue'),
                animation: { duration: Math.round((1 - $('#refresh-progress').circleProgress('progressValue')) * updateTime), easing: "swing" }
            });
        }
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