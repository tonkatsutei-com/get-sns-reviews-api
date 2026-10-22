<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="getsnsrev-section" id="getsnsrev-section-<?php echo $id; ?>">
    <header class="getsnsrev-section__header">
        <h3 class="getsnsrev-section__title"><?php echo $label; ?></h3>
        <p class="getsnsrev-section__description"><?php echo $description; ?></p>
        <p class="getsnsrev-section__meta">
            <span class="getsnsrev-section__quota"><?php echo $free_quota_label; ?>: <?php echo $free_quota_value; ?></span>
            <a class="getsnsrev-section__docs" href="<?php echo $docs_url; ?>" target="_blank" rel="noopener noreferrer"><?php echo $docs_label; ?></a>
        </p>
    </header>
    <form class="getsnsrev-section__form" method="post" action="<?php echo $form_action; ?>">
        <input type="hidden" name="action" value="<?php echo $save_action; ?>" />
        <input type="hidden" name="service" value="<?php echo $id; ?>" />
        <input type="hidden" name="<?php echo $nonce_name; ?>" value="<?php echo $nonce_value; ?>" />
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="getsnsrev-api-key-<?php echo $id; ?>"><?php echo $api_key_label; ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text getsnsrev-api-key" id="getsnsrev-api-key-<?php echo $id; ?>"
                           name="api_key" value="<?php echo $api_key_value; ?>"
                           data-masked="<?php echo $api_key_is_masked ? '1' : '0'; ?>"
                           autocomplete="off" spellcheck="false" />
                    <p class="description"><?php echo $api_key_help; ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="getsnsrev-section__actions">
            <button type="submit" class="button button-primary"><?php echo $save_button_label; ?></button>
        </p>
    </form>
    <footer class="getsnsrev-section__results">
        <p class="getsnsrev-result">
            <span class="getsnsrev-result__label"><?php echo $status_label; ?></span>
            <span class="getsnsrev-result__value getsnsrev-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            <span class="getsnsrev-result__at"><?php echo $tested_at_label; ?>: <?php echo $tested_at_value; ?></span>
        </p>
        <?php if ($message !== '') : ?>
            <p class="getsnsrev-result__message"><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if (!empty($approval_note['show'])) : ?>
            <p class="getsnsrev-result__approval">
                <?php echo $approval_note['text']; ?>
                <a href="<?php echo $approval_note['url']; ?>" target="_blank" rel="noopener noreferrer"><?php echo $approval_note['url_label']; ?></a>
            </p>
        <?php endif; ?>
        <p class="getsnsrev-result">
            <span class="getsnsrev-result__label"><?php echo $quota_label; ?></span>
            <span class="getsnsrev-result__value getsnsrev-quota <?php echo $quota_class; ?>"><?php echo $quota_text; ?></span>
        </p>
        <?php if ($show_quota_reset) : ?>
            <form method="post" action="<?php echo $form_action; ?>">
                <input type="hidden" name="action" value="<?php echo $reset_quota_action; ?>" />
                <input type="hidden" name="service" value="<?php echo $id; ?>" />
                <input type="hidden" name="<?php echo $nonce_name; ?>" value="<?php echo $nonce_value; ?>" />
                <button type="submit" class="button"><?php echo $quota_button_label; ?></button>
            </form>
        <?php endif; ?>
    </footer>
</section>
