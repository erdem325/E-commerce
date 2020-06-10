<?php

namespace App\Controller\Admin;

use App\Entity\Orders;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\OrderDetailRepository;
use App\Repository\OrdersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * @Route("/admin")
 */

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="admin")
     */
    public function index()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findBy([
            'status' => 'False', ]);
        return $this->render('admin/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'users' => $users,
        ]);
    }
    /**
     * @Route("/orders/{slug}", name="admin_orders_index")
     */
    public function orders($slug, OrdersRepository $ordersRepository)
    {
        $orders = $ordersRepository->findBy(['status' => $slug]);
        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }
    /**
     * @Route("admin/user_onay/{id}", name="user_onay")
     */
    public function userOnay($id){

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);
        $user->setStatus('True');
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('admin');

    }
    /**
     * @Route("/orders/show/{id}", name="admin_orders_show", methods="GET")
     */
    public function show($id,Orders $order, OrderDetailRepository $orderDetailRepository):Response
    {
        $orderdetail = $orderDetailRepository->findBy(
            ['orderid' => $id]
        );

        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
            'orderdetail' => $orderdetail,
        ]);
    }
    /**
     * @Route("/orders/{id}/update", name="admin_orders_update", methods="POST")
     */
    public function order_update($id,Orders $orders,Request $request):Response
    {

        $em = $this->getDoctrine()->getManager();
        $sql = "UPDATE orders SET shipinfo= :shipinfo, note=:note,status=:status
                  WHERE id=:id";
        $statement=$em->getConnection()->prepare($sql);
        $statement->bindValue('shipinfo', $request->request->get('shipinfo'));
        $statement->bindValue('note', $request->request->get('note'));
        $statement->bindValue('status', $request->request->get('status'));
        $statement->bindValue('id', $id);
        $statement->execute();
        $this->addFlash('success','sipariş bilgileri güncellendi');

        return $this->redirectToRoute('admin_orders_show', array('id'=>$id));
    }
}
