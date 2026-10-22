<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="getsnsrev-section getsnsrev-import" id="getsnsrev-section-<?php echo $id; ?>">
    <header class="getsnsrev-section__header">
        <h3 class="getsnsrev-section__title"><?php echo $heading; ?></h3>
        <p class="getsnsrev-section__description"><?php echo $description; ?></p>
        <p class="getsnsrev-section__meta getsnsrev-import__status <?php echo $available ? 'is-ok' : 'is-error'; ?>"><?php echo $status; ?></p>
    </header>
    <form class="getsnsrev-import__form" method="post" action="<?php echo $form_action; ?>">
        <input type="hidden" name="action" value="<?php echo $import_action; ?>" />
        <input type="hidden" name="<?php echo $nonce_name; ?>" value="<?php echo $nonce_value; ?>" />
        <p class="getsnsrev-section__actions">
            <button type="submit" class="button button-secondary"<?php echo $available ? '' : ' disabled="disabled"'; ?>><?php echo $button_label; ?></button>
        </p>
    </form>
</section>
