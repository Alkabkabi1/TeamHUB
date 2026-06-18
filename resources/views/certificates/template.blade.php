<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .canvas {
            position: relative;
            width: {{ $width }}px;
            height: {{ $height }}px;
            overflow: hidden;
        }

        .canvas .bg {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $width }}px;
            height: {{ $height }}px;
        }

        .layer {
            position: absolute;
        }
    </style>
</head>
<body>
    <div class="canvas">
        @if ($backgroundImage)
            <img class="bg" src="{{ $backgroundImage }}" alt="">
        @endif

        @foreach ($layers as $layer)
            <div class="layer" style="
                left: {{ $layer['left'] }}px;
                top: {{ $layer['top'] }}px;
                width: {{ $layer['width'] }}px;
                text-align: {{ $layer['align'] }};
            ">
                @if ($layer['isImage'])
                    @if ($layer['content'])
                        <img src="{{ $layer['content'] }}" style="width: {{ $layer['width'] }}px;" alt="">
                    @endif
                @else
                    <span style="
                        font-family: '{{ $layer['fontFamily'] }}', 'DejaVu Sans', sans-serif;
                        font-size: {{ $layer['fontSize'] }}px;
                        font-weight: {{ $layer['fontWeight'] }};
                        color: {{ $layer['color'] }};
                        line-height: 1.2;
                    ">{{ $layer['content'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
