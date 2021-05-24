<?php


namespace Vocalizr\AppBundle\Entity\Revenue;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\StripeProductInvoiceRepository")
 */
class StripeProductInvoice
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $product_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $name;

    /**
     * @ORM\Column(type="integer")
     */
    public $amount;

    /**
     * @ORM\Column(type="boolean")
     */
    public $is_refund;

    /**
     * @ORM\ManyToOne(targetEntity="StripeInvoice", inversedBy="products")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    public $stripe_invoice;

    /**
     * @ORM\Column(type="datetime")
     */
    public $created_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new DateTime();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @param string $product_id
     * @return StripeProductInvoice
     */
    public function setProductId($product_id)
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param integer $amount
     * @return StripeProductInvoice
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsRefund()
    {
        return $this->is_refund;
    }

    /**
     * @param boolean $is_refund
     * @return StripeProductInvoice
     */
    public function setIsRefund($is_refund)
    {
        $this->is_refund = $is_refund;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     * @return StripeProductInvoice
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return StripeProductInvoice
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return StripeInvoice
     */
    public function getStripeInvoice()
    {
        return $this->stripe_invoice;
    }

    /**
     * @param StripeInvoice $stripeInvoice
     * @return StripeProductInvoice
     */
    public function setStripeInvoice($stripeInvoice)
    {
        $this->stripe_invoice = $stripeInvoice;
        return $this;
    }

}