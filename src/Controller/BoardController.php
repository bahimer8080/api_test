<?php

namespace App\Controller;

 use App\Entity\Board;
 use App\Entity\Member;
 use App\Entity\User;
 use App\Repository\UserRepository;
 use Doctrine\ORM\EntityManagerInterface;
 use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
 use Symfony\Component\HttpFoundation\JsonResponse;
 use Symfony\Component\HttpFoundation\Request;
 use Symfony\Component\Routing\Annotation\Route;
 use App\Repository\BoardRepository;
 use App\Repository\MemberRepository;
 use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
 use Symfony\Component\Security\Core\User\UserInterface;

/**
* @Route("/api")
*/

class BoardController extends AbstractController
{
    /**
     * @Route("/board",methods={"GET","HEAD"})
     */
    public function index(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $member = $Member->findBy([
            "user" => $userId,
            "role" => 1
        ]);

        $memberArray = [];
        foreach($member as $m){
            $memberArray[] = [
                "id" => $m->getBoard()->getId(),
                "name" => $m->getBoard()->getName()
            ];
        }

        $response = new JsonResponse();
        return $response->setData($memberArray );
    }


    /**
     * @Route("/board/{id}",methods={"GET","HEAD"})
     */
    public function show(int $id,Request $request, 
        EntityManagerInterface $em,
        BoardRepository $Board,
        MemberRepository $Member,
        UserInterface $userToken,
        UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $board = $Member->findBy([
            "id" => $id,//$request->query->get("id"),
            "user" => $userId
        ]);
        $response = new JsonResponse();
        if( count( $board ) > 0 ){
            $boardArray = [
                "id" => $board[0]->getBoard()->getId(),
                "name" => $board[0]->getBoard()->getName()
            ];
            return $response->setData($boardArray);
        } else {
            return $response->setData([ "message" => "BOARD NOT FOUND", "state" => false ]);
        }
    }

    /**
     * @Route("/board",methods={"POST","HEAD"})
     */
    public function store(Request $request, 
        EntityManagerInterface $em,
        BoardRepository $Board,
        MemberRepository $Member,
        UserInterface $userToken,
        UserPasswordEncoderInterface $encoder){
            $board = new Board();
            $board->setName( $request->toArray()["name"] );
            $board->setState(1);
            $em->persist($board);
            $em->flush();

            $userId = $userToken->getId();
            $member = new Member();
            $member->setUser($userToken);
            $member->setBoard( $board );
            $member->setRole(1);
            $em->persist($member);
            $em->flush();

            $response = new JsonResponse();
            return $response->setData( ["message" => "TASK CREATED SUCCESSFULLY" , 
                "state" => true ] );
        }

    /**
     * @Route("/board/{id}",methods={"PUT"})
     */
    public function edit(int $id,Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $board = $Member->findBy([
            "board" => $id,
            "user" => $userId
        ]);

        $response = new JsonResponse();
        if(count($board) > 0){
            $entityManager = $this->getDoctrine()->getManager();
            $boardUpd = $entityManager->getRepository(Board::class)->find($board[0]->getBoard()->getId());

            if (!$boardUpd) {
                return $response->setData([ "BOARD NOT UPDATED", "status" => false ]);
            }

            $boardUpd->setName($request->toArray()["name"]);
            $entityManager->flush();
            return $response->setData([ "BOARD UPDATED SUCCESSFULLY", "status" => true ]);
        } else {
            
            return $response->setData([ "BOARD NOT FOUND", "status" => true ]);
        }

        

        
    }

    /**
     * @Route("/board/{id}",methods={"DELETE","HEAD"})
     */
    public function delete(int $id,Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $board = $Member->findBy([
            "board" => $id,
            "user" => $userId
        ]);


        $response = new JsonResponse();
        if(count( $board ) > 0){
            $entityManager = $this->getDoctrine()->getManager();
            $boardUpd = $entityManager->getRepository(Board::class)->find($board[0]->getBoard()->getId());

            if (!$boardUpd) {
                return $response->setData([ "BOARD NOT DELETED", "status" => false ]);
            }

            $entityManager->remove($boardUpd);
            $entityManager->flush();
            return $response->setData([ "BOARD DELETE SUCCESSFULLY", "status" => true ]);
        } else {
            return $response->setData([ "BOARD NOT FOUND", "status" => false ]);
        }

        

        
    }

    /**
     * @Route("/assignUserToBoard",methods={"POST","HEAD"})
     */
    public function assignUserToBoard(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserRepository $User,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $board = $Member->findBy([
            "board" => $request->query->get("id"),
            "user" => $userId
        ]);
        $response = new JsonResponse();
        if( count($board) > 0 ){
            $memberUser = $Member->findBy([
                "board" => $request->query->get("id"),
                "user" => $request->query->get("user_id")
            ]);
            if( count($memberUser) == 0 ){
                $b = $Board->find($request->query->get("id")); 
                $u = $User->find($request->query->get("user_id"));
                $member = new Member();
                $member->setBoard($b);
                $member->setUser($u);
                $member->setRole(0);
                $em->persist($member);
                $em->flush();
                return $response->setData([ "status" => 1 ]);
            } else {
                return $response->setData([ "PREVIUSLY ASSIGNED USER", "status" => false ]);
            }
        } else {
            return $response->setData([ "BOARD NOT FOUND", "status" => false ]);
        }
    }

}