<?php

namespace App\Model;

use App\Entity\ProjectBid;
use App\Entity\UserBlock;
use App\Entity\VocalizrActivity;
use Doctrine\ORM\OptimisticLockException;
use claviska\SimpleImage;
use Exception;
use App\Entity\Project;
use App\Entity\StripeCharge;
use App\Entity\UserConnectInvite;
use App\Entity\UserInfo;
use App\Entity\UserReview;
use App\Entity\UserSubscription;
use App\Entity\UserVocalCharacteristic;
use App\Entity\UserVocalStyle;
use App\Entity\UserVoiceTag;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;
use App\Exception\UnsubscribeException;
use App\Repository\UserVocalCharacteristicRepository;
use App\Repository\UserVocalStyleRepository;
use App\Repository\UserVoiceTagRepository;

/**
 * Class UserInfoModel
 *
 * @package App\Model
 */
class UserInfoModel extends Model
{
    /**
     * @param UserInfo $fromUser
     * @param UserInfo $toUser
     *
     * @return UserVoiceTag[]|UserVocalStyle[]|UserVocalCharacteristic[]
     */
    public function getTags(UserInfo $toUser, UserInfo $fromUser = null)
    {
        $userTags = [];

        $entityAliases = [
            'voiceTags'            => UserVoiceTag::class,
            'vocalStyles'          => UserVocalStyle::class,
            'vocalCharacteristics' => UserVocalCharacteristic::class,
        ];

        foreach ($entityAliases as $key => $alias) {
            if ($toUser->getIsVocalist()) {
                /** @var UserVoiceTagRepository|UserVocalStyleRepository|UserVocalCharacteristicRepository $repository */
                $repository     = $this->em->getRepository($alias);
                $userTags[$key] = $repository->getByUserJoinVotedUser($toUser->getId(), $fromUser ? $fromUser->getId() : null);
            } else {
                $userTags[$key] = [];
            }
        }

        return $userTags;
    }

