<h2 class="text-lg border-b border-gray-200 pb-2 font-semibold text-primary"><?php echo e(__('Order Summary')); ?></h2>
<div class="mt-4">
    <dl class="space-y-4">
        <?php $__currentLoopData = $order['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-start justify-between">
                <dt class="">
                    <div><?php echo e($item['name']); ?></div>
                    <?php if($item['discount']): ?>
                        <div class="text-secondary"><?php echo e($item['discount']); ?> <?php echo e(__('Discount Applied')); ?></div>
                    <?php endif; ?>
                </dt>
                <dd class=" font-medium text-gray-900">
                    <?php if($item['discount']): ?>
                        <span class="line-through mr-1"><?php echo e($item['original_price']); ?></span>
                    <?php endif; ?>
                    <span><?php echo e($item['amount_formatted']); ?></span>
                </dd>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = $order['taxes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tax): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between">
                <dt class="">
                    <?php echo e($tax['name']); ?> (<?php echo e(ucfirst($tax['type'])); ?>)
                </dt>
                <dd class=" font-medium text-gray-900">
                    <?php echo e($tax['amount_formatted']); ?>

                </dd>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-center justify-between border-t border-dotted border-gray-200 pt-4">
            <dt class="text-base font-semibold">
                <?php echo e(__('Total')); ?>

            </dt>
            <dd class="text-base font-semibold text-gray-900">
                <?php echo e($order['total_formatted']); ?>

            </dd>
        </div>
    </dl>
</div>
<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/store/checkout/partials/_order_summary.blade.php ENDPATH**/ ?>