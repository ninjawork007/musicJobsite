<?php


namespace Vocalizr\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminEmailController extends Controller
{

    /**
     * @Route("/admin/email", name="admin_email")
     *
     * @param Request $request
     * @return Response
     */
    public function adminEmail(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('@VocalizrApp/Admin/user_email.html.twig');
    }

    /**
     * @Route("/admin/email/vocalists", name="admin_email_vocalists")
     *
     * @param Request $request
     * @return Response
     */
    public function adminEmailVocalists(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $users = $this->getDoctrine()->getRepository('VocalizrAppBundle:UserInfo')->findVocalistsEmail();
        $rows = [];
        foreach ($users as $user) {
            $rows[] = $user['email'];
        }

        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=vocalists.csv');

        return $response;
    }

    /**
     * @Route("/admin/email/producers", name="admin_email_producers")
     *
     * @param Request $request
     * @return Response
     */
    public function adminEmailProducers(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $users = $this->getDoctrine()->getRepository('VocalizrAppBundle:UserInfo')->findProducersEmail();
        $rows = [];
        foreach ($users as $user) {
            $rows[] = $user['email'];
        }

        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=producers.csv');

        return $response;
    }


    /**
     * @Route("/admin/email/all-users", name="admin_email_all_users")
     *
     * @param Request $request
     * @return Response
     */
    public function adminEmailAllUsers(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $users = $this->getDoctrine()->getRepository('VocalizrAppBundle:UserInfo')->findAllUsersEmail();
        $rows = [];
        foreach ($users as $user) {
            $rows[] = $user['email'];
        }

        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=all-users.csv');

        return $response;
    }


}