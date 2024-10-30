<?php

namespace WcMipConnector\Factory;

defined('ABSPATH') || exit;

use WcMipConnector\Entity\WCDimension;
use WcMipConnector\Entity\WCProduct;
use WcMipConnector\Entity\WCProductLang;
use WcMipConnector\Entity\WCSrc;
use WcMipConnector\Service\SupplierService;
use WcMipConnector\Service\TaxesService;

class ProductFactory
{
    private const PUBLISH_STATUS = 'publish';
    private const VISIBILITY_STATUS = 'visible';
    private const VARIABLE = 'variable';
    private const SIMPLE = 'simple';

    public const UNITY_AND_AMOUNT_BY_UNIT_TYPES = [
        'ONE_KILOGRAM' => [
            'unity' => 'kg',
            'amount' => 1,
        ],
        'HUNDRED_GRAMS' => [
            'unity' => 'gr',
            'amount' => 100,
        ],
        'ONE_LITER' => [
            'unity' => 'l',
            'amount' => 1,
        ],
        'HUNDRED_MILLILITERS' => [
            'unity' => 'ml',
            'amount' => 100,
        ],
        'ONE_METER' => [
            'unity' =>'m',
            'amount' => 1,
        ],
        'ONE_SQUARE_METER' => [
            'unity' =>'m2',
            'amount' => 1,
        ],
        'ONE_UNIT' => [
            'unity' => 'unit',
            'amount' => 1,
        ],
    ];

    /** @var TaxesService */
    private $taxesService;
    /** @var SupplierService */
    private $supplierService;

    public function __construct()
    {
        $this->taxesService = new TaxesService();
        $this->supplierService = new SupplierService();
    }

    /**
     * @param array $product
     * @param array $tagIds
     * @param array $categoryIds
     * @param array $brandIds
     * @param array $brandPluginIds
     * @param array $attributesIds
     * @param string $languageIsoCode
     * @param array $taxes
     * @return WCProduct
     */
    public function create(
        array $product,
        array $tagIds,
        array $categoryIds,
        array $brandIds,
        array $brandPluginIds,
        array $attributesIds,
        string $languageIsoCode,
        array $taxes
    ): WCProduct {
        $productModel = new WCProduct();

        $productLangModel = $this->createLang($product['ProductLangs'], $languageIsoCode);

        $productModel->name = $productLangModel->title;
        $productModel->description = $productLangModel->description;
        $productModel->sku = $product['SKU'];
        $productModel->price = $product['Price'];
        $productModel->regular_price = (string)$product['Price'];
        $productModel->sale_price = (string)$product['Price'];
        $productModel->type = self::SIMPLE;

        if ($product['Tax']['TaxID']) {
            $taxClass = $this->taxesService->getTaxClassByTaxId($taxes, (int)$product['Tax']['TaxID']);
            $productModel->tax_class = $taxClass;
        }

        if (\array_key_exists('ProductUnitInfo', $product) && empty($product['Variations'])) {
            $productModel->short_description = self::getProductUnitInfo($product['ProductUnitInfo'], $product['Price']);
        }

        if ($product['Variations']) {
            $productModel->type = self::VARIABLE;
        }

        if ($product['Stock'] > 0) {
            $productModel->in_stock = true;
        }

        $productModel->manage_stock = true;
        $productModel->stock_quantity = $product['Stock'];
        $productModel->weight = (string)$product['Weight'];

        $dimensionModel = new WCDimension();
        $dimensionModel->length = (string)$product['Length'];
        $dimensionModel->width = (string)$product['Width'];
        $dimensionModel->height = (string)$product['Height'];

        $productModel->status = self::PUBLISH_STATUS;
        $productModel->catalog_visibility = self::VISIBILITY_STATUS;

        $productModel->dimensions = $dimensionModel;
        $productModel->images = $this->createImage($product['Images'], $product['SKU']);
        $productModel->tags = $tagIds;
        $productModel->categories = $categoryIds;

        $supplier = $this->supplierService->getAttribute();
        $attributes = \array_merge($attributesIds, [$brandIds], [$supplier]);

        $productModel->attributes = $attributes;
        $productModel->id = null;
        $productModel->brands = $brandPluginIds;

        return $productModel;
    }

    /**
     * @param array $productLangs
     * @param string $languageIsoCode
     * @return WCProductLang
     */
    private function createLang(array $productLangs, string $languageIsoCode): WCProductLang
    {
        $productLangModel = new WCProductLang();

        foreach ($productLangs as $productLang) {
            if (strtolower($productLang['IsoCode']) === strtolower($languageIsoCode)) {
                $productLangModel->title = $productLang['Title'];
                $productLangModel->description = $productLang['Description'];

                return $productLangModel;
            }
        }

        return $productLangModel;
    }

    /**
     * @param array $productImages
     * @param string $sku
     * @return WCSrc[]
     */
    private function createImage(array $productImages, string $sku): array
    {
        $images = [];

        foreach ($productImages as $key => $productImage) {
            $srcModel = new WCSrc();
            $srcModel->position = $key;
            $srcModel->name = $sku.'_'.$key;
            $srcModel->src = $productImage['ImageURL'];
            $srcModel->alt = $sku.'_'.$key;
            $images[] = $srcModel;
        }

        return $images;
    }

    /**
     * @param array $tags
     * @param WCProduct $product
     * @return array
     */
    public function setTags(array $tags, WCProduct $product): array
    {
        foreach ($tags as $tag) {
            $product->tags[] = ['id' => (int)$tag];
        }

        return $product->tags;
    }

    public static function getProductUnitInfo(array $productUnitInfo, float $price): string
    {
        $unitInfo = '';

        if (
            empty($productUnitInfo)
            || !\array_key_exists($productUnitInfo['Type'], self::UNITY_AND_AMOUNT_BY_UNIT_TYPES)
        ) {
            return $unitInfo;
        }

        $amount = self::UNITY_AND_AMOUNT_BY_UNIT_TYPES[$productUnitInfo['Type']]['amount'];
        $unity = self::UNITY_AND_AMOUNT_BY_UNIT_TYPES[$productUnitInfo['Type']]['unity'];
        $productAmount = $productUnitInfo['Amount'];

        try {
            $priceUnity = \number_format(($amount * $price) / $productAmount, 4, '.', '');
        } catch (\Throwable $exception) {
            return $unitInfo;
        }

        return $priceUnity.' '.get_woocommerce_currency_symbol().' / '.$amount.' '.$unity;
    }
}