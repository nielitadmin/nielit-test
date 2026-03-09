<div class="mt-4 p-6 bg-gray-50 border border-gray-200 rounded-sm">
    <h4 class="font-semibold mb-2 underline"><?php echo e(__('Bank Account Details')); ?></h4>
    <div class="w-full flex gap-4 sm:justify-center items-center">
        <table class="w-full table-auto">
            <tbody>
            <?php if($bankSettings->bank_name != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('Bank Name')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->bank_name); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->account_owner != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('Account Owner')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->account_owner); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->account_number != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('Account Number')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->account_number); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->iban != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('IBAN')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->iban); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->routing_number != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('Routing Number')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->routing_number); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->bic_swift != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('BIC/Swift')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->bic_swift); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($bankSettings->other_details != '-'): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800 font-medium"><?php echo e(__('Other Details')); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-gray-800"><?php echo e($bankSettings->other_details); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/components/bank_details.blade.php ENDPATH**/ ?>