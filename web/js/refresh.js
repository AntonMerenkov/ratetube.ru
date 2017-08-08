$(function() {
    function updateStatistics() {
        /**
         * Обновление позиций
         */
        $.ajax({
            url: $('#refreshButton').attr('href'),
            dataType: 'json'
        }).success(function(newData) {
            // превращаем верстку в абсолютную
            $('#news-table').css({
                height: $('#news-table').height() + 'px',
                position: 'relative'
            });

            var firstColWidth = $('#news-table thead th:first-child').outerWidth();
            var positions = $('#news-table').find('tbody tr').map(function() {
                return $(this).position().top - 2;
            });
            $('#news-table').find('tbody tr').each(function(index) {
                $(this).css({
                    position: 'absolute',
                    top: positions[ index ],
                    left: 0,
                    right: 0
                }).attr('data-top', positions[ index ]).find('td:first-child').css('width', firstColWidth + 'px');
            });

            // меняем структуру согласно новым данным
            var rows = $('#news-table').find('tbody tr');
            var oldIds = $.makeArray(rows.map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            var newIds = newData.map(function(item) {
                return parseInt(item.id);
            });

            // анимация
            // скрываем старые элементы
            for (var i in oldIds) {
                if (newIds.indexOf(oldIds[ i ]) == -1) {
                    rows.eq(i).animate({opacity: 0}, 400, 'swing', function() {
                        $(this).addClass('hidden');
                    });
                }
            }

            for (var i in newIds) {
                if (newIds[ i ] != oldIds[ i ]) {
                    if (oldIds.indexOf(newIds[ i ]) == -1) {
                        // добавляем новый элемент
                        $('#news-table').find('tbody').append($('<tr data-id="' + newData[ i ].id + '" class="warning" style="position: absolute; top: ' + positions[ i ] + 'px; left: 0; right: 0;" data-top="' + positions[ i ] + '">' +
                            '<td style="width: 693px;">' +
                            '<div class="cell-table">' +
                                '<div class="cell-table-cell"><a class="channel-link" href="/channel/' + newData[ i ].channel.id + '" title="' + newData[ i ].channel.name + '" style="background-image: url(\"' + newData[ i ].channel.image_url + '\")"></a></div>' +
                                '<div class="cell-table-cell"><a href="https://www.youtube.com/watch?v=' + newData[ i ].video_link + '" target="_blank">' + newData[ i ].name + '</a></div>' +
                                '<div class="cell-table-cell"><a href="#" class="info"></a></div>' +
                            '</div>' +
                            '</td>' +
                            '<td>' + (newData[ i ].views_diff > 0 ? '+' + newData[ i ].views_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].likes_diff > 0 ? '+' + newData[ i ].likes_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].dislikes_diff > 0 ? '+' + newData[ i ].dislikes_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].likes > 0 ? '+' + newData[ i ].likes : "") + '</td>' +
                            '</tr>'));
                    } else {
                        // изменяем позицию элемента
                        var element = $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ]));
                        if (parseInt(element.attr('data-top')) > positions[ i ]) {
                            element.find('td').animate({'background-color': '#264155'}, 1800, 'swing', function() {
                                $(this).parent().addClass('active');
                                $(this).delay(2000).animate({'background-color': '#16202d'}, 2000, 'swing', function() {
                                    $(this).parent().removeClass('active');
                                });
                            });
                        }

                        element.attr('data-top', positions[ i ]).animate({top: positions[ i ]});
                    }
                }
            }

            // выравниваем элементы по верху с отступом 2px, т.к. у элементов может быть разная высота
            var currentPosition = $('#news-table thead tr').outerHeight() - 2;
            if (currentPosition > 100)
                currentPosition = 32;
            var rowsSorted = $('#news-table tbody tr').sort(function(a, b) {
                return parseInt($(a).attr('data-top')) - parseInt($(b).attr('data-top'));
            });

            rowsSorted.each(function() {
                $(this).animate({'top': currentPosition + 'px'});
                currentPosition += $(this).outerHeight() - 2;
            });
            $('#news-table').css('height', (currentPosition + 2) + 'px');

            // устанавливаем новые значения статистики для существующих элементов
            for (var i in newData) {
                //console.log(newData[ i ]);
                var row = rows.filter('[data-id="' + newData[ i ].id + '"]');

                if (row.find('td').eq(1).text() != (newData[ i ].views_diff == 0 ? '' : '+' + newData[ i ].views_diff))
                    row.find('td').eq(1).css({
                        color: 'rgba(103, 193, 245, 0)',
                        transition: 'none'
                    }).text(newData[ i ].views_diff == 0 ? '' : '+' + newData[ i ].views_diff).animate({
                        color: 'rgba(103, 193, 245, 0.35)'
                    });

                if (row.find('td').eq(2).text() != (newData[ i ].likes_diff == 0 ? '' : '+' + newData[ i ].likes_diff))
                    row.find('td').eq(2).css({
                        color: 'rgba(113, 213, 76, 0)',
                        transition: 'none'
                    }).text(newData[ i ].likes_diff == 0 ? '' : '+' + newData[ i ].likes_diff).animate({
                        color: 'rgba(113, 213, 76, 0.35)'
                    });

                if (row.find('td').eq(3).text() != (newData[ i ].dislikes_diff == 0 ? '' : '+' + newData[ i ].dislikes_diff))
                    row.find('td').eq(3).css({
                        color: 'rgba(255, 69, 57, 0)',
                        transition: 'none'
                    }).text(newData[ i ].dislikes_diff == 0 ? '' : '+' + newData[ i ].dislikes_diff).animate({
                        color: 'rgba(255, 69, 57, 0.35)'
                    });

                if (row.find('td').eq(4).text() != (newData[ i ].likes == 0 ? '' : newData[ i ].likes))
                    row.find('td').eq(4).css({
                        color: 'rgba(255, 255, 255, 0)',
                        transition: 'none'
                    }).text(newData[ i ].likes == 0 ? '' : newData[ i ].likes).css({
                        color: 'rgba(255, 255, 255, 0.35)'
                    });
            }

            // статичная замена
            setTimeout(function() {
                // удаляем невидимые элементы
                $('#news-table').find('tbody tr.hidden').remove();

                rows = $('#news-table').find('tbody tr');
                oldIds = $.makeArray(rows.map(function() {
                    return parseInt($(this).attr('data-id'));
                }));

                rows.removeAttr('data-top');

                for (var i in newIds)
                    if (newIds[ i ] != oldIds[ i ]) {
                        rows.eq(oldIds.indexOf(newIds[ i ])).insertBefore(rows.eq(i));

                        rows = $('#news-table').find('tbody tr');
                        oldIds = $.makeArray(rows.map(function() {
                            return parseInt($(this).attr('data-id'));
                        }));
                    }

                $('#news-table').find('tbody tr').removeAttr('style').removeClass('warning');
                $('#news-table').find('tbody tr td').removeAttr('style');
                $('#news-table').removeAttr('style');
            }, 2000);
        });
    }

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

            updateStatistics();
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
});