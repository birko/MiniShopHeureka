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

        $medias = $em->getRepository("CoreProductBundle:ProductMedia")->getProductsMediasArray(null, array('image'));
        $videos = $em->getRepository("CoreProductBundle:ProductMedia")->getProductsMediasArray(null, array('video'));
        $stocks = $em->getRepository("CoreProductBundle:Stock")->getStocksArray();
        $attributes = $em->getRepository("CoreProductBundle:Attribute")->getGroupedAttributesByProducts(array(), array(), $request->get('_locale'));
        $options = $em->getRepository("CoreProductBundle:ProductOption")->getGroupedOptionsByProducts(array(), array(), $request->get('_locale'));
        $shippings = $em->getRepository("CoreShopBundle:Shipping")->getShippingQueryBuilder(null, true)->getQuery()->getResult();
        
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
        $delivery_id = $this->container->getParameter('heureka.delivery_id');
        
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
                $i = 0;
                foreach($medias[$product->getId()] as $media)
                {
                    $imgurl = ($i === 0) ? 'IMGURL' : 'IMGURL_ALTERNATIVE';
                    $img = $document->createElement($imgurl);
                    $img->appendChild($document->createTextNode($request->getScheme() . '://' . $request->getHttpHost() . '/'. $media->getWebPath('original')));
                    $item->appendChild($img);
                    $i++;
                }
            }
            
            if (isset($videos[$product->getId()])) {
                $media = reset($videos[$product->getId()]);
                $img = $document->createElement('VIDEO_URL');
                switch($media->getVideoType()) {
                    case 1: 
                        $img->appendChild($document->createTextNode('http://www.youtube.com/watch?v=' . $media->getSource()));
                        break;
                    case 2: 
                        $img->appendChild($document->createTextNode('http://www.vimeo.com/' . $media->getSource()));
                        break;
                    default: 
                        $img->appendChild($document->createTextNode($request->getScheme() . '://' . $request->getHttpHost() . '/'. $media->getWebPath('original')));
                        break;
                }                
                $item->appendChild($img);
                $i++;
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
            $price->appendChild($document->createTextNode(number_format($pprice, 2, '.', '')));
            $item->appendChild($price);

            $manuf = $document->createElement('MANUFACTURER');
            if ($product->getVendor()) {
                $manuf->appendChild($document->createCDATASection(trim($product->getVendor()->getTitle())));
            }
            $item->appendChild($manuf);

            $avb = $document->createElement('DELIVERY_DATE');
            $qdocument = "0";
            if (isset($stocks[$product->getId()])) {
                $stock = reset($stocks[$product->getId()]);
                $qdocument = ($stock->getAmount() > 0 || ($stock->getAvailability())) ? "0" : "";
            }
            $avb->appendChild($document->createTextNode($qdocument));
            $item->appendChild($avb);
            
            if (!empty($shippings)) {
                foreach($shippings as $ship) {
                    $is = $document->createElement('DELIVERY');
                    $sh = $document->createElement('DELIVERY_ID');
                    if (array_key_exists($ship->getId(), $delivery_id)){
                        $sh->appendChild($document->createTextNode($delivery_id[$ship->getId()]));
                    } else {
                         $sh->appendChild($document->createTextNode('VLASTNA_PREPRAVA'));
                    }
                    $is->appendChild($sh);
                    $sh = $document->createElement('DELIVERY_PRICE');
                    $sh->appendChild($document->createTextNode(number_format($ship->calculatePriceVAT($currency), 2, '.', '')));
                    $is->appendChild($sh);
                    $sh = $document->createElement('DELIVERY_PRICE_COD');
                    $sh->appendChild($document->createTextNode(number_format($ship->calculatePriceVAT($currency), 2, '.', '')));
                    $is->appendChild($sh);
                    $item->appendChild($is);
                }
            }

            $cat = $document->createElement('CATEGORYTEXT');
            $pom = "";
            if ($product->getProductCategories()->count() > 0) {
                $category  = $product->getProductCategories()->first()->getCategory();
                $category_id = $category->getId();
                if (isset($paths[$category_id])) {
                    $pom = $paths[$category_id];
                } else {
                    $path ="";
                    $categoryquery = $em->getRepository('CoreCategoryBundle:Category')
                    ->getPathQueryBuilder($category)
                    ->andWhere("node.enabled=:enabled")
                    ->setParameter("enabled", true)
                    ->getQuery();
                    if ($request->get('_locale')) {
                        $categoryquery->setHint(
                            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER, 
                            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                        );
                        $categoryquery->setHint(
                            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                            $request->get('_locale') // take locale from session or request etc.
                        );
                    }
                    $pathcategories = $categoryquery->getResult();
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
            
            $parameters = array();
            foreach(array($attributes, $options) as $parameterArray)
            {
                if(isset($parameterArray[$product->getId()])) {
                    foreach($parameterArray[$product->getId()] as $aname => $avalues) {
                        foreach($avalues as $av) {
                            $parameters[$aname][$av['value']] = array(
                                'name' => $aname,
                                'value' => $av['value'],
                            );
                        }
                    }
                }
            }
            if(!empty($parameters)) {
                $combinations = $this->addCombination($parameters);
                foreach($combinations as $comb) {
                    $clone = $item->cloneNode(true);
                    foreach ($comb as $cname => $cvalue) {
                        $param = $document->createElement('PARAM');
                        $param_name = $document->createElement('PARAM_NAME');
                        $param_name->appendChild($document->createTextNode($cname));
                        $param->appendChild($param_name);
                        $param_val = $document->createElement('VAL');
                        $param_val->appendChild($document->createTextNode($cvalue));
                        $param->appendChild($param_val);
                        $clone->appendChild($param);
                    }
                    $itemgroup = $document->createElement('ITEMGROUP_ID');
                    $icode = trim($product->getId());
                    $itemgroup->appendChild($document->createTextNode($icode));
                    $clone->appendChild($itemgroup);
                    $shop->appendChild($clone);
                }
            } else {
                $shop->appendChild($item);
            }    
        }

        $response = new Response();
        $response->setContent($document->saveXML());
        $response->headers->set('Content-Encoding', ' UTF-8');
        $response->headers->set('Content-Type', ' text/xml; charset=UTF-8');
        $response->headers->set('Content-disposition', ' attachment;filename=heureka.xml');

        return $response;
    }
    
    private function addCombination($options)
    {
        $comb = array_shift($options);
        $first = reset($comb);
        $option_name = $first['name'];
        $result = array();
        foreach ($comb as $ovalues) {
            $option_value = $ovalues['value'];
            if(!empty($options)) {
                $prev_result = $this->addCombination($options);
                foreach($prev_result as $pom) {
                    $result[] = array_merge(array($option_name => $option_value), $pom);
                }
            } else {
                $result[] =  array($option_name => $option_value);
            }
        }
        
        return $result;
    }
    
    public function parseSekcieAction()
    {
        $sections = array(
            'sk' => 'http://www.heureka.sk/direct/xml-export/shops/heureka-sekce.xml',
            'cz' => 'http://www.heureka.cz/direct/xml-export/shops/heureka-sekce.xml',
        );
        $file = "<?php\narray(\n";
        foreach($sections as $lang => $url) 
        {
            $file .= "\t\"{$lang}\" => array(\n";
            $element = simplexml_load_file($url);
            $this->parseElement($element, $file);
            $file .= "\t),\n";
        }
        $file .= ");\n";
        
        echo $file;
        $response = new Response();
        $response->setContent($file);
        $response->headers->set('Content-Encoding', ' UTF-8');
        $response->headers->set('Content-Type', ' text/xml; charset=UTF-8');
        $response->headers->set('Content-disposition', ' attachment;filename=heureka.php');

        return $response;
    }
    
    private function parseElement($element, &$file, $parent = null)
    {
        foreach($element->CATEGORY as $category) {
            $path = $category->CATEGORY_NAME; 
            if ($parent !== null) {
                $path = $parent ." | ".$path;
            }
            $file .=  "\t\t\"{$category->CATEGORY_ID}\" => \"{$path}\",\n";
            $this->parseElement($category, $file, $path);
        }
    }
}
