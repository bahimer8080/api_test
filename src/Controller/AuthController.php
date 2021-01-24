<?php

namespace App\Controller;

 use App\Entity\User;
 use App\Repository\UserRepository;
 use Doctrine\ORM\EntityManagerInterface;
 use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
 use Symfony\Component\HttpFoundation\JsonResponse;
 use Symfony\Component\HttpFoundation\Request;
 use Symfony\Component\Routing\Annotation\Route;
 use App\Repository\PostsRepository;
 use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
 use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    /**
     * @Route("/register",methods={"POST","HEAD"})
     */
    public function register(Request $request, 
    EntityManagerInterface $em,
    UserRepository $User,
    UserPasswordEncoderInterface $encoder
    ){
        $response = new JsonResponse();
        $user = $User->findBy([
            "email" => $request->toArray()["username"]
        ]);
        $userReq = $request->toArray();
        if( !empty( $user ) && $user[0]->getEmail() == $userReq["username"] ){
            return $response->setData( ["message" => "USUARIO REGISTRADO ANTERIORMENTE" , "state" => false ] );
        } else {
            $newUser = new User();
            $plainPassword = $userReq["password"];
            $encoded = $encoder->encodePassword(new User(), $plainPassword);
            $newUser->setEmail( $userReq["username"] );
            $newUser->setRoles(["ROLE_USER"]);
            $newUser->setPassword($encoded );
            $em->persist($newUser);
            $em->flush();
            return $response->setData( ["message" => "USUARIO REGISTRADO EXITOZAMENTE" , "state" => true ] );
        }
    }
}