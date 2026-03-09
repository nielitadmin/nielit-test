<div class="w-full sm:w-80 relative bg-white border rounded-lg shadow-sm divide-y divide-gray-200 <?php echo e($plan['popular'] ? 'border-secondary' : 'border-gray-200'); ?>">
    <div class="p-6">
        <?php if($plan['popular']): ?>
            <p class="absolute top-0 ltr:right-6 rtl:left-6 py-1.5 px-4 bg-secondary rounded-full text-sm font-semibold uppercase tracking-wide text-white transform -translate-y-1/2"><?php echo e(__('Most Popular')); ?></p>
        <?php endif; ?>
        <div class="flex gap-2 items-center justify-start">
            <h2 class="text-lg leading-6 font-semibold text-primary"><?php echo e($plan['name']); ?></h2>
            <?php if($plan['has_discount']): ?>
                <div class="flex items-center justify-center bg-green-100 rounded">
                    <p class="font-mono text-sm leading-loose text-center text-green-700 px-2 uppercase"><?php echo e(__('Save')); ?> <?php echo e($plan['discount_percentage']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <p class="mt-4 text-gray-500"><?php echo e($plan['description']); ?></p>
        <p class="mt-6">
            <?php if($plan['has_discount']): ?>
                <span class="text-3xl font-bold text-gray-800"><span class="line-through"><?php echo e($plan['price']); ?></span> <?php echo e($plan['discounted_price']); ?></span>
            <?php else: ?>
                <span class="text-3xl font-bold text-gray-800"><?php echo e($plan['price']); ?></span>
            <?php endif; ?>
            <span class="text-base font-medium text-secondary">/<?php echo e(__('month')); ?></span>
        </p>
            <p class="mt-4 max-w-2xl text-gray-500 lg:mx-auto">
                <?php echo e(__('To be paid')); ?> <?php echo e($plan['total_price']); ?> <?php echo e(__('for')); ?> <?php echo e($plan['duration']); ?> <?php echo e(__('months')); ?>

            </p>
        <a href="<?php echo e(route('checkout', ['plan' => $plan['code']])); ?>" class="mt-4 block w-full bg-gray-800 border bg-primary rounded-md py-2 font-semibold text-white text-center hover:opacity-90">
            <?php echo e(__('Buy Plan')); ?>

        </a>
    </div>
    <div class="pt-6 pb-8 px-6">
        <h3 class="font-medium text-gray-900 tracking-wide"><?php echo e($plan['duration']); ?> <?php echo e(__('Months')); ?>  <?php echo e(__('Unlimited Access To')); ?></h3>
        <ul role="list" class="mt-6 space-y-4">
            <?php $__currentLoopData = $plan['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="flex space-x-3">
                    <?php if($feature['included']): ?>
                        <svg class="flex-shrink-0 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    <?php else: ?>
                        <svg class="flex-shrink-0 h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <?php endif; ?>
                    <span class="rtl:pr-2 text-gray-500"><?php echo e(__($feature['name'])); ?></span>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
</div>
<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/components/plan.blade.php ENDPATH**/ ?>