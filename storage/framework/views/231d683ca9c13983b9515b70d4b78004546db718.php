<?php $__env->startSection('title', __('Pricing')); ?>

<?php $__env->startSection('content'); ?>
    <div x-data="{category: '<?php echo e($selectedCategory); ?>'}">
        <section id="categories" class="hero-gradient dark:bg-gray-900 border-b border-gray-100 lg:px-4 xl:px-0">
            <div class="max-w-2xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8 lg:max-w-7xl">
                <div class="px-0 sm:px-4 lg:px-0 lg:flex lg:justify-between lg:items-center">
                    <div class="max-w-xl">
                        <h2 class="text-5xl sm:text-5xl md:text-4xl lg:text-5xl font-bold leading-tight text-primary dark:text-white"><?php echo e(__('Pricing Plans')); ?></h2>
                        <p class="mt-5 text-lg text-gray-600"><?php echo e(app(\App\Settings\CategorySettings::class)->subtitle); ?></p>
                    </div>
                    <div class="mt-10 w-full max-w-xs lg:mt-0">
                        <label for="category" class="block text-base font-medium text-primary"><?php echo e(__('Category')); ?></label>
                        <div class="mt-1.5 relative">
                            <select id="category" name="category" x-model="category" class="block w-full bg-none bg-primary bg-opacity-25 border border-transparent text-white focus:ring-white focus:border-white rounded-md">
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option class="bg-primary" value="<?php echo e($category['code']); ?>">
                                        <?php echo e($category['category']); ?> <?php echo e($category['name']); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 px-2 flex items-center">
                                <svg class="h-4 w-4 text-indigo-300" x-description="Heroicon name: solid/chevron-down" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div x-bind:class="category !== '<?php echo e($category['code']); ?>' ? 'hidden' : ''" class="max-w-7xl mx-auto py-12 px-4 sm:py-28 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-primary sm:text-4xl">
                        <?php echo e($category['name']); ?> <?php echo e(__('Subscription Plans')); ?>

                    </p>
                    <p class="mt-4 max-w-2xl text-lg text-gray-500 lg:mx-auto">
                        <?php echo e(__('To be paid as a one-time payment.')); ?>

                    </p>
                </div>
                <div class="mt-16 flex flex-wrap items-center justify-center gap-6">
                    <?php $__currentLoopData = $category['plans']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('styles'); ?>
    <style>
        .hero-gradient {
            background-image: linear-gradient(rgba(255, 255, 255, 0) 0%, <?php echo e(hex2rgba('#'.app(\App\Settings\ThemeSettings::class)->primary_color, 0.03)); ?> 100%);
            background-repeat: no-repeat;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('store.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/store/pricing.blade.php ENDPATH**/ ?>