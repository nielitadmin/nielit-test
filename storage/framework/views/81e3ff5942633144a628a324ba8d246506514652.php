<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo e($exam['title']); ?> Exam Score Report</title>
        <style>
            body { width: 100%; color: #444; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif}
            table {width: 100%;border: 1px solid transparent}
            table {border-collapse: collapse}
            table, th {border: 1px solid transparent;vertical-align: middle;padding: 5px}
            td {border: 1px solid transparent;padding: 5px}
            footer {position: fixed;bottom: 0;left: 0;right: 0;height: 20px}
            .footer-note {padding-top: 10px; font-size: 12px}
            .logo-block {text-align: center; width: 30%}
            .heading-block {text-align: right; width: 70%}
            .logo {height: 35px}
            .heading {text-align: center; background: #dadada;font-weight: bold}
            .list-item {display: block;padding: 4px}
            .heading-item {display: block;padding: 4px;margin-top: 4px}
            .text-center {text-align: center}
            .uppercase {text-transform: uppercase}
            .signature-block {text-align: center; vertical-align: bottom}
            .green {color: green}
            .red {color: red}
            .w-25 {width: 25%}
            .w-50 {width: 50%}
            <?php if($rtl): ?>
            body {text-align: right;}
            .list-item {text-align: right;}
            .heading-item {text-align: right;}
            .footer-note {text-align: right;}
            .result-block {text-align: right;}
            <?php endif; ?>
        </style>
    </head>
    <body>
    <div class="report">
        <table>
            <thead>
            <tr>
                <th colspan="1" class="logo-block">
                    <img class="logo" src="<?php echo e($logo); ?>" alt="Logo" />
                </th>
                <th colspan="3" class="heading-block">
                    <h2>Exam Score Report</h2>
                    <h3><?php echo e($exam['title']); ?></h3>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="2" class="heading w-50">
                    Test Details
                </td>
                <td colspan="2" class="heading w-50">
                    User Details
                </td>
            </tr>
            <tr>
                <td colspan="2" class="w-50">
                    <span class="heading-item"><strong>Exam Name:</strong></span>
                    <span class="list-item"><?php echo e($exam['title']); ?></span>
                    <span class="heading-item"><strong>Completed on:</strong></span>
                    <span class="list-item"><?php echo e($session['completed_at']); ?></span>
                    <span class="heading-item"><strong>Session ID:</strong></span>
                    <span class="list-item"><?php echo e($session['id']); ?></span>
                    <br>
                </td>
                <td colspan="2" class="w-50">
                    <span class="heading-item"><strong>Test Taker:</strong></span>
                    <span class="list-item"><?php echo e($session['user']); ?></span>
                    <span class="heading-item"><strong>E-Mail:</strong></span>
                    <span class="list-item"><?php echo e($session['email']); ?></span>
                    <span class="heading-item"><strong>IP & Device:</strong></span>
                    <span class="list-item"><?php echo e($session['ip']); ?>, <?php echo e($session['device']); ?>, <?php echo e($session['os']); ?>, <?php echo e($session['browser']); ?></span>
                    <br>
                </td>
            </tr>
            <tr>
                <td colspan="4" class="heading">
                    Test Results
                </td>
            </tr>
            <?php $__currentLoopData = $session['properties']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $properties): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($rtl): ?>
                            <td class="w-25">
                                <span class="list-item"><?php echo e($property['value']); ?></span>
                            </td>
                            <td class="w-25">
                                <span class="list-item"><strong><?php echo e($property['key']); ?></strong></span>
                            </td>
                        <?php else: ?>
                            <td class="w-25">
                                <span class="list-item"><strong><?php echo e($property['key']); ?></strong></span>
                            </td>
                            <td class="w-25">
                                <span class="list-item"><?php echo e($property['value']); ?></span>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <?php if($rtl): ?>
                    <td colspan="2" class="signature-block w-50">Authorized by</td>
                    <td colspan="2"  class="w-50 result-block">
                        <br>
                        <h2 class="uppercase <?php echo e($session['status'] == 'Passed' ? 'green' : 'red'); ?>"><?php echo e($session['status']); ?></h2>
                        <br>
                        Final Result
                    </td>
                <?php else: ?>
                    <td colspan="2" class="w-50 result-block">
                        <br>
                        <h2 class="uppercase <?php echo e($session['status'] == 'Passed' ? 'green' : 'red'); ?>"><?php echo e($session['status']); ?></h2>
                        <br>
                        Final Result
                    </td>
                    <td colspan="2" class="signature-block w-50">Authorized by</td>
                <?php endif; ?>
            </tr>
            </tbody>
        </table>
    </div>
    <footer>
        <p class="footer-note"><?php echo e($footer); ?></p>
    </footer>
    </body>
</html>

<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/pdf/exam-session-report.blade.php ENDPATH**/ ?>