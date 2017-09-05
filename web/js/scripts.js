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
        if ($('#search').hasClass('active')) {
            if ($.trim($('#search input').val()) != '') {
                $('#search form').submit();
            } else {
                $('#search').removeClass('active')
            }
        } else {
            $('#search').addClass('active')
        }

        $('#search').toggleClass('active');

        return false;
    });

    /**
     * Окно предпросмотра с возможностью репоста
     */
    $('#news-table').on('click', '.info', function(e) {
        e.preventDefault();

        var rowExists = false;
        if ($(this).closest('tr').hasClass('full'))
            rowExists = true;

        $('#news-table').find('.info-row').remove();
        $('#news-table').find('.full').removeClass('full');

        if (rowExists)
            return true;

        var link = $(this).closest('tr').find('.cell-table .cell-table-cell:eq(1) a');
        var channelLink = $(this).closest('tr').find('.cell-table .cell-table-cell:eq(0) a');

        $(this).closest('tr').addClass('full');
        var infoRow = $('<tr class="info-row">' +
            '<td colspan="6">\n' +
                '<div class="cell-content">\n' +
                    '<div class="image">\n' +
                        '<a href="' + link.attr('href') + ' target="_blank"">\n' +
                            '<img src="' + link.attr('data-image') + '">\n' +
                        '</a>\n' +
                    '</div>\n' +
                    '<div class="video-info">\n' +
                        '<div class="channel-info">\n' +
                            '<a href="' + channelLink.attr('href') + '">\n' +
                                '<div class="channel-image" style=\'' + channelLink.attr('style') + '\'></div>\n' +
                                '<div class="channel-name">' + channelLink.attr('title') + '</div>\n' +
                            '</a>\n' +
                        '</div>\n' +
                        '<div class="name">\n' +
                            '<a href="' + link.attr('href') + '" target="_blank">' + link.text() + '</a>\n' +
                        '</div>\n' +
                        '<div class="share42init" data-url="' + link.attr('href') + '" data-title="' + link.text() + '" data-image="' + link.attr('data-image') + '" data-description="Больше видео на ratetube.ru!"></div>\n' +
                    '</div>\n' +
                '</div>\n' +
            '</td>\n' +
        '</tr>').insertAfter($(this).closest('tr'));

        infoRow.find('div.share42init').each(function(idx){var el=$(this),u=el.attr('data-url'),t=el.attr('data-title'),i=el.attr('data-image'),d=el.attr('data-description'),f=el.attr('data-path'),fn=el.attr('data-icons-file'),z=el.attr("data-zero-counter");if(!u)u=location.href;if(!fn)fn='icons.png';if(!z)z=0;if(!f){function path(name){var sc=document.getElementsByTagName('script'),sr=new RegExp('^(.*/|)('+name+')([#?]|$)');for(var p=0,scL=sc.length;p<scL;p++){var m=String(sc[p].src).match(sr);if(m){if(m[1].match(/^((https?|file)\:\/{2,}|\w:[\/\\])/))return m[1];if(m[1].indexOf("/")==0)return m[1];b=document.getElementsByTagName('base');if(b[0]&&b[0].href)return b[0].href+m[1];else return document.location.pathname.match(/(.*[\/\\])/)[0]+m[1];}}return null;}f=path('share42.js');}if(!t)t=document.title;if(!d){var meta=$('meta[name="description"]').attr('content');if(meta!==undefined)d=meta;else d='';}u=encodeURIComponent(u);t=encodeURIComponent(t);t=t.replace(/\'/g,'%27');i=encodeURIComponent(i);d=encodeURIComponent(d);d=d.replace(/\'/g,'%27');var vkImage='';if(i!='null'&&i!='')vkImage='&image='+i;var s=new Array('"#" data-count="fb" onclick="window.open(\'//www.facebook.com/sharer/sharer.php?u='+u+'\', \'_blank\', \'scrollbars=0, resizable=1, menubar=0, left=100, top=100, width=550, height=440, toolbar=0, status=0\');return false" title="Поделиться в Facebook"','"#" data-count="mail" onclick="window.open(\'//connect.mail.ru/share?url='+u+'&title='+t+'&description='+d+'&imageurl='+i+'\', \'_blank\', \'scrollbars=0, resizable=1, menubar=0, left=100, top=100, width=550, height=440, toolbar=0, status=0\');return false" title="Поделиться в Моем Мире@Mail.Ru"','"#" data-count="odkl" onclick="window.open(\'//ok.ru/dk?st.cmd=addShare&st._surl='+u+'&title='+t+'\', \'_blank\', \'scrollbars=0, resizable=1, menubar=0, left=100, top=100, width=550, height=440, toolbar=0, status=0\');return false" title="Добавить в Одноклассники"','"#" data-count="twi" onclick="window.open(\'//twitter.com/intent/tweet?text='+t+'&url='+u+'\', \'_blank\', \'scrollbars=0, resizable=1, menubar=0, left=100, top=100, width=550, height=440, toolbar=0, status=0\');return false" title="Добавить в Twitter"','"#" data-count="vk" onclick="window.open(\'//vk.com/share.php?url='+u+'&title='+t+vkImage+'&description='+d+'\', \'_blank\', \'scrollbars=0, resizable=1, menubar=0, left=100, top=100, width=550, height=440, toolbar=0, status=0\');return false" title="Поделиться В Контакте"');var l='';for(j=0;j<s.length;j++)l+='<span class="share42-item" style="display:inline-block;margin:0 6px 6px 0;height:24px;"><a rel="nofollow" style="display:inline-block;width:24px;height:24px;margin:0;padding:0;outline:none;background:url('+f+fn+') -'+24*j+'px 0 no-repeat" href='+s[j]+' target="_blank"></a></span>';el.html('<span id="share42">'+l+'</span>'+'');})
    });
});