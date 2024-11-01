<?php

if (!class_exists('TCG_WCFM_Fields')) {
    class TCG_WCFM_Fields extends WCFM_Fields
    {
        /**
         * Output a text input box.
         *
         * @access public
         * @param array $field
         * @return void
         */
        public function text_input($field)
        {
            if ($field['id'] == 'woocommerce_the_courier_guy_shopArea') {
                $data            = [];
                $data['options'] = [];
                if (is_array($field['value'])) {
                    $data['options'] = $field['value'];
                }
                ob_start();
                ?>
                <p class="zip wcfm_title wcfm_ele"><strong><?php
                        echo wp_kses_post($field['label']); ?></strong></p>
                <label class="screen-reader-text" for="<?php
                echo esc_attr($field['id']); ?>"><?php
                    echo wp_kses_post($field['label']); ?></label>
                <select class="select <?php
                echo esc_attr($field['class']); ?>" name="<?php
                echo esc_attr($field['name']); ?>" id="<?php
                echo esc_attr($field['id']); ?>">
                    <?php
                    foreach ((array)$data['options'] as $option_key => $option_value) : ?>
                        <option value="<?php
                        echo esc_attr($option_key); ?>" <?php
                        selected($option_key, esc_attr('4509')); ?>><?php
                            echo esc_attr($option_value); ?></option>
                    <?php
                    endforeach; ?>
                </select>
                <?php

                echo ob_get_clean();
            } else {
                parent::text_input($field);
            }
        }
    }
}
