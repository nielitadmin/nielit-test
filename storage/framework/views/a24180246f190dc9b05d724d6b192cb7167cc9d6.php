<section id="pricing" class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:py-28 sm:px-6 lg:px-8">
        <div class="lg:text-center">
            <h2 class="text-base text-secondary font-semibold tracking-wide uppercase"><?php echo e(__('Pricing')); ?></h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-primary sm:text-4xl">
                <?php echo e($category['name']); ?> <?php echo e(__('Subscription Plans')); ?>

            </p>
            <p class="mt-4 max-w-2xl text-lg text-gray-500 lg:mx-auto">
                <?php echo e(__('To be paid as a one-time payment.')); ?>

            </p>
        </div>
        <div class="mt-16 flex flex-wrap items-center justify-center gap-6">
            <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginal8ac4ab7405be44d184feb11078f9ffa7464e15f6 = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\Plan::class, ['plan' => $plan]); ?>
<?php $component->withName('plan'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
<?php if (isset($__componentOriginal8ac4ab7405be44d184feb11078f9ffa7464e15f6)): ?>
<?php $component = $__componentOriginal8ac4ab7405be44d184feb11078f9ffa7464e15f6; ?>
<?php unset($__componentOriginal8ac4ab7405be44d184feb11078f9ffa7464e15f6); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/components/pricing.blade.php ENDPATH**/ ?>