<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
//use Doctrine\RegistryInterface as Doctrine;
use Doctrine\ORM\EntityManager;
use Swift_Message;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\UserInfo;

class MailService
{
    /** @var  */
    private $mailer;

    /** @var Container */
    private $container;

    /** @var Swift_Message */
    private $message;

    /** @var EntityManager */
    private $em;

    /** @var TwigEngine */
    private $templating;

    /**
     * MailService constructor.
     *
     * @param Doctrine           $doctrine
     * @param ContainerInterface $container
     * @param TwigEngine         $templating
     */
    public function __construct($doctrine, $container, $templating)
    {
        $this->em         = $doctrine->getEntityManager();
        $this->container  = $container;
        $this->mailer     = $container->get('mailer');
        $this->message    = Swift_Message::newInstance();
        $this->templating = $templating;

        // Setup defaults for message object
        $this->message->setFrom($container->getParameter('mail_from_email'), $container->getParameter('mail_from_name'));
    }

    public function send()
    {
        $this->mailer->send($this->message);
    }

    public function flushQueue()
    {
        $transport = $this->mailer->getTransport();
        if (!$transport instanceof \Swift_Transport_SpoolTransport) {
            return;
        }
        $spool = $transport->getSpool();
        if (!$spool instanceof \Swift_MemorySpool) {
            return;
        }

        $spool->flushQueue($this->container->get('swiftmailer.transport.real'));
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send activation email to confirm their email address is valid
     *
     * Required:
     * $vars = array('userInfo' => $userInfo);
     *
     * @param array $vars Include UserInfo Entity in array
     */
    public function sendRegisterActivate($vars = [])
    {
        $this->message->setSubject($this->container->getParameter('mail_subject_activation'))
                ->setTo($vars['userInfo']->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:registerActivate.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:registerActivate.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send reset password link
     *
     * Required:
     * $vars = array('userInfo' => $userInfo);
     *
     * @param array $vars
     */
    public function sendResetPassword($vars = [])
    {
        $this->message->setSubject($this->container->getParameter('mail_subject_reset_pass'))
                ->setTo($vars['userInfo']->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:resetPass.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:resetPass.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send email change request
     *
     * Required:
     * $vars = array(
     *              'user' => $user, // UserInfo Entity
     *              'emailRequest' => $emailRequest // EmailChangeRequest Entity
     *         );
     *
     * @param array $vars
     */
    public function changeEmailRequest($vars = [])
    {
        $this->message->setSubject($this->container->getParameter('mail_subject_change_email'))
                ->setTo($vars['emailRequest']->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:changeEmail.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:changeEmail.txt.twig', $vars), 'text/plain')
        ;

        $this->send($this->message);
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send notification to bidder that the project has been awarded to them and
     * prompt them to accept it
     *
     * @param array $vars
     */
    public function sendProjectAwardedNotification($vars = [])
    {
        $emailTo = $vars['projectBid']->getUserInfo()->getEmail();

        $this->message->setSubject('Congratulations! You have been awarded a gig')
                ->setTo($emailTo)
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectAwardedNotification.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectAwardedNotification.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     * Send notification to project owner that user responded
     *
     * @param array $vars
     */
    public function sendProjectBidResponseToOwner($vars = [])
    {
        $projectBid = $vars['projectBid'];
        $owner      = $projectBid->getProject()->getUserInfo();
        $user       = $projectBid->getUserInfo();
        $action     = $vars['action'];

        $this->message->setSubject($user->getUsername() . ' ' . $action . ' your gig')
                ->setTo($owner->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectBidResponseToOwner.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectBidResponseToOwner.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send notification to project bidder (winner) to prompt them
     * to upload their assets
     *
     * Required:
     * $vars = array(
     *      'user' => $user, // UserInfo Entity of other party
     *      'project' => $project, // Project Entity
     * );
     *
     * @param array $vars
     */
    public function sendProjectPromptAssets($vars = [])
    {
        $this->message->setSubject($vars['project']->getUserInfo()->getUsername() . ' has asked for assets')
                ->setTo($vars['user']->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectPromptAssets.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectPromptAssets.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send email to other party notifying them files have been uploaded
     *
     * Required:
     * $vars = array(
     * 'from' => $userInfo, // User Info of uploader
     * 'to' => $userInfo, = $this->project>getUserInfo(), // UserInfo Entity of who email is going to
     * 'count' => $fileCount, // Count of how many files were uploaded
     * 'assets' => $projectAssetsArray, // Array of assets
     * 'project' => $this->project, // Project Entity
     * );
     *
     * @param array $vars
     */
    public function sendProjectUploadedAssetsNotification($vars = [])
    {
        extract($vars);
        $this->message->setSubject($from->getUsername() . ' has uploaded assets for gig ' . $project->getTitle())
                ->setTo($to->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectUploadedAssetsNotification.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectUploadedAssetsNotification.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send email to other party notifying them payment escrow has been released
     *
     * Required:
     * $vars = array(
     * 'user' => $user, // User Entity
     *      'project -> $project, // Project Entity
     * );
     *
     * @param array $vars
     */
    public function sendProjectPaymentRelease($vars = [])
    {
        extract($vars);
        $this->message->setSubject($project->getUserInfo()->getUsername() . ' has released your payment')
                ->setTo($user->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectPaymentRelease.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectPaymentRelease.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }

    /**
     * ADDED TO MANDRILL
     *
     * Send to notify user of response of dispute
     *
     * @param array $vars
     */
    public function sendDisputeResponse($vars = [])
    {
        extract($vars);
        $this->message->setSubject($dispute->getUserInfo()->getUsername() . ' responded to your dispute')
                ->setTo($dispute->getFromUserInfo()->getEmail())
                ->setBody($this->templating->render('VocalizrAppBundle:Mail:projectDisputeResponse.html.twig', $vars), 'text/html')
                ->addPart($this->templating->render('VocalizrAppBundle:Mail:projectDisputeResponse.txt.twig', $vars), 'text/plain')
        ;

        $this->send();
    }
}