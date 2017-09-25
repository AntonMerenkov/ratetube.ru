var RGBvalues = (function () {
    var _hex2dec = function (v) {
        return parseInt(v, 16)
    };

    var _splitHEX = function (hex) {
        var c;
        if (hex.length === 4) {
            c = (hex.replace('#', '')).split('');
            return {
                r: _hex2dec((c[0] + c[0])),
                g: _hex2dec((c[1] + c[1])),
                b: _hex2dec((c[2] + c[2]))
            };
        } else {
            return {
                r: _hex2dec(hex.slice(1, 3)),
                g: _hex2dec(hex.slice(3, 5)),
                b: _hex2dec(hex.slice(5))
            };
        }
    };

    var _splitRGB = function (rgb) {
        var c = (rgb.slice(rgb.indexOf('(') + 1, rgb.indexOf(')'))).split(',');
        var flag = false, obj;
        c = c.map(function (n, i) {
            return (i !== 3) ? parseInt(n, 10) : flag = true, parseFloat(n);
        });
        obj = {
            r: c[0],
            g: c[1],
            b: c[2]
        };
        if (flag) obj.a = c[3];
        return obj;
    };

    var color = function (col) {
        var slc = col.slice(0, 1);
        if (slc === '#') {
            return _splitHEX(col);
        } else if (slc.toLowerCase() === 'r') {
            return _splitRGB(col);
        } else {
            console.log('!Ooops! RGBvalues.color(' + col + ') : HEX, RGB, or RGBa strings only');
        }
    };

    return {
        color: color
    };
}());

