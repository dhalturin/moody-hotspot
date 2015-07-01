<!DOCTYPE html>
<html>
    <head>
        <title>{%title%}</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <script type="text/javascript" src="/data/js/jquery.js"></script>
        <script type="text/javascript" src="/data/js/main.js?{%date%}"></script>
        <link type="text/css" rel="stylesheet" href="/data/css/main.css?{%date%}" />
    </head>
    <body>
        <script type="text/javascript">
            $(document).ready(function()
            {
                var bg_list = {%bg_list%};

                bg_list = bg_list[getRandomInt(0, bg_list.length - 1)];

                console.log(' .. set bg: ' + bg_list);

                $('.content').css({'background-image': 'url(/' + bg_list + ')'});
            });
        </script>

        <div class="content">
{%content%}
            <div class="footer">{%title%} 2014 by <a href="https://twitter.com/dhalturin" target="_blank">@dhalturin</a></div>
        </div>
    </body>
</html>
