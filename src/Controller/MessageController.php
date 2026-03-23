<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $inbox = $messageRepository->findInboxForUser($user);
        $unreadCount = $messageRepository->countUnreadForUser($user);

        return $this->render('admin/message/index.html.twig', [
            'messages' => $inbox,
            'unreadCount' => $unreadCount,
            'activeTab' => 'inbox',
        ]);
    }

    #[Route('/sent', name: 'app_message_sent', methods: ['GET'])]
    public function sent(MessageRepository $messageRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $sent = $messageRepository->findSentForUser($user);

        return $this->render('admin/message/index.html.twig', [
            'messages' => $sent,
            'unreadCount' => $messageRepository->countUnreadForUser($user),
            'activeTab' => 'sent',
        ]);
    }

    #[Route('/compose', name: 'app_message_compose', methods: ['GET', 'POST'])]
    public function compose(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $message = new Message();
        $message->setSender($user);
        
        // Handle reply parameter
        $replyTo = $request->query->getInt('reply');
        $replyMessage = null;
        if ($replyTo > 0) {
            $replyUser = $userRepository->find($replyTo);
            if ($replyUser) {
                $message->setRecipient($replyUser);
            }
        }
        
        $form = $this->createForm(MessageType::class, $message, [
            'current_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'message', 'Message to ' . $message->getRecipient()->getFullName(), $message->getId());

            $this->addFlash('success', 'Message sent successfully!');
            return $this->redirectToRoute('app_message_sent', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/message/compose.html.twig', [
            'message' => $message,
            'form' => $form,
            'replyTo' => $replyTo,
        ]);
    }

    #[Route('/{id}', name: 'app_message_show', methods: ['GET'])]
    public function show(Message $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is sender or recipient
        if ($message->getSender()->getId() !== $user->getId() && $message->getRecipient()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to view this message.');
            return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
        }

        // Mark as read if user is the recipient
        if ($message->getRecipient()->getId() === $user->getId() && !$message->isRead()) {
            $message->setIsRead(true);
            $entityManager->flush();
        }

        return $this->render('admin/message/show.html.twig', [
            'message' => $message,
            'isRecipient' => $message->getRecipient()->getId() === $user->getId(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is sender or recipient
        if ($message->getSender()->getId() !== $user->getId() && $message->getRecipient()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to delete this message.');
            return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $message->getId(), $request->request->getString('_token'))) {
            $messageId = $message->getId();
            $entityManager->remove($message);
            $entityManager->flush();

            $activityLogger->log('DELETE', 'message', 'Message deleted', $messageId);
            $this->addFlash('success', 'Message deleted successfully.');
        }

        return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
    }
}