    /**
     * @param UserInfo $user
     */
    public function recalculateUserRating(UserInfo $user)
    {
        $vocalistRatesSum     = 0;
        $producerRatesSum     = 0;
        $employerRatesSum     = 0;
        $employerReviewsCount = 0;
        $vocalistReviewsCount = 0;
        $producerReviewsCount = 0;

        foreach ($user->getUserReviews() as $review) {
            if (!$project = $review->getProject()) {
                continue;
            }
            if ($project->getUserInfo() === $user) {
                // Do not include reviews to producer or vocalist stats if user is job owner.
                $employerRatesSum += $review->getRating();
                $employerReviewsCount++;
                $review->setType(UserReview::REVIEW_TYPE_EMPLOYER);
            } elseif ($project->getLookingFor() === Project::LOOKING_FOR_PRODUCER) {
                $producerRatesSum += $review->getRating();
                $producerReviewsCount++;
                $review->setType(UserReview::REVIEW_TYPE_PRODUCER);
            } else {
                $vocalistRatesSum += $review->getRating();
                $vocalistReviewsCount++;
                $review->setType(UserReview::REVIEW_TYPE_VOCALIST);
            }
        }

        $ratedCount    = $vocalistReviewsCount + $producerReviewsCount + $employerReviewsCount;
        $ratesTotalSum = $vocalistRatesSum + $producerRatesSum + $employerRatesSum;

        if ($ratedCount > 0) {
            $ratingAvg = $ratesTotalSum / $ratedCount;
        } else {
            $ratingAvg = 0;
        }

        $user
            ->setRatingTotal($ratesTotalSum)
            ->setRatedCount($ratedCount)
            ->setRating((float)number_format($ratingAvg, 1))

            ->setVocalistRatedCount($vocalistReviewsCount)
            ->setProducerRatedCount($producerReviewsCount)
            ->setEmployerRatedCount($employerReviewsCount)
        ;

        if ($vocalistReviewsCount > 0) {
            $vocalistRating = (float)number_format($vocalistRatesSum / $vocalistReviewsCount, 1);
        } else {
            $vocalistRating = 0;
        }
        if ($producerReviewsCount > 0) {
            $producerRating = (float)number_format($producerRatesSum / $producerReviewsCount, 1);
        } else {
            $producerRating = 0;
        }
        if ($employerReviewsCount > 0) {
            $employerRating = (float)number_format($employerRatesSum / $employerReviewsCount, 1);
        } else {
            $employerRating = 0;
        }

        $user
            ->setVocalistRating($vocalistRating)
            ->setProducerRating($producerRating)
            ->setEmployerRating($employerRating)
        ;
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return UserWalletTransaction|null
     */
    public function createWalletTransactionFromWithdraw(UserWithdraw $withdraw)
    {
        return $this->container->get('vocalizr_app.model.wallet_transaction')
            ->createFromWithdraw($withdraw);
    }

    /**
     * @deprecated
     *
     * @param UserInfo $user
     * @param float $operationAmountCents
     * @param string|null $type
     * @param string|null $description
     * @param array $data
     *
     * @return UserWalletTransaction
     */
    public function createWalletTransaction(UserInfo $user, $operationAmountCents, $type = null, $description = null, $data = [])
    {
        return $this->container->get('vocalizr_app.model.wallet_transaction')
            ->create($user, $operationAmountCents, $type, $description, $data);
    }

    /**
     * @param UserInfo $user
     * @param string $id
     * @param bool $isStripe
     * @param StripeCharge|null $charge
     * @throws Exception
     */
    public function addSubscription(UserInfo $user, $id, $isStripe = true, $charge = null)
    {
        $this->getSubscriptionModel()->addSubscription($user, $id, $isStripe, $charge);
    }

    /**
     * @param UserSubscription $subscription
     * @param array $subscriptionObject
     * @throws OptimisticLockException
     */
    public function updateStripeSubscription(UserSubscription $subscription, $subscriptionObject)
    {
        $this->getSubscriptionModel()->updateStripeSubscription($subscription, $subscriptionObject);
    }


    /**
     * @param UserInfo $userInfo
     */
    public function deactivate(UserInfo $userInfo)
    {
        $this->unsubscribe($userInfo);

        $this->em->getRepository(VocalizrActivity::class)->deleteUserActivity($userInfo);

        $userInfo->setIsActive(false);

        $userInfo
            ->setStripeCustId('')
            ->setSubscriptionPlan(null)
        ;

        $this->em->flush();

        $projectBidRepository = $this->em->getRepository(ProjectBid::class);

        $projectBidRepository->deleteNotAwardedUserBids($userInfo);
        $projectBidRepository->markUserBidsDeleted($userInfo);
    }

    /**
     * @param UserInfo $userInfo
     * @param bool $throwException
     * @param bool $atPeriodEnd
     * @throws UnsubscribeException
     */
    public function unsubscribe(UserInfo $userInfo, $throwException = false, $atPeriodEnd = false)
    {
        $this->getSubscriptionModel()->unsubscribe($userInfo, $throwException, $atPeriodEnd);
    }

    /**
     * @param UserInfo $me
     * @param UserInfo $otherUser
     * @return bool
     */
    public function isInFavorites(UserInfo $me, UserInfo $otherUser)
    {
        return $this->repository->isUserFavorite($me->getId(), $otherUser->getId());
    }

    /**
     * @param UserInfo $user1
     * @param UserInfo $user2
     * @return null|UserConnectInvite
     */
    public function getConnectionInviteBetweenUsers(UserInfo $user1, UserInfo $user2)
    {
        return $this->container->get('vocalizr_app.model.user_connect')->getConnectionInviteBetweenUsers($user1, $user2);
    }

    /**
     * @param UserInfo $user
     * @return int
     */
    public function getReviewsCount(UserInfo $user)
    {
        return $this->em->getRepository(UserReview::class)->getUserReviewsCount($user);
    }

    /**
     * @param UserInfo $me
     * @param UserInfo $otherUser
     * @return bool
     */
    public function isUserBlocked(UserInfo $me, UserInfo $otherUser)
    {
        return $this->em->getRepository(UserBlock::class)
            ->isUserBlocked($me->getId(), $otherUser->getId());
    }

    /**
     * @param UserInfo $user
     * @throws Exception
     */
    public function generateThumbnails(UserInfo $user)
    {
        $filename = $user->getPath();

        if (!$filename) {
            return;
        }

        $uploadDir = $user->getUploadRootDir();

        $filepath = $uploadDir . $filename;

        if (!file_exists($filepath)) {
            error_log('Source file for thumbnail is not found: ' . $filepath);
        }

        // Profile view (162px)
        $this->generateThumbnail($filepath, $uploadDir . 'large/' . $filename,  324);
        // Bid and search view (80px)
        $this->generateThumbnail($filepath, $uploadDir . 'medium/' . $filename,  160);
        // Activity feed, messaging, notifications (34-58px)
        $this->generateThumbnail($filepath, $uploadDir . 'small/' . $filename,  80);
    }

    /**
     * @param string $inputFile
     * @param string $outputFile
     * @param int $size
     * @param array $parameters
     * @throws Exception
     */
    private function generateThumbnail($inputFile, $outputFile, $size, $parameters = [])
    {
        $rotateAngle = 0;

        $isPng = (substr($inputFile, -4) === '.png');

        if (!$isPng && !isset($parameters['disable_rotation'])) {
            $exif = exif_read_data($inputFile);
            if (is_array($exif) && array_key_exists('Orientation', $exif) && in_array($orientation = (int) $exif['Orientation'], [3, 6, 8])) {
                switch ($orientation) {
                    case 3:
                        $rotateAngle = 180;
                        break;
                    case 6:
                        $rotateAngle = 90;
                        break;
                    case 8:
                        $rotateAngle = -90;
                        break;

                }
            }
        }

        if ($isPng) {
            $quality = isset($parameters['png_quality']) ? $parameters['png_quality'] : 9;
        } else {
            $quality = isset($parameters['jpg_quality']) ? $parameters['jpg_quality'] : 85;
        }

        try {
            $image = new \claviska\SimpleImage();
            // Magic! ✨
            $image->fromFile($inputFile);

            if ($rotateAngle) {
                $image->rotate($rotateAngle);
            }

            $image->resize($size, $size);                       // resize to SIZE X SIZE pixels
            $image->toFile($outputFile, 'image/png');  // convert to PNG and save a copy to new-image.png
        } catch (Exception $exception) {
            error_log('Could not create thumbnail ' . $outputFile . ': ' . $exception->getMessage());
            if (isset($parameters['throw'])) {
                throw $exception;
            }
        }
    }

    /**
     * @return UserSubscriptionModel
     */
    private function getSubscriptionModel()
    {
        return $this->container->get('vocalizr_app.model.user_subscription');
    }

    protected function getEntityName()
    {
        return UserInfo::class;
    }
}