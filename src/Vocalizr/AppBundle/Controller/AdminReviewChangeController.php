<?php


namespace Vocalizr\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vocalizr\AppBundle\Model\UserInfoModel;

class AdminReviewChangeController extends Controller
{

    /**
     * @Route("/admin/review_change", name="admin_review_change")
     *
     * @param Request $request
     * @return Response
     */
    public function adminReviewChange(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('@VocalizrApp/Admin/user_review_change.html.twig');
    }


    /**
     * @Route("/admin/review_change/list", name="admin_review_change_list")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminReviewChangeList(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $searchTerm = trim($request->get('search-term'));

        if ($searchTerm == '') {

            $responseData = [
                'success' => false,
                'message' => 'No search string defined'
            ];

            return new JsonResponse($responseData);
        }

        $users = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserInfo')
            ->findUser($searchTerm, ['email', 'username', 'display_name'], false, ['review']);


        return new JsonResponse([
            'success'    => true,
            'numResults' => count($users),
            'html'       => $this->renderView(
                'VocalizrAppBundle:Admin:adminReviewUserResults.html.twig',
                ['results' => $users]
            ),
        ]);
    }

    /**
     * @Route("/admin/review_change/change", name="admin_review_change_change")
     * @param Request $request
     * @return JsonResponse
     */
    public function adminReviewChangeChange(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $userReviewId = $request->get('id');
        if (!$userReviewId) {
            throw new BadRequestHttpException('Field id is not specified');
        }

        $em = $this->getDoctrine()->getManager();

        $userReview = $em->getRepository('VocalizrAppBundle:UserReview')->find($userReviewId);
        if (!$userReview) {
            throw new BadRequestHttpException('Review not found');
        }

        $validator = $this->get('validator');
        $violations = $validator->validateValue($review = $request->get('review'), [
            new NotBlank(),
        ]);

        if ($violations->count() > 0) {
            throw new BadRequestHttpException('Entered review is not valid.');
        }

        $userReview->setContent($review);

        $em->persist($userReview);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/admin/review_change/delete", name="admin_review_change_delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function adminReviewDelete(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $userReviewId = $request->get('id');
        if (!$userReviewId) {
            throw new BadRequestHttpException('Field id is not specified');
        }

        $em = $this->getDoctrine()->getManager();

        $userReview = $em->getRepository('VocalizrAppBundle:UserReview')->find($userReviewId);
        if (!$userReview) {
            throw new BadRequestHttpException('Review not found');
        }
        $reviewedUser = $userReview->getReviewedBy();
        $em->remove($userReview);

        $userModel = new UserInfoModel();
        $userModel->recalculateUserRating($reviewedUser);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}