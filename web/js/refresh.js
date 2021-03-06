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
        if (cell.length == 0)
            return;

        if (cell.text() == newValue)
            return;

        if (Visibility.state() != 'visible')
            return true;

        if (cell.attr('data-animation') == 1) {
            cell.text(newValue);
            return;
        }

        var textColor = RGBvalues.color(cell.css('color'));

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

            $(this).animate({'background-color': $(this).closest('tr').attr('data-special') != undefined ? 'rgba(65, 84, 100, 1)' : 'rgba(22, 32, 45, 1)'}, 500, function() {
                $(this).removeAttr('style').removeAttr('data-animation');
            });
        });
    }

    function animateCellPosition(cell) {
        var textColor = RGBvalues.color(cell.css('color'));

        if (textColor.a == undefined)
            textColor.a = 1;

        cell.animate({
            'background-color': '#264155',
            color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', 1)'
        }, 1200, 'swing', function() {
            $(this).delay(200).animate({
                'background-color': ($(this).closest('tr').is('[data-special]') ? '#415464' : '#16202d'),
                color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', ' + textColor.a + ')'
            }, 1200, 'swing');
        });
    }

    function animateCellPositionName(cell) {
        var textColor = RGBvalues.color(cell.css('color'));

        if (textColor.a == undefined)
            textColor.a = 1;

        cell.animate({
            'background-color': '#264155',
            color: 'rgba(255, 255, 255, 1)'
        }, 1200, 'swing', function() {
            $(this).delay(200).animate({
                'background-color': ($(this).closest('tr').is('[data-special]') ? '#415464' : '#16202d'),
                color: 'rgba(' + textColor.r + ', ' + textColor.g + ', ' + textColor.b + ', ' + textColor.a + ')'
            }, 1200, 'swing');
        });
    }

    function updateStatistics() {
        // ???????????? ???? ??????????????????, ???????? ?????????????? ???? ??????????????
        if (Visibility.state() != 'visible')
            return true;

        // ???????????? ???? ??????????????????, ???????? ?????????? ???????? ?????????????????????????? ?????? ?????????????????? ????????????????????
        if ($('.modal-video').length > 0 || $('#news-table .info-row').length > 0)
            return true;

        /**
         * ???????????????????? ??????????????
         */
        $.ajax({
            url: $('#refreshButton').attr('href'),
            dataType: 'json'
        }).done(function(data) {
            var newData = data[ 'data' ];

            // ???????????? ?????????????????? ???????????????? ?????????? ????????????
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

            // ?????????????????????? channel ??????, ?????? ?????? ??????
            for (var i in newData)
                if (newData[ i ].channel == undefined)
                    newData[ i ].channel = {
                        id: '',
                        name: '',
                        image_url: ''
                    };

            // ????????????????
            // ???????? ???????????? ???? ???????????????????? - ???????????? ???? ????????????
            if (JSON.stringify(oldIdsCheck) != JSON.stringify(newIds)) {
                // ???????????????????? ?????????????? ?? ????????????????????
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

                // ????????????????
                // ???????????????? ???????????? ????????????????
                for (var i in oldIds) {
                    if (newIds.indexOf(oldIds[ i ]) == -1) {
                        if (!rows.eq(i).hasClass('info-row'))
                            rows.eq(i).addClass('must-hidden').animate({opacity: 0}, 400, 'swing', function() {
                                $(this).addClass('hidden');
                            });
                    }
                }

                // ?????????????????????? ???????? ?? ?????????????????? ??????????????
                var currentPosition = rows.first().position().top;

                for (var i in newIds) {
                    if (oldIds.indexOf(newIds[ i ]) == -1) {
                        // ?????????????????? ?????????? ??????????????
                        var newRow = $('<tr data-id="' + newData[ i ].id + '" class="warning" style="position: absolute; top: ' + currentPosition + 'px; left: 0; right: 0;" data-top="' + currentPosition + '" data-height="">' +
                            '<td style="width: ' + firstColWidth + 'px;">' +
                            '<div class="cell-table">' +
                            '<div class="cell-table-cell"><a class="channel-link" href="/channel/' + newData[ i ].channel.id + '" title="' + newData[ i ].channel.name + '"' + (newData[ i ].channel.image_url ? ' style="background-image: url(\'' + newData[ i ].channel.image_url + '\')"' : '') + '></a></div>' +
                            '<div class="cell-table-cell"><a ' + (newData[ i ].ad != undefined && newData[ i ].ad ? 'class="ad" ' : '') + 'href="#" data-video-id="' + newData[ i ].video_link + '" data-image="' + newData[ i ].image_url + '">' + newData[ i ].name + '</a></div>' +
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
                        newRow.attr('data-no-animate', 1);
                        currentPosition += newRow.outerHeight();
                    } else {
                        // ???????????????? ?????????????? ????????????????
                        var element = $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ]));

                        // ???????? ?????????? ?????????????????? - ???????????????????????? ??????
                        // if (parseInt(element.attr('data-top')) > currentPosition)
                        if (oldIds.indexOf(newIds[ i ]) > i) {
                            element.attr('data-no-animate', 1);

                            element.find('td:eq(0)').each(function() {
                                animateCellPositionName($(this));
                            });

                            element.find('td:not(:eq(0))').each(function() {
                                animateCellPosition($(this));
                            });
                        }

                        if (parseInt(element.attr('data-top')) != currentPosition) {
                            //console.log('?????????????? ' + oldIds.indexOf(newIds[ i ]), element.attr('data-top') + 'px => ' + currentPosition + 'px');
                            element.attr('data-top', currentPosition).animate({top: currentPosition});
                            currentPosition += parseInt(element.attr('data-height'));

                            // ???????? ???????????? ?????????????? ???????????????????????????? ?????? - ?????????????????????? ?????? ???????????? ???? ??????????????????
                            var infoElement = $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ]) + 1);
                            if (infoElement.hasClass('info-row')) {
                                //console.log('????????-?????????????? ' + (oldIds.indexOf(newIds[ i ]) + 1), currentPosition + 'px');

                                infoElement.attr('data-top', currentPosition).animate({top: currentPosition});
                                currentPosition += parseInt(infoElement.attr('data-height'));
                            }
                        } else {
                            currentPosition += parseInt(element.attr('data-height'));
                        }
                    }

                    //console.log('?????????? ?????????????? ' + currentPosition + 'px');
                }

                // ?????????????????? data-special
                $('#news-table').find('tbody tr').each(function() {
                    var dataItem = newData[ newIds.indexOf(parseInt($(this).attr('data-id'))) ];

                    if (dataItem != undefined && dataItem.special != undefined && dataItem.special == 1) {
                        if ($(this).attr('data-special') == undefined)
                            $(this).attr('data-special', 1);
                    } else {
                        if ($(this).attr('data-special') == 1)
                            $(this).removeAttr('data-special');
                    }
                });

                // ?????????????????????? ???????????????? ???? ?????????? ?? ???????????????? 2px, ??.??. ?? ?????????????????? ?????????? ???????? ???????????? ????????????
                var currentPosition = $('#news-table thead tr').outerHeight() - 2;
                if (currentPosition > 100)
                    currentPosition = 32;
                var rowsSorted = $('#news-table tbody tr').not('.must-hidden').sort(function(a, b) {
                    return parseInt($(a).attr('data-top')) - parseInt($(b).attr('data-top'));
                });

                rowsSorted.each(function() {
                    $(this).animate({'top': currentPosition + 'px'});
                    currentPosition += $(this).outerHeight() - 2;
                });
                $('#news-table').css('height', (currentPosition + 2) + 'px');

                // ?????????????????? ????????????
                setTimeout(function() {
                    // ?????????????? ?????????????????? ????????????????
                    $('#news-table').find('tbody tr.hidden').remove();

                    rows = $('#news-table').find('tbody tr').filter('[data-id]');
                    oldIds = $.makeArray(rows.map(function() {
                        return parseInt($(this).attr('data-id'));
                    }));

                    rows.removeAttr('data-top');
                    rows.removeAttr('data-height');
                    rows.removeAttr('data-no-animate');

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
                }, 2000);

                // ?????????????? ?????????? ??????????, ?????????? ???? ???????????? ????????????????
                setTimeout(function() {
                    $('#news-table').find('tbody tr').removeAttr('style').removeClass('warning');
                    $('#news-table').find('tbody tr td').removeAttr('style');
                    $('#news-table').removeAttr('style');
                }, 5000);

                // ???????????????? ?????????? ?? ?????????????????? ????????
                updateYoutubeLinks();
            }

            // ?????????????????????????? ?????????? ???????????????? ???????????????????? ?????? ???????????????????????? ??????????????????
            var animationInterval = 80;
            for (var i in newData) {
                var row = rows.filter('[data-id="' + newData[ i ].id + '"]');

                // ???????? ?????? ???????????? ?????????????? - ???? ?????? ??????????????????????
                if (row.attr('data-no-animate') == 1) {
                    row.find('td:eq(1)').text((newData[ i ].views_diff == 0 || newData[ i ].views_diff == undefined) ? '' : '+' + newData[ i ].views_diff);
                    row.find('td:eq(2)').text((newData[ i ].likes_diff == 0 || newData[ i ].likes_diff == undefined) ? '' : '+' + newData[ i ].likes_diff);
                    row.find('td:eq(3)').text((newData[ i ].dislikes_diff == 0 || newData[ i ].dislikes_diff == undefined) ? '' : '+' + newData[ i ].dislikes_diff);
                    row.find('td:eq(4)').text((newData[ i ].views == 0 || newData[ i ].views == undefined) ? '' : newData[ i ].views);
                    row.find('td:eq(5)').text((newData[ i ].position_diff == 0 || newData[ i ].position_diff == undefined) ? '' : (newData[ i ].position_diff > 0 ? '+' + newData[ i ].position_diff : newData[ i ].position_diff));
                } else {
                    setTimeout(animateCell.bind(null, row.find('td:eq(1)'), (newData[ i ].views_diff == 0 || newData[ i ].views_diff == undefined) ? '' : '+' + newData[ i ].views_diff), (parseInt(i) + 1) * animationInterval);
                    setTimeout(animateCell.bind(null, row.find('td:eq(2)'), (newData[ i ].likes_diff == 0 || newData[ i ].likes_diff == undefined) ? '' : '+' + newData[ i ].likes_diff), (parseInt(i) + 2) * animationInterval);
                    setTimeout(animateCell.bind(null, row.find('td:eq(3)'), (newData[ i ].dislikes_diff == 0 || newData[ i ].dislikes_diff == undefined) ? '' : '+' + newData[ i ].dislikes_diff), (parseInt(i) + 3) * animationInterval);
                    setTimeout(animateCell.bind(null, row.find('td:eq(4)'), (newData[ i ].views == 0 || newData[ i ].views == undefined) ? '' : newData[ i ].views), (parseInt(i) + 4) * animationInterval);
                    setTimeout(animateCell.bind(null, row.find('td:eq(5)'), (newData[ i ].position_diff == 0 || newData[ i ].position_diff == undefined) ? '' : (newData[ i ].position_diff > 0 ? '+' + newData[ i ].position_diff : newData[ i ].position_diff)), (parseInt(i) + 5) * animationInterval);
                }
            }

            // ???????????????????? ?????????????? ?? ??????????
            var newStreaming = data[ 'streaming' ];
            newStreaming = newStreaming.slice(0, parseInt($('.widget-streaming .video-list').attr('data-count')));

            var rowsStreaming = $('.widget-streaming').find('.video-item');
            var oldStreamingIds = $.makeArray(rowsStreaming.map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            var newStreamingIds = newStreaming.map(function(item) {
                return parseInt(item.id);
            });

            // ????????????????
            // ???????? ???????????? ???? ???????????????????? - ???????????? ???? ????????????
            if (JSON.stringify(oldStreamingIds) != JSON.stringify(newStreamingIds)) {
                if (newStreamingIds.length == 0)
                    $('.widget-streaming').addClass('hidden');
                else
                    $('.widget-streaming').removeClass('hidden');

                // ?????????????????? ????????????
                $('.widget-streaming .video-list').css({
                    height: $('.widget-streaming .video-list').height() + 'px'
                });

                // ???????????????? ?????? ????????????????
                rowsStreaming.animate({opacity: 0}, 400, 'swing');

                setTimeout(function() {
                    // ?????????????? ??????????????????
                    for (var i in oldStreamingIds) {
                        if (newStreamingIds.indexOf(oldStreamingIds[ i ]) == -1) {
                            rowsStreaming.eq(i).remove();
                        }
                    }

                    rowsStreaming = $('.widget-streaming').find('.video-item');
                    oldStreamingIds = $.makeArray(rowsStreaming.map(function() {
                        return parseInt($(this).attr('data-id'));
                    }));

                    for (var i in newStreamingIds) {
                        if (oldStreamingIds.indexOf(newStreamingIds[ i ]) == -1) {
                            // ?????????????????? ?????????? ??????????????
                            var newRow = $('<div class="video-item" data-id="' + newStreaming[ i ].id + '">\n' +
                                '<a href="/channel/' + newStreaming[ i ].channel.id + '" class="channel-name">' + newStreaming[ i ].channel.name + '</a>\n' +
                                '<a href="#" data-video-id="' + newStreaming[ i ].video_link + '" class="link">\n' +
                                '<div class="image" style="background-image: url(' + newStreaming[ i ].image_url + ')"></div>\n' +
                                '<div class="name">' + newStreaming[ i ].name + '</div>\n' +
                                '</a>\n' +
                                '</div>');

                            if (i == 0)
                                newRow.prependTo($('.widget-streaming .video-list'));
                            else
                                newRow.insertAfter($('.widget-streaming .video-list .video-item').eq(i - 1));

                            rowsStreaming = $('.widget-streaming').find('.video-item');
                        } else {
                            // ???????????????? ?????????????? ????????????????
                            if (oldStreamingIds.indexOf(newStreamingIds[ i ]) != i) {
                                if (i == 0)
                                    rowsStreaming.eq(oldStreamingIds.indexOf(newStreamingIds[ i ])).prependTo($('.widget-streaming .video-list'));
                                else
                                    rowsStreaming.eq(oldStreamingIds.indexOf(newStreamingIds[ i ])).insertAfter($('.widget-streaming .video-list .video-item').eq(i - 1));

                                rowsStreaming = $('.widget-streaming').find('.video-item');
                                oldStreamingIds = $.makeArray(rowsStreaming.map(function() {
                                    return parseInt($(this).attr('data-id'));
                                }));
                            }
                        }
                    }

                    // ???????????? ???????????????? ????????????
                    var height = 0;
                    rowsStreaming.each(function() {
                        height += $(this).outerHeight() + 5;
                    });

                    $('.widget-streaming .video-list').animate({
                        height: height + 'px'
                    }, 400, function() {
                        $(this).removeAttr('style');
                    });

                    // ???????????????????? ?????? ????????????????
                    rowsStreaming.animate({opacity: 1}, 400, 'swing', function() {
                        $(this).removeAttr('style');
                    });

                    // ???????????????? ?????????? ?? ?????????????????? ????????
                    updateYoutubeLinks();
                }, 500);
            }
        });
    }

    /**
     * ?????????????????? ????????????????????
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
        size: 45,
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
                value: $('#refresh-progress').circleProgress('progressValue'), // circleProgress ??????????????????????????
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