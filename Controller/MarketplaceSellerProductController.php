<?php
/**
 * Copyright since 2022 Bwlab of Luigi Massa and Contributors
 * Bwlab of Luigi Massa is an Italy Company
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shoppygo.io so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade ShoppyGo to newer
 * versions in the future. If you wish to customize ShoppyGo for your
 * needs please refer to https://docs.shoppygo.io/ for more information.
 *
 * @author    Bwlab and Contributors <contact@shoppygo.io>
 * @copyright Since 2022 Bwlab of Luigi Massa and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace ShoppyGo\MarketplaceBundle\Controller;

use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShopBundle\Controller\Admin\ProductController;
use ShoppyGo\MarketplaceBundle\Domain\Product\Command\CreateSellerProductCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketplaceSellerProductController extends ProductController
{
    /**
     * @var ProductController
     */
    private $productController;

    public function __construct(ProductController $productController)
    {
        parent::__construct();
        $this->productController = $productController;
    }

    /**
     * Product form.
     *
     * @param int $id The product ID
     * @param Request $request
     *
     * @return array|Response Template vars
     *
     * @throws \LogicException
     */
    public function formAction($id, Request $request)
    {
        $seller_id = null;
        $marketplaceCore = $this->get('shoppygo.core');

        if ($marketplaceCore->isEmployeeSeller()) {
            $product = new Product($id);
            if ((int) $product->state !== Product::STATE_TEMP) {
                //
                // il prodotto NON è in fase di creazione
                // Product::STATE_TEMP è lo stato settato da newAction
                //  in questo caso non essendo in fase di creazione devo controllare che appartenga al seller
                $is_owner = $this->get('shoppygo.marketplace.repository.product_supplier_repository')->isSellerProduct(
                    $id,
                    $marketplaceCore->getSellerId());
                if ($is_owner === false) {
                    //
                    // se non appartiene al seller redireziono al catalogo
                    //
                    return $this->redirect('admin_product_catalog');
                }
            }
        }
        $parent_response = parent::formAction($id, $request);

        //modifica della lista delle cateorie
//        $parent_response['categories'] = $this->get('shoppygo.provider.category')
//            ->getCategoriesWithBreadCrumb();

        if ($parent_response instanceof JsonResponse) {
            if ($marketplaceCore->isEmployeeSeller()) {
                // @todo da disattivare quanto l'actionAfterCreateProductFormHadler sarà creato
                $this->get('prestashop.core.command_bus')
                    ->handle(new CreateSellerProductCommand($marketplaceCore->getSellerId(), $id));
            }

            return $parent_response;
        }

        return new Response(
            $this->renderView(
//                '@PrestaShop/Admin/Product/ProductPage/product.html.twig',
                '@ShoppyGoMarketplace/overrides/product_page/product.html.twig',
                array_merge($parent_response, ['is_seller' => $marketplaceCore->isEmployeeSeller()])
            )
        );
    }

    public function newAction()
    {
        return parent::newAction(); // TODO: Change the autogenerated stub
    }
}
