<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/assets/diana-MWv7s2gH.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $name }}</title>

    <style>
        .benchmark {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 11px;
            color: gray;
        }
    </style>
</head>

<body>
    <div class="benchmark">
        Elapsed time: {{ (hrtime(true) - DIANA_START) / 1_000_000_000 }} seconds
    </div>
    <div id="root"></div>

    @vite("main.jsx")
</body>

</html>