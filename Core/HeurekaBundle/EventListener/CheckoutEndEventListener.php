<?php
namespace Core\HeurekaBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class CheckoutEndEventListener
{
    protected $container;
    protected $em;

    public function __construct(\Symfony\Component\DependencyInjection\Container $container, \Doctrine\ORM\EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $controller = $event->getController();

        if (!is_array($controller) && !($controller[0] instanceof ShopController)) {
            return;
        }
        $controller = $controller[0]; //ShopController
        $request = $event->getRequest();
        $session = $request->getSession();
        $routename = $request->get('_route');
        if (in_array($routename, array("checkout_end"))) {
            $heureka = $this->container->getParameter('heureka.key');
            if (!empty($heureka)) {
                $cart = $this->container->get('neonus_nws_shop.shopservice')->getCart();
                if (!$cart->isEmpty()) {
                    if ($session->has('order-id')) {
                        $order = $session->get('order-id');
                        $em = $this->em;
                        $orderEntity = $em->getRepository('CoreShopBundle:Order')->find($order);
                        if (!empty($orderEntity)) {
                            $overeno = new \HeurekaOvereno($heureka, \HeurekaOvereno::LANGUAGE_SK);
                            $overeno->setEmail($orderEntity->getInvoiceEmail());
                            $orderitems = $orderEntity->getItems();
                            foreach ($orderitems as $item) {
                                if ($item->getProduct()) {
                                    $pname = str_replace(array('&nbsp;', '&amp;'), array(" ", "&"), strip_tags($item->getProduct()->getTitle()));
                                    $overeno->addProduct(trim($pname));
                                    $overeno->addProductItemId($item->getProduct()->getId());
                                }
                            }
                            $overeno->addOrderId($orderEntity->getId());
                            $overeno->send();
                        }
                    }
                }
            }
        }
    }
}
