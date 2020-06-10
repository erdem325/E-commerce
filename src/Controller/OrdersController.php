<?php

namespace App\Controller;

use App\Entity\OrderDetail;
use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\Admin\SettingRepository;
use App\Repository\OrderDetailRepository;
use App\Repository\OrdersRepository;
use App\Repository\ShopcartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orders")
 */
class OrdersController extends AbstractController
{
    /**
     * @Route("/", name="orders_index", methods="GET")
     */
    public function index(OrdersRepository $ordersRepository, SettingRepository $settingRepository): Response
    {
        $user = $this->getUser();
        $data =$settingRepository->findAll();
        $userid = $user->getid();
        return $this->render('orders/index.html.twig', [
            'orders' => $ordersRepository->findBy(['userid'=>$userid]),
            'data' => $data,
            ]);
    }

    /**
     * @Route("/new", name="orders_new", methods="GET|POST")
     */
    public function new(Request $request, SettingRepository $settingRepository, ShopcartRepository $shopcartRepository): Response
    {
        $orders = new Orders();
        $data =$settingRepository->findAll();
        $form = $this->createForm(OrdersType::class, $orders);
        $form->handleRequest($request);

        $user = $this->getUser();
        $userid = $user->getid();
        $total = $shopcartRepository->getUserShopCartTotal($userid);

        $submittedToken = $request->request->get('token');
        if($this->isCsrfTokenValid('form-order',$submittedToken)){


            if ($form->isSubmitted() ) {
                //kredi kartı ile ilgili işlemler burada gerçekleşir.
                $em = $this->getDoctrine()->getManager();

                $orders->setUserid($userid);
                $orders->setAmount($total);
                $orders->setStatus("New");

                $em->persist($orders);
                $em->flush();

                $orderid = $orders->getId();
                $shopcart=$shopcartRepository->getUserShopCart($userid);

                foreach ($shopcart as $item){
                    $orderdetail = new OrderDetail();
                    $orderdetail->setOrderid($orderid);
                    $orderdetail->setUserid($user->getid());
                    $orderdetail->setProductid( $item["productid"]);
                    $orderdetail->setPrice( $item["pprice"]);
                    $orderdetail->setQuantity( $item["quantity"]);
                    $orderdetail->setAmount( $item["total"]);
                    $orderdetail->setName( $item["title"]);
                    $orderdetail->setStatus( "Ordered");
                    $orderdetail->setStatus( "Ordered");

                    $em->persist($orderdetail);
                    $em->flush();
                }
                //delete user shpocart
                $em  = $this->getDoctrine()->getManager();
                $query = $em->createQuery('
                DELETE FROM App\Entity\Shopcart s 
                WHERE s.userid = :userid
                ')
                    ->setParameter('userid', $userid);
                $query->execute();
                $this->addFlash('success', 'siparişiniz başarıyla gerçekleştirilmiştir...');
                return $this->redirectToRoute('orders_index');
            }
        }
        return $this->render('orders/new.html.twig', [
            'order' => $orders,
            'total' => $total,
            'form' => $form->createView(),
            'data' => $data,
        ]);
    }

    /**
     * @Route("/{id}", name="orders_show", methods="GET")
     */
    public function show(Orders $order, SettingRepository $settingRepository, OrderDetailRepository $orderDetailRepository): Response
    {
        $user = $this->getUser();
        $userid = $user->getId();
        $orderid = $order->getId();
        $data =$settingRepository->findAll();
        $orderdetail = $orderDetailRepository->findBy(['orderid'=>$orderid]);
        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderdetail' => $orderdetail,
            'data' => $data,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="orders_edit", methods="GET|POST")
     */
    public function edit(Request $request, SettingRepository $settingRepository, Orders $order): Response
    {
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);
        $data =$settingRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('orders_index', ['id' => $order->getId()]);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
            'data' => $data,
        ]);
    }

    /**
     * @Route("/{id}", name="orders_delete", methods="DELETE")
     */
    public function delete(Request $request, Orders $order): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_index');
    }
}
