<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<details class="getsnsrev-section getsnsrev-reviews-test getsnsrev-accordion" id="getsnsrev-section-<?php echo $id; ?>"<?php if ($open) : ?> open<?php endif; ?>>
    <summary class="getsnsrev-section__header getsnsrev-accordion__summary">
        <h3 class="getsnsrev-section__title"><?php echo $heading; ?></h3>
    </summary>
    <p class="getsnsrev-section__description"><?php echo $description; ?></p>
    <div class="getsnsrev-reviews-test__rows">
        <?php foreach ($sns_rows as $row) : ?>
            <form class="getsnsrev-reviews-test__sns-row" method="post" action="<?php echo $form_action; ?>">
                <input type="hidden" name="action" value="<?php echo $reviews_test_action; ?>" />
                <input type="hidden" name="<?php echo $nonce_name; ?>" value="<?php echo $nonce_value; ?>" />
                <input type="hidden" name="getsnsrev_sns" value="<?php echo $row['sns']; ?>" />
                <input type="hidden" name="getsnsrev_service" class="getsnsrev-service-sync" value="" />
                <input type="hidden" name="getsnsrev_max_reviews" class="getsnsrev-max-sync" value="" />
                <input type="hidden" name="getsnsrev_no_cache" class="getsnsrev-no-cache-sync" value="" />
                <label class="getsnsrev-reviews-test__sns-label" for="<?php echo $row['input_id']; ?>"><?php echo $row['label']; ?></label>
                <input type="text" id="<?php echo $row['input_id']; ?>" name="getsnsrev_keyword" class="regular-text"
                       value="<?php echo $row['keyword_value']; ?>" placeholder="<?php echo $row['placeholder']; ?>"
                       autocomplete="off" spellcheck="false" />
                <?php ?>
                <button type="submit" class="button"><?php echo $submit_label; ?></button>
                <?php if ($row['keyword_help'] !== '') : ?>
                    <p class="description getsnsrev-reviews-test__hint"><?php echo $row['keyword_help']; ?></p>
                <?php endif; ?>
            </form>
        <?php endforeach; ?>
    </div>
    <div class="getsnsrev-reviews-test__shared">
        <span class="getsnsrev-reviews-test__shared-item">
            <label for="getsnsrev-reviews-service"><?php echo $service_label; ?></label>
            <select id="getsnsrev-reviews-service">
                <?php foreach ($service_options as $option) : ?>
                    <option value="<?php echo $option['value']; ?>"<?php if ($option['value'] === $service_selected) : ?> selected="selected"<?php endif; ?>><?php echo $option['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </span>
        <span class="getsnsrev-reviews-test__shared-item">
            <label for="getsnsrev-reviews-max"><?php echo $max_label; ?></label>
            <input type="number" id="getsnsrev-reviews-max" min="1" max="100" step="1" value="<?php echo $max_value; ?>" />
        </span>
        <span class="getsnsrev-reviews-test__shared-item getsnsrev-reviews-test__shared-item--no-cache">
            <label for="getsnsrev-no-cache">
                <input type="checkbox" id="getsnsrev-no-cache" value="1" checked="checked" />
                <?php echo $no_cache_label; ?>
            </label>
        </span>
    </div>
    <p class="description"><?php echo $no_cache_help; ?></p>
    <form class="getsnsrev-reviews-test__bulk" method="post" action="<?php echo $form_action; ?>">
        <input type="hidden" name="action" value="<?php echo $bulk_action; ?>" />
        <input type="hidden" name="<?php echo $nonce_name; ?>" value="<?php echo $nonce_value; ?>" />
        <input type="hidden" name="getsnsrev_service" class="getsnsrev-service-sync" value="" />
        <input type="hidden" name="getsnsrev_max_reviews" class="getsnsrev-max-sync" value="" />
        <?php foreach ($sns_rows as $row) : ?>
            <input type="hidden" name="getsnsrev_keyword_<?php echo $row['sns']; ?>" class="getsnsrev-keyword-sync" data-sns="<?php echo $row['sns']; ?>" value="" />
        <?php endforeach; ?>
        <p class="getsnsrev-reviews-test__bulk-actions">
            <button type="submit" class="button button-primary" data-fetching-label="<?php echo $bulk_fetching_label; ?>"><?php echo $bulk_button_label; ?></button>
            <span class="description"><?php echo $bulk_help; ?></span>
        </p>
    </form>
    <footer class="getsnsrev-section__results">
        <?php if (!empty($result['has_result'])) : ?>
            <div class="getsnsrev-detail-result">
                <h4 class="getsnsrev-detail-result__title"><?php echo $result['heading']; ?></h4>
                <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $result['site_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['site']; ?></span></p>
                <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $result['code_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['code']; ?></span></p>
                <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $result['service_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['service']; ?></span></p>
                <p class="getsnsrev-result getsnsrev-result--source <?php echo $result['source_class']; ?>"><span class="getsnsrev-result__label"><?php echo $result['source_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['source_text']; ?></span></p>
                <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $result['at_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['at']; ?></span></p>
                <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $result['count_label']; ?></span><span class="getsnsrev-result__value"><?php echo $result['count']; ?></span></p>
                <?php if ($result['error'] !== '') : ?>
                    <p class="getsnsrev-result__message"><?php echo $result['error']; ?></p>
                <?php elseif ($result['count'] > 0) : ?>
                    <table class="getsnsrev-reviews-test__table">
                        <thead>
                            <tr>
                                <?php foreach ($result['columns'] as $column) : ?>
                                    <th scope="col"><?php echo $column; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['rows'] as $review) : ?>
                                <tr>
                                    <td><?php echo $review['author']; ?></td>
                                    <td><?php echo $review['text']; ?></td>
                                    <td><?php echo $review['posted_date']; ?></td>
                                    <td><?php echo $review['likes']; ?></td>
                                    <td><?php echo $review['comments']; ?></td>
                                    <td>
                                        <?php if ($review['url'] !== '') : ?>
                                            <a href="<?php echo $review['url']; ?>" target="_blank" rel="noopener noreferrer"><?php echo $result['link_label']; ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="getsnsrev-result__message"><?php echo $result['not_found']; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </footer>
    <?php if (!empty($bulk['has_result'])) : ?>
        <section class="getsnsrev-bulk-result" id="getsnsrev-bulk-result">
            <h4 class="getsnsrev-detail-result__title"><?php echo $bulk['heading']; ?></h4>
            <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $bulk['service_label']; ?></span><span class="getsnsrev-result__value"><?php echo $bulk['service']; ?></span></p>
            <p class="getsnsrev-result"><span class="getsnsrev-result__label"><?php echo $bulk['at_label']; ?></span><span class="getsnsrev-result__value"><?php echo $bulk['at']; ?></span></p>
            <table class="getsnsrev-reviews-test__table getsnsrev-bulk-result__table">
                <thead>
                    <tr>
                        <?php foreach ($bulk['columns'] as $column) : ?>
                            <th scope="col"><?php echo $column; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bulk['rows'] as $bulk_row) : ?>
                        <tr>
                            <td><?php echo $bulk_row['sns']; ?></td>
                            <td><?php echo $bulk_row['keyword']; ?></td>
                            <td class="getsnsrev-bulk-result__status <?php echo $bulk_row['status_class']; ?>"><?php echo $bulk_row['status']; ?></td>
                            <td>
                                <?php if (!empty($bulk_row['can_mark'])) : ?>
                                    <form method="post" action="<?php echo $bulk['form_action']; ?>" class="getsnsrev-bulk-result__mark">
                                        <input type="hidden" name="action" value="<?php echo $bulk['mark_action']; ?>" />
                                        <input type="hidden" name="<?php echo $bulk['nonce_name']; ?>" value="<?php echo $bulk['nonce_value']; ?>" />
                                        <input type="hidden" name="getsnsrev_service" value="<?php echo $bulk['mark_service']; ?>" />
                                        <input type="hidden" name="getsnsrev_sns" value="<?php echo $bulk_row['mark_sns']; ?>" />
                                        <button type="submit" class="button button-small"><?php echo $bulk['mark_label']; ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($bulk_row['reviews']['rows'])) : ?>
                            <tr class="getsnsrev-bulk-result__reviews-row">
                                <td colspan="<?php echo count($bulk['columns']); ?>">
                                    <p class="getsnsrev-bulk-result__reviews-title"><?php echo $bulk_row['reviews']['heading']; ?>（<?php echo $bulk_row['reviews']['count']; ?>件）</p>
                                    <table class="getsnsrev-reviews-test__table">
                                        <thead>
                                            <tr>
                                                <?php foreach ($bulk_row['reviews']['columns'] as $review_column) : ?>
                                                    <th scope="col"><?php echo $review_column; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bulk_row['reviews']['rows'] as $bulk_review) : ?>
                                                <tr>
                                                    <td><?php echo $bulk_review['author']; ?></td>
                                                    <td><?php echo $bulk_review['text']; ?></td>
                                                    <td><?php echo $bulk_review['posted_date']; ?></td>
                                                    <td><?php echo $bulk_review['likes']; ?></td>
                                                    <td><?php echo $bulk_review['comments']; ?></td>
                                                    <td>
                                                        <?php if ($bulk_review['url'] !== '') : ?>
                                                            <a href="<?php echo $bulk_review['url']; ?>" target="_blank" rel="noopener noreferrer"><?php echo $bulk_row['reviews']['link_label']; ?></a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" action="<?php echo $bulk['form_action']; ?>" class="getsnsrev-bulk-result__reset">
                <input type="hidden" name="action" value="<?php echo $bulk['reset_action']; ?>" />
                <input type="hidden" name="<?php echo $bulk['nonce_name']; ?>" value="<?php echo $bulk['nonce_value']; ?>" />
                <p>
                    <button type="submit" class="button"><?php echo $bulk['reset_label']; ?></button>
                    <span class="description"><?php echo $bulk['saved_note']; ?></span>
                </p>
            </form>
        </section>
    <?php endif; ?>
</details>