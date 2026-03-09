<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title>File Manager - <?php echo e(config('app.name', 'QwikTest')); ?> Admin</title>

        <!-- Styles -->
        <link rel="stylesheet" href="<?php echo e(asset('vendor/font-awesome/all.css')); ?>">
        <link rel="stylesheet" href="<?php echo e(asset('vendor/bootstrap/bootstrap.min.css')); ?>">
        <link rel="stylesheet" href="<?php echo e(asset('vendor/file-manager/css/file-manager.css')); ?>">
    </head>
    <body class="font-sans antialiased">
        <div class="vh-100">
            <div id="fm"></div>
        </div>
        <!-- Scripts -->
        <script src="<?php echo e(asset('vendor/file-manager/js/file-manager.js')); ?>"></script>
    </body>
</html>
<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/file-manager.blade.php ENDPATH**/ ?>