$(function() {
    function animateCell(cell, newValue) {
        if (cell.text() == newValue)
            return;

        if (Visibility.state() != 'visible')
            return true;

        if (cell.attr('data-animation') == 1) {
            cell.text(newValue);
            return;
        }

        var textColor = RGBvalues.color(cell.css('color'));

        /*console.debug(RGBvalues.color('rgb(52, 86, 120)'));
        console.debug(RGBvalues.color('#345678'));
        console.debug(RGBvalues.color('rgba(52, 86, 120, 0.67)'));
        console.debug(RGBvalues.color('#357'));*/

        cell.attr('data-animation', 1).animate({'background-color': 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', 0.1)'}, 300, function() {
            cell.css({
                color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', 0)',
                transition: 'none'
            }).text(newValue).animate({
                color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', ' + Math.min(textColor.a * 2, 1) + ')'
            }, 500, function() {
                $(this).animate({
                    color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', ' + textColor.a + ')'
                }, 500, function() {
                    $(this).removeAttr('style');
                })
            });

            $(this).animate({'background-color': 'rgba(22, 32, 45, 1)'}, 500, function() {
                $(this).removeAttr('style').removeAttr('data-animation');
            });
        });
    }

    function updateStatistics() {
        // ничего не обновляем, если вкладка не активна
        if (Visibility.state() != 'visible')
            return true;

        /**
         * Обновление позиций
         */
        $.ajax({
            url: $('#refreshButton').attr('href'),
            dataType: 'json'
        }).success(function(data) {
            var newData = data[ 'data' ];

            // меняем структуру согласно новым данным
            var rows = $('#news-table').find('tbody tr');
            var oldIds = $.makeArray(rows.map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            var oldIdsCheck = $.makeArray(rows.filter('[data-id]').map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            var newIds = newData.map(function(item) {
                return parseInt(item.id);
            });

            // анимация
            // если данные не изменились - ничего не делаем
            if (JSON.stringify(oldIdsCheck) != JSON.stringify(newIds)) {
                // превращаем верстку в абсолютную
                $('#news-table').css({
                    height: $('#news-table').height() + 'px',
                    position: 'relative'
                });

                var firstColWidth = $('#news-table thead th:first-child').outerWidth();
                var positions = $('#news-table').find('tbody tr').map(function() {
                    return $(this).position().top - 2;
                });
                var heights = $('#news-table').find('tbody tr').map(function() {
                    return $(this).outerHeight() + 2;
                });
                $('#news-table').find('tbody tr').each(function(index) {
                    $(this).css({
                        position: 'absolute',
                        top: positions[ index ],
                        left: 0,
                        right: 0
                    }).attr('data-top', positions[ index ]).attr('data-height', heights[ index ]);

                    if ($(this).hasClass('info-row'))
                        $(this).find('td:first-child').css('width', $(this).outerWidth() + 'px');
                    else
                        $(this).find('td:first-child').css('width', firstColWidth + 'px');
                });

                // анимация
                // скрываем старые элементы
                for (var i in oldIds) {
                    if (newIds.indexOf(oldIds[ i ]) == -1) {
                        if (!rows.eq(i).hasClass('info-row'))
                            rows.eq(i).animate({opacity: 0}, 400, 'swing', function() {
                                $(this).addClass('hidden');
                            });
                    }
                }

                // расставляем ряды с начальной позиции
                var currentPosition = rows.first().position().top;

                for (var i in newIds) {
                    if (oldIds.indexOf(newIds[ i ]) == -1) {
                        // добавляем новый элемент
                        var newRow = $('<tr data-id="' + newData[ i ].id + '" class="warning" style="position: absolute; top: ' + currentPosition + 'px; left: 0; right: 0;" data-top="' + currentPosition + '" data-height="">' +
                            '<td style="width: 693px;">' +
                            '<div class="cell-table">' +
                            '<div class="cell-table-cell"><a class="channel-link" href="/channel/' + newData[ i ].channel.id + '" title="' + newData[ i ].channel.name + '" style="background-image: url(\'' + newData[ i ].channel.image_url + '\')"></a></div>' +
                            '<div class="cell-table-cell"><a href="https://www.youtube.com/watch?v=' + newData[ i ].video_link + '" data-image="' + newData[ i ].image_url + '" target="_blank">' + newData[ i ].name + '</a></div>' +
                            '<div class="cell-table-cell"><a href="#" class="info"></a></div>' +
                            '</div>' +
                            '</td>' +
                            '<td>' + (newData[ i ].views_diff > 0 ? '+' + newData[ i ].views_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].likes_diff > 0 ? '+' + newData[ i ].likes_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].dislikes_diff > 0 ? '+' + newData[ i ].dislikes_diff : "") + '</td>' +
                            '<td>' + (newData[ i ].likes > 0 ? '+' + newData[ i ].likes : "") + '</td>' +
                            '<td>' + (newData[ i ].position_diff > 0 ? '+' + newData[ i ].position_diff : (newData[ i ].position_diff < 0 ? newData[ i ].position_diff : "")) + '</td>' +
                            '</tr>').appendTo($('#news-table').find('tbody'));

                        newRow.attr('data-height', newRow.outerHeight());
                        currentPosition += newRow.outerHeight();
                    } else {
                        // изменяем позицию элемента
                        var element = $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ]));

                        // если видео поднялось - подсвечиваем его
                        if (parseInt(element.attr('data-top')) > currentPosition) {
                            element.find('td').animate({'background-color': '#264155'}, 1800, 'swing', function() {
                                $(this).parent().addClass('active');
                                $(this).delay(2000).animate({'background-color': '#16202d'}, 2000, 'swing', function() {
                                    $(this).parent().removeClass('active');
                                });
                            });
                        }

                        if (parseInt(element.attr('data-top')) != currentPosition) {
                            //console.log('Элемент ' + oldIds.indexOf(newIds[ i ]), element.attr('data-top') + 'px => ' + currentPosition + 'px');
                            element.attr('data-top', currentPosition).animate({top: currentPosition});
                            currentPosition += parseInt(element.attr('data-height'));

                            // если следом следует информационный ряд - переместить его следом за элементом
                            var infoElement = $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ]) + 1);
                            if (infoElement.hasClass('info-row')) {
                                //console.log('Инфо-элемент ' + (oldIds.indexOf(newIds[ i ]) + 1), currentPosition + 'px');

                                infoElement.attr('data-top', currentPosition).animate({top: currentPosition});
                                currentPosition += parseInt(infoElement.attr('data-height'));
                            }
                        } else {
                            currentPosition += parseInt(element.attr('data-height'));
                        }
                    }

                    //console.log('Новая позиция ' + currentPosition + 'px');
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

                // статичная замена
                setTimeout(function() {
                    // удаляем невидимые элементы
                    $('#news-table').find('tbody tr.hidden').remove();

                    rows = $('#news-table').find('tbody tr').filter('[data-id]');
                    oldIds = $.makeArray(rows.map(function() {
                        return parseInt($(this).attr('data-id'));
                    }));

                    rows.removeAttr('data-top');
                    rows.removeAttr('data-height');

                    var infoRows = {};
                    $('#news-table').find('tbody tr').filter('.info-row').each(function() {
                        infoRows[ $(this).prev('tr').attr('data-id') ] = $(this);
                    });

                    for (var i in newIds) {
                        if (newIds[ i ] != oldIds[ i ]) {
                            rows.eq(oldIds.indexOf(newIds[ i ])).insertBefore(rows.eq(i));

                            rows = $('#news-table').find('tbody tr').filter('[data-id]');
                            oldIds = $.makeArray(rows.map(function() {
                                return parseInt($(this).attr('data-id'));
                            }));
                        }
                    }

                    for (var id in infoRows)
                        infoRows[ id ].insertAfter(rows.filter('[data-id="' + id + '"]'));

                    $('#news-table').find('tbody tr').removeAttr('style').removeClass('warning');
                    $('#news-table').find('tbody tr td').removeAttr('style');
                    $('#news-table').removeAttr('style');
                }, 2000);
            }

            // устанавливаем новые значения статистики для существующих элементов
            var animationInterval = 80;
            for (var i in newData) {
                var row = rows.filter('[data-id="' + newData[ i ].id + '"]');

                setTimeout(animateCell.bind(null, row.find('td:eq(1)'), newData[ i ].views_diff == 0 ? '' : '+' + newData[ i ].views_diff), (parseInt(i) + 1) * animationInterval);
                setTimeout(animateCell.bind(null, row.find('td:eq(2)'), newData[ i ].likes_diff == 0 ? '' : '+' + newData[ i ].likes_diff), (parseInt(i) + 2) * animationInterval);
                setTimeout(animateCell.bind(null, row.find('td:eq(3)'), newData[ i ].dislikes_diff == 0 ? '' : '+' + newData[ i ].dislikes_diff), (parseInt(i) + 3) * animationInterval);
                setTimeout(animateCell.bind(null, row.find('td:eq(4)'), newData[ i ].views == 0 ? '' : newData[ i ].views), (parseInt(i) + 4) * animationInterval);
                setTimeout(animateCell.bind(null, row.find('td:eq(5)'), newData[ i ].position_diff == 0 ? '' : (newData[ i ].position_diff > 0 ? '+' + newData[ i ].position_diff : newData[ i ].position_diff)), (parseInt(i) + 5) * animationInterval);
            }

            // обновление виджета В эфире
            var newStreaming = data[ 'streaming' ];
            newStreaming = newStreaming.slice(0, parseInt($('.widget-streaming .video-list').attr('data-count')));

            rows = $('.widget-streaming').find('.video-item');
            oldIds = $.makeArray(rows.map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            newIds = newStreaming.map(function(item) {
                return parseInt(item.id);
            });

            // анимация
            // если данные не изменились - ничего не делаем
            if (JSON.stringify(oldIds) != JSON.stringify(newIds)) {
                if (newIds.length == 0)
                    $('.widget-streaming').addClass('hidden');
                else
                    $('.widget-streaming').removeClass('hidden');

                // фиксируем высоту
                $('.widget-streaming .video-list').css({
                    height: $('.widget-streaming .video-list').height() + 'px'
                });

                // скрываем все элементы
                rows.animate({opacity: 0}, 400, 'swing');

                setTimeout(function() {
                    // удаляем пропавшие
                    for (var i in oldIds) {
                        if (newIds.indexOf(oldIds[ i ]) == -1) {
                            rows.eq(i).remove();
                        }
                    }

                    rows = $('.widget-streaming').find('.video-item');
                    oldIds = $.makeArray(rows.map(function() {
                        return parseInt($(this).attr('data-id'));
                    }));

                    for (var i in newIds) {
                        if (oldIds.indexOf(newIds[ i ]) == -1) {
                            // добавляем новый элемент
                            var newRow = $('<div class="video-item" data-id="' + newStreaming[ i ].id + '">\n' +
                                '<a href="/channel/' + newStreaming[ i ].channel.id + '" class="channel-name">' + newStreaming[ i ].channel.name + '</a>\n' +
                                '<a href="https://www.youtube.com/watch?v=' + newStreaming[ i ].video_link + '" class="link" target="_blank">\n' +
                                '<img src="' + newStreaming[ i ].image_url + '" class="image">\n' +
                                '<div class="name">' + newStreaming[ i ].name + '</div>\n' +
                                '</a>\n' +
                                '</div>');

                            if (i == 0)
                                newRow.prependTo($('.widget-streaming .video-list'));
                            else
                                newRow.insertAfter($('.widget-streaming .video-list .video-item').eq(i - 1));

                            rows = $('.widget-streaming').find('.video-item');
                        } else {
                            // изменяем позицию элемента
                            if (oldIds.indexOf(newIds[ i ]) != i) {
                                if (i == 0)
                                    rows.eq(oldIds.indexOf(newIds[ i ])).prependTo($('.widget-streaming .video-list'));
                                else
                                    rows.eq(oldIds.indexOf(newIds[ i ])).insertAfter($('.widget-streaming .video-list .video-item').eq(i - 1));

                                rows = $('.widget-streaming').find('.video-item');
                                oldIds = $.makeArray(rows.map(function() {
                                    return parseInt($(this).attr('data-id'));
                                }));
                            }
                        }
                    }

                    // делаем анимацию высоты
                    var height = 0;
                    rows.each(function() {
                        height += $(this).outerHeight() + 5;
                    });

                    $('.widget-streaming .video-list').animate({
                        height: height + 'px'
                    }, 400, function() {
                        $(this).removeAttr('style');
                    });

                    // показываем все элементы
                    rows.animate({opacity: 1}, 400, 'swing', function() {
                        $(this).removeAttr('style');
                    });
                }, 500);
            }
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