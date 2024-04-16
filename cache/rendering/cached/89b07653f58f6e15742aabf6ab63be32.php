<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/assets/diana-MWv7s2gH.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo \Diana\Support\Helpers\Emit::e($name); ?></title>

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
        Elapsed time: <?php echo \Diana\Support\Helpers\Emit::e((hrtime(true) - DIANA_START) / 1_000_000_000); ?> seconds
    </div>
    <div id="root"></div>

    <script type="module" src="assets/main-C9HzgnHW.js"></script>
<link rel="modulepreload" href="/assets/vendor-B0AyrCyX.js">
<link rel="stylesheet" href="/assets/main-CeN8urqP.css">
</body>

</html><?php /**PATH /repo/dianaphp/project/res/app.blade.php ENDPATH**/ ?>