<?php $__env->startSection('title', $category['headline'] ?: $category['name']); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginala0a7f931d42acf6b97053d812af7fdd9017aa5da = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\CategoryHero::class, ['category' => $category,'price' => $least_price]); ?>
<?php $component->withName('category-hero'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
<?php if (isset($__componentOriginala0a7f931d42acf6b97053d812af7fdd9017aa5da)): ?>
<?php $component = $__componentOriginala0a7f931d42acf6b97053d812af7fdd9017aa5da; ?>
<?php unset($__componentOriginala0a7f931d42acf6b97053d812af7fdd9017aa5da); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>

    <!--Features-->
    <?php if($homePageSettings->enable_features): ?>
        <?php if (isset($component)) { $__componentOriginal9896791f17a81069298d61b559ac9f1149c1ecfc = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\Features::class, []); ?>
<?php $component->withName('features'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
<?php if (isset($__componentOriginal9896791f17a81069298d61b559ac9f1149c1ecfc)): ?>
<?php $component = $__componentOriginal9896791f17a81069298d61b559ac9f1149c1ecfc; ?>
<?php unset($__componentOriginal9896791f17a81069298d61b559ac9f1149c1ecfc); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
    <?php endif; ?>

    <!--Pricing-->
    <?php echo $__env->make('components.pricing', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--Testimonials-->
    <?php if($homePageSettings->enable_testimonials): ?>
        <?php if (isset($component)) { $__componentOriginal99843580f067249f9ed6ac259729d2bf4b8605e4 = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\Testimonials::class, []); ?>
<?php $component->withName('testimonials'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
<?php if (isset($__componentOriginal99843580f067249f9ed6ac259729d2bf4b8605e4)): ?>
<?php $component = $__componentOriginal99843580f067249f9ed6ac259729d2bf4b8605e4; ?>
<?php unset($__componentOriginal99843580f067249f9ed6ac259729d2bf4b8605e4); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
    <?php endif; ?>

    <!--CTA-->
    <?php if($homePageSettings->enable_cta): ?>
        <?php if (isset($component)) { $__componentOriginald677f82e54e42f61b391c66e66511c5bb1c2da69 = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\Cta::class, []); ?>
<?php $component->withName('cta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
<?php if (isset($__componentOriginald677f82e54e42f61b391c66e66511c5bb1c2da69)): ?>
<?php $component = $__componentOriginald677f82e54e42f61b391c66e66511c5bb1c2da69; ?>
<?php unset($__componentOriginald677f82e54e42f61b391c66e66511c5bb1c2da69); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('store.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/resources/views/store/explore.blade.php ENDPATH**/ ?>