<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap getsnsrev-settings">
    <h1><?php echo $page_title; ?></h1>
    <p class="getsnsrev-intro"><?php echo $intro; ?></p>
    <?php if (!empty($notice['show'])) : ?>
        <div class="notice notice-<?php echo $notice['type']; ?> is-dismissible">
            <p><?php echo $notice['message']; ?></p>
        </div>
    <?php endif; ?>
    <?php ?>
    <?php if (!empty($reviews_test)) : ?>
        <?php echo $renderer->render('partial-reviews-test', $reviews_test); ?>
    <?php endif; ?>
    <?php if (!empty($import)) : ?>
        <?php echo $renderer->render('partial-import-section', $import); ?>
    <?php endif; ?>
    <h2 class="getsnsrev-sections-heading"><?php echo $services_heading; ?></h2>
    <?php foreach ($sections as $section) : ?>
        <?php echo $renderer->render('partial-service-section', $section); ?>
    <?php endforeach; ?>
</div>
