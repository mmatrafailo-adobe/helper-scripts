<?php

$file = __DIR__ . '/app/etc/config.php';
$ext = include $file;


$allowedPref = [
    'Magento',
    'Amasty',
    'CueBlocks',
    'Mirasvit',
    'Klarna',
    'Wyomind',
    'MiraklSeller',
    'Mageplaza',
    'Aheadworks',
    'Vertex',
    'Bss',
    'CityBeach',
    'WeltPixel',
    'Xtento',
    'Yereone',
    //'Magmodules',
    ];

$disabledByDefault = array(
    'Magento_CatalogInventoryGraphQl' => 0,
    'Magento_Csp' => 0,
    'Magento_Inventory' => 0,
    'Magento_InventoryAdminUi' => 0,
    'Magento_InventoryAdvancedCheckout' => 0,
    'Magento_InventoryApi' => 0,
    'Magento_InventoryBundleImportExport' => 0,
    'Magento_InventoryBundleProduct' => 0,
    'Magento_InventoryBundleProductAdminUi' => 0,
    'Magento_InventoryBundleProductIndexer' => 0,
    'Magento_InventoryCatalog' => 0,
    'Magento_InventorySales' => 0,
    'Magento_InventoryCatalogAdminUi' => 0,
    'Magento_InventoryCatalogApi' => 0,
    'Magento_InventoryCatalogFrontendUi' => 0,
    'Magento_InventoryCatalogSearch' => 0,
    'Magento_InventoryConfigurableProduct' => 0,
    'Magento_InventoryConfigurableProductAdminUi' => 0,
    'Magento_InventoryConfigurableProductFrontendUi' => 0,
    'Magento_InventoryConfigurableProductIndexer' => 0,
    'Magento_InventoryConfiguration' => 0,
    'Magento_InventoryConfigurationApi' => 0,
    'Magento_InventoryDistanceBasedSourceSelection' => 0,
    'Magento_InventoryDistanceBasedSourceSelectionAdminUi' => 0,
    'Magento_InventoryDistanceBasedSourceSelectionApi' => 0,
    'Magento_InventoryElasticsearch' => 0,
    'Magento_InventoryExportStockApi' => 0,
    'Magento_InventoryIndexer' => 0,
    'Magento_InventorySalesApi' => 0,
    'Magento_InventoryGroupedProduct' => 0,
    'Magento_InventoryGroupedProductAdminUi' => 0,
    'Magento_InventoryGroupedProductIndexer' => 0,
    'Magento_InventoryImportExport' => 0,
    'Magento_InventoryInStorePickupApi' => 0,
    'Magento_InventoryInStorePickupAdminUi' => 0,
    'Magento_InventorySourceSelectionApi' => 0,
    'Magento_InventoryInStorePickup' => 0,
    'Magento_InventoryInStorePickupGraphQl' => 0,
    'Magento_InventoryInStorePickupShippingApi' => 0,
    'Magento_InventoryInStorePickupQuoteGraphQl' => 0,
    'Magento_InventoryInStorePickupSales' => 0,
    'Magento_InventoryInStorePickupSalesApi' => 0,
    'Magento_InventoryInStorePickupQuote' => 0,
    'Magento_InventoryInStorePickupShipping' => 0,
    'Magento_InventoryInStorePickupShippingAdminUi' => 0,
    'Magento_InventoryInStorePickupMultishipping' => 0,
    'Magento_InventoryCache' => 0,
    'Magento_InventoryLowQuantityNotification' => 0,
    'Magento_InventoryLowQuantityNotificationApi' => 0,
    'Magento_InventoryMultiDimensionalIndexerApi' => 0,
    'Magento_InventoryProductAlert' => 0,
    'Magento_InventoryRequisitionList' => 0,
    'Magento_InventoryReservations' => 0,
    'Magento_InventoryReservationCli' => 0,
    'Magento_InventoryReservationsApi' => 0,
    'Magento_InventoryExportStock' => 0,
    'Magento_InventorySalesAdminUi' => 0,
    'Magento_InventoryGraphQl' => 0,
    'Magento_InventorySalesFrontendUi' => 0,
    'Magento_InventorySetupFixtureGenerator' => 0,
    'Magento_InventoryShipping' => 0,
    'Magento_InventoryShippingAdminUi' => 0,
    'Magento_InventorySourceDeductionApi' => 0,
    'Magento_InventorySourceSelection' => 0,
    'Magento_InventoryInStorePickupFrontend' => 0,
    'Magento_InventorySwatchesFrontendUi' => 0,
    'Magento_InventoryVisualMerchandiser' => 0,
    'Magento_InventoryWishlist' => 0,
    'Magento_InventoryLowQuantityNotificationAdminUi' => 0,
    'Magento_InventoryInStorePickupSalesAdminUi' => 0,
    'Magento_SwatchesGraphQl' => 0,
    'Magento_SwatchesLayeredNavigation' => 0,
    'Magento_TwoFactorAuth' => 0,
    'Magento_InventoryInStorePickupWebapiExtension' => 0,
    'PayPal_Braintree' => 0,
    'Dotdigitalgroup_Email' => 0,
    'Amazon_Core' => 0,
    'Amazon_Login' => 0,
    'Amazon_Payment' => 0,
    'CueBlocks_PersistentGuestCart' => 0,
    'CueBlocks_StoreMaintenance' => 0,
    'Dotdigitalgroup_Chat' => 0,
    'Dotdigitalgroup_Enterprise' => 0,
    'Dotdigitalgroup_Sms' => 0,
    'Ess_M2ePro' => 0,
    'Klarna_Core' => 0,
    'Klarna_Ordermanagement' => 0,
    'Klarna_Kp' => 0,
    'PayPal_BraintreeGraphQl' => 0,
    'Vertex_Tax' => 0,
);

$disabledByDefault = [];
foreach ($ext['modules'] as $module => $enabled) {
    if (!$enabled) {
        $disabledByDefault[$module] = 0;
    }

}
var_export($disabledByDefault);
die;

$prefixes = [];

foreach ($ext['modules'] as $module => $enabled) {
    $prefix = current(explode('_', $module));
    //echo $prefix;


    if (isset($disabledByDefault[$module])) {
        continue;
    }
    $allowed = in_array($prefix, $allowedPref);

    $allowed = in_array($prefix, $allowedPref);

    if (!$allowed) {

        if (!isset($prefixes[$prefix])) {
            $prefixes[$prefix] = 0;
        }
        $prefixes[$prefix] += 1;
    }


    $ext['modules'][$module] = (int)$allowed;

}

arsort($prefixes);
print_r($prefixes);

file_put_contents($file, "<?php \r\n return " . var_export($ext, true) . ';');