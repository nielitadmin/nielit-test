<?php $__env->startSection('title', __('Checkout')); ?>

<?php $__env->startSection('content'); ?>
    <main class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-extrabold py-8"><?php echo e(__('Checkout')); ?></h1>
            <div class="flex flex-col gap-4 mb-4 -mt-4">
                <?php echo $__env->make('components.alert-success', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php echo $__env->make('components.alert-danger', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
            <div class="max-w-2xl pb-24 mx-auto lg:max-w-none" x-data="{ payment: '<?php echo e(app(\App\Settings\PaymentSettings::class)->default_payment_processor); ?>' }">
                <form method="POST" action="<?php echo e(route('process_checkout', ['plan' => $plan])); ?>" class="lg:grid lg:grid-cols-2 lg:gap-x-12">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="payment_id" value="<?php echo e($payment_id); ?>">
                    <div>
                        <!-- Payment Method -->
                        <div class="rounded bg-white shadow-md border pb-4 border-gray-100">
                            <div class="p-4 lg:p-6">
                                <h2 class="text-lg border-b border-gray-200 pb-2 font-semibold text-primary"><?php echo e(__('Payment Method')); ?></h2>
                                <div class="mt-4 space-y-4 flex flex-col">
                                    <?php $__currentLoopData = $paymentProcessors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $processor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center cursor-pointer">
                                                <input x-model="payment" id="processor_<?php echo e($processor['code']); ?>" name="payment_method" type="radio" value="<?php echo e($processor['code']); ?>" <?php echo e($processor['default'] ? 'checked' : ''); ?> class="cursor-pointer focus:ring-current h-4 w-4 text-primary border-gray-300">
                                                <label for="<?php echo e($processor['code']); ?>" class="ml-3 flex items-center block font-medium text-gray-700 cursor-pointer">
                                                    <img class="h-8 rounded border border-gray-200" src="<?php echo e(url('/')); ?>/images/<?php echo e($processor['code']); ?>.svg" alt="<?php echo e($processor['name']); ?>">
                                                    <span class="ml-2 font-semibold"><?php echo e($processor['name']); ?></span>
                                                </label>
                                            </div>
                                            <div class="hidden sm:block text-sm text-gray-500"><?php echo e($processor['description']); ?></div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Information -->
                        <div class="rounded bg-white shadow-md border border-gray-100 mt-10 pb-4">
                            <div class="p-4 lg:p-6">
                                <h2 class="text-lg border-b border-gray-200 pb-2 font-semibold text-primary"><?php echo e(__('Billing Information')); ?></h2>
                                <!-- Full name -->
                                <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-4">
                                    <div class="sm:col-span-2">
                                        <label for="full_name" class="block font-medium text-gray-700"><?php echo e(__('Full name')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="full_name" id="full_name" value="<?php echo e(old('full_name') ?? ($billing_information['full_name'] ?? null)); ?>" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('full_name') ? ' border-red-300' : 'border-gray-300'); ?>" >
                                        </div>
                                        <?php if($errors->has('full_name')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('full_name')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block font-medium text-gray-700"><?php echo e(__('Email')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="email" id="email" value="<?php echo e(old('email') ?? ($billing_information['email'] ?? null)); ?>" autocomplete="email" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('email') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('email')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('email')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block font-medium text-gray-700"><?php echo e(__('Phone')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="phone" id="phone" value="<?php echo e(old('phone') ?? ($billing_information['phone'] ?? null)); ?>" autocomplete="tel" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('phone') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('phone')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('phone')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Address -->
                                    <div class="sm:col-span-2">
                                        <label for="address" class="block font-medium text-gray-700"><?php echo e(__('Address')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="address" id="address" value="<?php echo e(old('address') ?? ($billing_information['address'] ?? null)); ?>" autocomplete="street-address" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('address') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('address')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('address')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- City -->
                                    <div>
                                        <label for="city" class="block font-medium text-gray-700"><?php echo e(__('City')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="city" id="city" value="<?php echo e(old('city') ?? ($billing_information['city'] ?? null)); ?>" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('city') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('city')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('city')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- State -->
                                    <div>
                                        <label for="state" class="block font-medium text-gray-700"><?php echo e(__('State')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="state" id="state" value="<?php echo e(old('state') ?? ($billing_information['state'] ?? null)); ?>" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('state') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('state')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('state')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Country -->
                                    <div>
                                        <label for="country" class="block font-medium text-gray-700"><?php echo e(__('Country')); ?></label>
                                        <div class="mt-1">
                                            <select id="country" name="country" value="<?php echo e(old('country') ?? ($billing_information['country'] ?? null)); ?>" autocomplete="country" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('country') ? ' border-red-300' : 'border-gray-300'); ?>">
                                                <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($country['code']); ?>" <?php echo e($country['code'] == 'US' ? 'selected' : ''); ?>><?php echo e($country['name']); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <?php if($errors->has('country')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('country')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Zip Code -->
                                    <div>
                                        <label for="zip" class="block font-medium text-gray-700"><?php echo e(__('Postal code')); ?></label>
                                        <div class="mt-1">
                                            <input type="text" name="zip" id="zip" value="<?php echo e(old('zip') ?? ($billing_information['zip'] ?? null)); ?>" autocomplete="zip" class="block w-full rounded-sm shadow-sm focus:ring-current focus:border-primary <?php echo e($errors->has('zip') ? ' border-red-300' : 'border-gray-300'); ?>">
                                        </div>
                                        <?php if($errors->has('zip')): ?>
                                            <div class="text-xs mt-1 text-red-500" role="alert"><span><?php echo e($errors->first('zip')); ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order summary -->
                    <div class="rounded bg-white shadow-md border border-gray-100 mt-10 pb-4 lg:mt-0">
                        <div class="p-4 lg:p-6">
                            <?php echo $__env->make('store.checkout.partials._order_summary', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <div class="mt-4">
                                <template x-if="payment == 'bank'">
                                    <div class="flex flex-col">
                                        <?php echo $__env->make('components.bank_details', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        <div class="mt-4"><?php echo e(__('When transferring the bank payment, please must include the following Payment ID in the reference field of the payment.')); ?></div>
                                        <div class="mt-2 px-4 py-2 rounded-md bg-blue-50 border border-blue-200">
                                            <?php echo e($payment_id); ?>

                                        </div>
                                        <button type="submit" class="mt-6 w-full bg-primary border border-transparent rounded-sm shadow-sm py-2 px-4 text-base font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-current">
                                            <?php echo e(__('Submit Bank Payment')); ?>

                                        </button>
                                    </div>
                                </template>
                                <template x-if="payment != 'bank'">
                                    <div class="flex flex-col mt-6">
                                        <div class="text-sm font-italic"><?php echo e(__('Note: You can review your order on the next page before making the payment.')); ?></div>
                                        <button type="submit" class="mt-4 w-full bg-primary border border-transparent rounded-sm shadow-sm py-2 px-4 text-base font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-current">
                                            <?php echo e(__('Review & Checkout')); ?>

                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('store.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/store/checkout/index.blade.php ENDPATH**/ ?>