<?php

/**
 * Plugin Name: WCFM and WC Marketplace - The Courier Guy Shipping for WooCommerce
 * Description: WCFM and WC Marketplace - The Courier Guy Shipping for WooCommerce - An extension to allow for the courier collection address to use a vendor's address.
 * Author: The Courier Guy
 * Author URI: https://www.thecourierguy.co.za/
 * Version: 1.0.2
 * Plugin Slug: wp-plugin-the-courier-guy-mvm
 * Text Domain: the-courier-guy-guy-mvm
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WpMultiVendorMarketPlaceTheCourierGuyExtension
 */
if (!class_exists('WpMultiVendorMarketPlaceTheCourierGuyExtension')) {
    class WpMultiVendorMarketPlaceTheCourierGuyExtension
    {

        private $pluginDependencies = [
            'wc-multivendor-marketplace' => [
                'filename' => 'wc-multivendor-marketplace.php',
                'label'    => 'WooCommerce Multivendor Marketplace',
                'url'      => 'https://wclovers.com/knowledgebase_category/wcfm-marketplace/'
            ],
            'the-courier-guy'            => [
                'filename' => 'TheCourierGuy.php',
                'label'    => 'The Courier Guy Shipping for WooCommerce',
                'url'      => 'https://wordpress.org/plugins/the-courier-guy/'
            ]
        ];

        private $requiredPlugins = [];

        /**
         * WpMultiVendorMarketPlaceTheCourierGuyExtension constructor.
         */
        public function __construct()
        {
            add_action('admin_init', [$this, 'validateDependencyPlugins']);
            add_action('wcfm_init', [$this, 'updateWCFMFields'], 999999);
            add_filter('wcfm_marketplace_settings_fields_address', [$this, 'addCourierGuyAddressProperties'], 10, 2);
            add_filter('thecourierguy_before_request_quote', [$this, 'applyVendorAddressToQuotePayload'], 10, 2);
            add_filter(
                'thecourierguy_before_submit_collection',
                [$this, 'applyVendorAddressToCollectionPayload'],
                10,
                2
            );
        }

        public function updateWCFMFields()
        {
            global $WCFM;
            require_once 'class-the-courier-guy-wcfm-fields.php';
            $wcf_fields        = new TCG_WCFM_Fields();
            $WCFM->wcfm_fields = $wcf_fields;
        }

        /**
         * @param array $properties
         * @param $vendorId
         * @return array
         */
        public function addCourierGuyAddressProperties($properties = [], $vendorId)
        {
            $vendor_data                                         = get_user_meta(
                $vendorId,
                'wcfmmp_profile_settings',
                true
            );
            $courierGuyLocationLabel                             = $vendor_data['address']['woocommerce_the_courier_guy_shopPlace'];
            $courierGuyLocation                                  = [
                $vendor_data['address']['woocommerce_the_courier_guy_shopArea'] => $courierGuyLocationLabel
            ];
            $properties['woocommerce_the_courier_guy_shopArea']  = array(
                'label'       => __(
                    'Courier Guy Location',
                    'wc-frontend-manager'
                ),
                'placeholder' => __(
                    'Location',
                    'wc-frontend-manager'
                ),
                'name'        => 'address[woocommerce_the_courier_guy_shopArea]',
                'type'        => 'input',
                'class'       => 'wcfm-text wcfm_ele tcg-suburb-field',
                'label_class' => 'wcfm_title wcfm_ele',
                'value'       => $courierGuyLocation
            );
            $properties['woocommerce_the_courier_guy_shopPlace'] = array(
                'name'        => 'address[woocommerce_the_courier_guy_shopPlace]',
                'type'        => 'hidden',
                'class'       => 'wcfm-text wcfm_ele',
                'label_class' => 'wcfm_title wcfm_ele',
                'value'       => $courierGuyLocationLabel
            );
            return $properties;
        }

        public function applyVendorAddressToQuotePayload($data, $package)
        {
            global $WCFM;
            if ($WCFM->is_marketplace == 'wcfmmarketplace' && isset($package['vendor_id'])) {
                $vendorData = get_user_meta($package['vendor_id'], 'wcfmmp_profile_settings', true);
                $data       = $this->applyVendorAddress($data, $vendorData);
            }
            return $data;
        }

        public function applyVendorAddressToCollectionPayload($data, $shipping)
        {
            global $WCFM;
            if ($WCFM->is_marketplace == 'wcfmmarketplace') {
                $vendorData = get_user_meta($shipping->get_meta('vendor_id', true), 'wcfmmp_profile_settings', true);
                $data       = $this->applyVendorAddress($data, $vendorData);
            }
            return $data;
        }

        private function applyVendorAddress($data, $vendorData)
        {
            if (!empty($vendorData)) {
                $address = $vendorData['address'];
                if (!empty($address)) {
                    $data['details']['origperadd1']  = $address['street_1'];
                    $data['details']['origperadd2']  = $address['street_2'];
                    $data['details']['origperpcode'] = $address['zip'];
                    $data['details']['origplace']    = $address['woocommerce_the_courier_guy_shopArea'];
                    $data['details']['origtown']     = $address['woocommerce_the_courier_guy_shopPlace'];
                    $data['details']['origperphone'] = $vendorData['phone'];
                }
            }
            return $data;
        }

        public function validateDependencyPlugins()
        {
            $hasDependencies    = true;
            $pluginDependencies = $this->getPluginDependencies();
            foreach ($pluginDependencies as $index => $value) {
                if (!is_plugin_active($index . '/' . $value['filename'])) {
                    $this->addRequiredPlugins($index, $value);
                    $hasDependencies = false;
                }
            }
            if (!$hasDependencies) {
                add_action('admin_notices', [$this, 'pluginDependencyAdminNotice']);
                deactivate_plugins(plugin_basename(__FILE__));
            }
        }

        public function pluginDependencyAdminNotice()
        {
            ?>
            <div class="error">
                <?php
                $requiredPlugins = $this->getRequiredPlugins();
                foreach ($requiredPlugins as $value) {
                    ?>
                    <p><?php
                        _e(
                            'Please install ' . $value['label'] . ' plugin before activating this plugin. You can download ' . $value['label'] . ' from <a target="_blank" href="' . $value['url'] . '">here</a>.',
                            'default'
                        ); ?></p>
                    <?php
                }
                ?>
            </div>
            <?php
        }

        /**
         * @return mixed
         */
        public function getPluginVersion()
        {
            $pluginDir     = plugin_basename(dirname(__FILE__));
            $pluginData    = current(get_plugins('/' . $pluginDir));
            $pluginVersion = $pluginData['Version'];
            return $pluginVersion;
        }

        /**
         * @return array
         */
        private function getPluginDependencies()
        {
            return $this->pluginDependencies;
        }

        /**
         * @return array
         */
        public function getRequiredPlugins()
        {
            return $this->requiredPlugins;
        }

        /**
         * @param $requiredPluginName
         * @param $requiredPluginDetails
         */
        public function addRequiredPlugins($requiredPluginName, $requiredPluginDetails)
        {
            $this->requiredPlugins[$requiredPluginName] = $requiredPluginDetails;
        }
    }

    new WpMultiVendorMarketPlaceTheCourierGuyExtension();
}
