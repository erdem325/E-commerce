<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\User;
use App\Form\Admin\MessagesType;
use App\Form\UserType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\ProductRepository;
use App\Repository\Admin\SettingRepository;

use App\Repository\Admin\ImageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(SettingRepository $settingRepository)
    {

    $data = $settingRepository->findAll();
        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT * FROM product WHERE status="True" ORDER BY ID DESC LIMIT 6';
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $slider = $statement->fetchAll();
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v">';


    return $this->render('home/index.html.twig', [
        'data' => $data,
        'slider' => $slider,
        'cats' => $cats,

    ]);
    }
    /**
     * @Route("/hakkimizda", name="hakkimizda")
     */
    public function hakkimizda(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();
        return $this->render('home/hakkimizda.html.twig', [
            'data' => $data,
        ]);
    }
    /**
     * @Route("/referans", name="referans")
     */
    public function referans(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();
        return $this->render('home/referans.html.twig', [
            'data' => $data,
        ]);
    }
    /**
     * @Route("/iletisim", name="iletisim", methods="GET|POST")
     */
    public function iletisim(SettingRepository $settingRepository, Request $request)
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');

        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('form-message',$submittedToken)){
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();
                $this->addFlash('success','Mesaj Gönderimi Başarılı');
                return $this->redirectToRoute('iletisim');
            }

        }

        $data = $settingRepository->findAll();
        return $this->render('home/iletisim.html.twig', [
            'data' => $data,
            'message' => $message,
        ]);
    }
    public function categorytree($parent=0,$user_tree_array =''){
        if(!is_array($user_tree_array))
            $user_tree_array = array();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM category WHERE status='True' AND parentid =".$parent;
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        if(count($result)>0){
            $user_tree_array[] ="<ul>";
            foreach ($result as $row){
                $string = "href='/category/ ".$row['id']." '";
                $user_tree_array[] = "<li> <a $string>".$row['title']."</a>";
                $user_tree_array = $this->categorytree($row['id'],$user_tree_array);

            }
            $user_tree_array[] = "</li></ul>";
        }
        return $user_tree_array;
    }
    /**
     * @Route("/category/{catid}", name="category_product", methods="GET")
     */
    public function CategoryProduct($catid,CategoryRepository $categoryRepository){
        $cats = $this->categorytree();
        $cats[0]= '<ul id="menu-v">';
        $data= $categoryRepository->findBy(['id' => $catid]);
        //dump($data);
        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT * FROM product WHERE status="True" AND category_id=:catid';
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('catid',$catid);
        $statement->execute();
        $products = $statement->fetchAll();
        //dump($result);
        //die();
        return $this->render('home/product.html.twig', [
            'data' => $data,
            'products' => $products,
            'cats' => $cats,
        ]);
    }
    /**
     * @Route("/product/{id}", name="product_detail", methods="GET")
     */
    public function ProductDetail($id, ProductRepository $productRepository, ImageRepository $imageRepository)
    {
        $data = $productRepository->findBy(
            ['id' => $id]
        );
        $images = $imageRepository->findBy(
            ['product_id' => $id]
        );

        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v">';

        return $this->render('home/productdetail.html.twig' , [
            'data'=> $data,
            'cats'=> $cats,
            'images' => $images,
        ]);
    }
    /**
     * @Route("/newuser", name="new_user", methods="GET|POST")
     */
    public function newuser(Request $request, UserRepository $userRepository, SettingRepository $settingRepository): Response
    {
        $data = $settingRepository->findAll();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('user-form', $submittedToken)) {
            if ($form->isSubmitted()) {

               $emaildata = $userRepository->findBy(
                   ['email' => $user->getEmail()
                   ]);

               if ($emaildata == null) {

                $em = $this->getDoctrine()->getManager();
                $user->setRoles("ROLE_USER");
                $user->setStatus("False");
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Üye Kayıt Edildi.');

                return $this->redirectToRoute('app_login');
                }
                else {
                    $this->addFlash('error', $user->getEmail()."Bu Email Zaten Kayıtlı!!!");

                }

            }
        }
        return $this->render('home/newuser.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'data' => $data,

        ]);
    }


}
