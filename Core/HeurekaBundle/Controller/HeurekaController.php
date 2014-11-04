<?php

namespace Core\HeurekaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Site\ShopBundle\Controller\ShopController;

class HeurekaController extends ShopController
{
    public function exportAction()
    {
        $request = $this->getRequest();
        
        $minishop  = $this->container->getParameter('minishop');
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository("CoreProductBundle:Product")->findByCategoryQuery(null, false, true, true, false);
        if ($request->get('_locale')) {
            $query->setHint(
                \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                $request->get('_locale') // take locale from session or request etc.
            );
        }

        $medias = $em->getRepository("CoreProductBundle:ProductMedia")->getProductsMediasArray();
        $stocks = $em->getRepository("CoreProductBundle:Stock")->getStocksArray();
        
        $pricegroup_id = $request->get('pricegroup');
        $priceGroup = null;
        if ($pricegroup_id !== null) {
            $priceGroup = $em->getRepository('CoreUserBundle:PriceGroup')->find($pricegroup_id);
        }
        $priceGroup = ($priceGroup) ? $priceGroup : $this->getPriceGroup();
        $currency_id = $request->get('currency');
        $currency = null;
        if ($currency_id !== null) {
            $currency = $em->getRepository('CorePriceBundle:Currency')->find($currency_id);
        }
        $currency = ($currency) ? $currency : $this->getCurrency();
        $pricetypes = $this->container->getParameter('heureka.prices');
        
        $request= $this->getRequest();
        $document = new \DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;
        $shop = $document->appendChild($document->createElement('SHOP'));
        $paths = array();

        foreach ($query->getResult()  as $product) {
            $item = $document->createElement('SHOPITEM');

            $code = $document->createElement('ITEM_ID');
            $code->appendChild($document->createTextNode($product->getId()));
            $item->appendChild($code);

            $name = $document->createElement('PRODUCT');
            $pname = str_replace(array('&nbsp;', '&amp;'), array(" ", "&"), strip_tags($product->getTitle()));
            $name->appendChild($document->createCDATASection($pname));
            $item->appendChild($name);

            $name = $document->createElement('PRODUCTNAME');
            $name->appendChild($document->createCDATASection($pname));
            $item->appendChild($name);

            $description = str_replace(array("\x0B", "\0", "\r", "\t"), ' ', strip_tags($product->getLongDescription() . " " . $product->getLongDescription())); // zrusenie niektorych whitespacesnakov za medzery
            $description = preg_replace('/\s+/', ' ', $description);
            $description = str_replace(array('&nbsp;', '&amp;'), array(" ", "&"), $description);
            $desc = $document->createElement('DESCRIPTION');
            if (!empty($description)) {
                $desc->appendChild($document->createCDATASection($description));
            }
            $item->appendChild($desc);

            $ean = $document->createElement('EAN');
            $eancode = trim($product->getId());
            $ean->appendChild($document->createTextNode($eancode));
            $item->appendChild($ean);

            if (isset($medias[$product->getId()])) {
                $img = $document->createElement('IMGURL');
                $media = current($medias[$product->getId()]);
                $img->appendChild($document->createTextNode($request->getScheme() . '://' . $request->getHttpHost() . '/'. $media->getWebPath('original')));
                $item->appendChild($img);
            }

            $price = $document->createElement('PRICE_VAT');
            $pprice = 0;
            foreach($pricetypes as $type) {
                $priceEntity = $product->getMinimalPrice($currency, $priceGroup, $type);
                if ($priceEntity) {
                    $pprice = $priceEntity->getPriceVat();
                    break;
                }
            }
            if (isset($prices[$product->getId()])) {
                $pprice = PriceRepository::getProductPrice($prices[$product->getId()], $types);
                $pprice = $pprice->calculatePriceVAT($pricegroup, $currency);
                break;
            }
            $price->appendChild($document->createTextNode(number_format($pprice, 2)));
            $item->appendChild($price);

            $manuf = $document->createElement('MANUFACTURER');
            if ($product->getVendor()) {
                $manuf->appendChild($document->createCDATASection(trim($product->getVendor()->getTitle())));
            }
            $item->appendChild($manuf);

            $avb = $document->createElement('DELIVERY_DATE');

            if (isset($stocks[$product->getId()])) {
                $stock = current($stocks[$product->getId()]);
                $qdocument = ($stock->getAmount() > 0 || ($stock->getAvailability())) ? "0" : "";
                $avb->appendChild($document->createTextNode($qdocument));
            }
            $item->appendChild($avb);

            $cat = $document->createElement('CATEGORYTEXT');
            $pom = "";
            if ($product->getProductCategories()->count() > 0) {
                $category  = $product->getProductCategories()->first()->getCategory();
                $category_id = $category->getId();
                if (isset($paths[$category_id])) {
                    $pom = $paths[$category_id];
                } else {
                    $path ="";
                    $pathcategories = $em->getRepository('CoreCategoryBundle:Category')->getPathQueryBuilder($category)->getQuery()->getResult();
                    if (!empty($pathcategories)) {
                        foreach ($pathcategories as $pathcat) {
                            if (!empty($path)) {
                                $path .= " | ";
                            }
                            $path .= $pathcat->getTitle();
                        }
                    }
                    $pom = $path;
                    $paths[$category_id] = $path;
                }

            }
            $cat->appendChild($document->createTextNode($pom));
            $item->appendChild($cat);

            $url = $document->createElement('URL');
            $routeParams = array('slug'=> $product->getSlug());
            if ($request->get('_locale')) {
                $routeParams['_locale'] = $request->get('_locale');
            }
            $url->appendChild($document->createTextNode($this->generateUrl('product_site', $routeParams, true)));
            $item->appendChild($url);

            $shop->appendChild($item);
        }

        $response = new Response();
        $response->setContent($document->saveXML());
        $response->headers->set('Content-Encoding', ' UTF-8');
        $response->headers->set('Content-Type', ' text/xml; charset=UTF-8');
        $response->headers->set('Content-disposition', ' attachment;filename=heureka.xml');

        return $response;
    }
}
