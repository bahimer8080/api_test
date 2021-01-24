<?php

namespace App\Controller;

 use App\Entity\Board;
 use App\Entity\Member;
 use App\Entity\Task;
 use App\Entity\User;
 use App\Repository\UserRepository;
 use Doctrine\ORM\EntityManagerInterface;
 use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
 use Symfony\Component\HttpFoundation\JsonResponse;
 use Symfony\Component\HttpFoundation\Request;
 use Symfony\Component\Routing\Annotation\Route;
 use App\Repository\BoardRepository;
 use App\Repository\MemberRepository;
 use App\Repository\TaskRepository;
 use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
 use Symfony\Component\Security\Core\User\UserInterface;

/**
* @Route("/api")
*/

class TaskController extends AbstractController
{
    /**
     * @Route("/getMyTask")
     */
    public function getMyTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    TaskRepository $Task,
    UserRepository $User,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $board = $Member->findBy([
            "board" => $request->query->get("board_id"),
            "user" => $userId,
            "role" => 1
        ]);
        $response = new JsonResponse();
        if( count( $board ) > 0 ){
            $tasks = $Task->findBy([ "board" => $request->query->get("board_id") ]);
            $tasksArray = [];
            foreach($tasks as $t){
                $tasksArray[] = [
                    "id" => $t->getId(),
                    "title" => $t->getTitle(),
                    "description"=> $t->getDescription(),
                    "state" => $t->getState()
                ];
            }
            return $response->setData( ["message" => $tasksArray ]);
        } else {
            return $response->setData( ["message" => "BOARD NOT FIND", "status" => false ]);
        }
    }


    /**
     * @Route("/getMyAssignedTask")
     */
    public function getMyAssignedTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    TaskRepository $Task,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $task = $Task->findBy([
            "user" => $userId
        ]);
        $taskArray = [];
        foreach($task as $t){
            $taskArray[] = [
                "id" => $t->getId(),
                "title" => $t->getTitle(),
                "description"=> $t->getDescription(),
                "state" => $t->getState()
            ];
        }
        $response = new JsonResponse();
        return $response->setData( ["message" => $taskArray ]);
    }

    /**
     * @Route("/createTask",methods={"POST","HEAD"})
     */
    public function createTask(Request $request, 
        EntityManagerInterface $em,
        BoardRepository $Board,
        MemberRepository $Member,
        UserInterface $userToken,
        UserPasswordEncoderInterface $encoder
        ){

            $userId = $userToken->getId();
            $board = $Member->findBy([
                "user" => $userId,
                "board" => $request->toArray()["board_id"]
            ]);
            $response = new JsonResponse();
            if( count($board) > 0 ){
                $task = new Task();
                $task->setTitle($request->toArray()["title"]);
                $task->setDescription($request->toArray()["description"]);
                $task->setBoard($board[0]->getBoard());
                $task->setUser($userToken);
                $task->setState(1);
                $em->persist($task);
                $em->flush();
                return $response->setData(["message" => "TASK SUCCESSFULLY" , "status" => true ]);
            } else {
                return $response->setData(["message" => "ACCESS DENIED TO BOARD" , "status" => false ]);
            }
        }

    /**
     * @Route("/updateTask",methods={"PUT","HEAD"})
     */
    public function updateTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    TaskRepository $Task,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $task = $Task->findBy([
            "id" => $request->query->get("task_id")
        ]);
        $response = new JsonResponse();
        if( count( $task ) > 0 ){
            $board = $Member->findBy([
                "board" => $task[0]->getBoard()->getId(),
                "user" => $userId,
                "role" => 1
            ]);
            if( count( $board ) > 0 ){
                $entityManager = $this->getDoctrine()->getManager();
                $tsk = $entityManager->getRepository(Task::class)->find($request->query->get("task_id"));

                if (!$tsk) {
                    return $response->setData([ "TASK NOT UPDATED", "status" => false ]);
                }

                $tsk->setTitle($request->toArray()["title"]);
                $tsk->setDescription($request->toArray()["description"]);
                $entityManager->flush();
                return $response->setData(["message" => "TASK UPDATED SUCCESSFULLY", "status" => true ] );
            }
        } else {
            return $response->setData(["message" => "TASK NOT UPDATED" , "status" => false] );
        }
    }


    /**
    * @Route("/deleteTask",methods={"DELETE","HEAD"})
    */

    public function deleteTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    TaskRepository $Task,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $response = new JsonResponse();
        $userId = $userToken->getId();
        $task = $Task->findBy([
            "id" => $request->query->get("task_id")
        ]);
        
        if( count( $task ) > 0 ){
            $board = $Member->findBy([
                "board" => $task[0]->getBoard()->getId(),
                "user" => $userId,
                "role" => 1
            ]);
            if( count( $board ) > 0 ){
                $entityManager = $this->getDoctrine()->getManager();
                $tsk = $entityManager->getRepository(Task::class)->find($request->query->get("task_id"));

                if (!$tsk) {
                    return $response->setData([ "TASK NOT DELETE", "status" => false ]);
                }

                $tsk->setState(0);
                $entityManager->flush();
                return $response->setData(["message" => "TASK DELETED SUCCESSFULLY", "status" => true ] );
            } else {
                return $response->setData(["message" => "TASK NOT DELETED" , "status" => false] );
            }
        } else {
            return $response->setData(["message" => "TASK NOT DELETED" , "status" => false] );
        }
        return $response->setData(["a" => $task]);
    }

    /**
     * @Route("/assignTask",methods={"PUT","HEAD"})
     */
    public function assignTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserRepository $User,
    TaskRepository $Task,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $task = $Task->findBy([
            "id" => $request->query->get("task_id")
        ]);
        $response = new JsonResponse();
        if( count( $task ) > 0 ){
            $board = $Member->findBy([
                "board" => $task[0]->getBoard()->getId(),
                "user" => $userId,
                "role" => 1
            ]);
            if( count( $board ) > 0 ){
                $entityManager = $this->getDoctrine()->getManager();
                $tsk = $entityManager->getRepository(Task::class)->find($request->query->get("task_id"));

                if (!$tsk) {
                    return $response->setData([ "USER NOT ASSIGNED", "status" => false ]);
                }
                $us = $User->findBy([ "id" => $request->query->get("task_id") ]);
                $tsk->setUser($us[0]);
                $entityManager->flush();
                return $response->setData(["message" => "USER ASSIGNED SUCCESSFULLY", "status" => true ] );
            }
        } else {
            return $response->setData(["message" => "USER NOT ASSIGNED 1" , "status" => false] );
        }
    }

    /**
     * @Route("/changeStateTask",methods={"PUT","HEAD"})
     */
    public function changeStateTask(Request $request, 
    EntityManagerInterface $em,
    BoardRepository $Board,
    MemberRepository $Member,
    UserRepository $User,
    TaskRepository $Task,
    UserInterface $userToken,
    UserPasswordEncoderInterface $encoder){
        $userId = $userToken->getId();
        $task = $Task->findBy([
            "id" => $request->query->get("task_id")
        ]);
        $response = new JsonResponse();
        if( count( $task ) > 0 ){
            $board = $Member->findBy([
                "board" => $task[0]->getBoard()->getId(),
                "user" => $userId
            ]);
            if( count( $board ) > 0 ){
                $entityManager = $this->getDoctrine()->getManager();
                $tsk = $entityManager->getRepository(Task::class)->find($request->query->get("task_id"));

                if (!$tsk) {
                    return $response->setData([ "STATE NOT CHANGE", "status" => false ]);
                }
                $us = $User->findBy([ "id" => $request->query->get("user_id") ]);
                $tsk->setState($request->query->get("state"));
                $entityManager->flush();
                return $response->setData(["message" => "STATE CHANGE SUCCESSFULLY", "status" => true ] );
            }
        } else {
            return $response->setData(["message" => "STATE NOT CHANGE" , "status" => false] );
        }
    }
}