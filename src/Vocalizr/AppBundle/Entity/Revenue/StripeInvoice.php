<?php


namespace Vocalizr\AppBundle\Entity\Revenue;


use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\StripeInvoiceRepository")
 */
class StripeInvoice
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $fee;

    /**
     * @ORM\Column(type="integer")
     */
    public $amount;

    /**
     * @ORM\Column(type="datetime")
     */
    public $date_create_invoice;

    /**
     * @ORM\Column(type="text")
     */
    public $invoice_id;

    /**
     * @ORM\Column(type="boolean")
     */
    public $is_refund;

    /**
     * @ORM\OneToMany(targetEntity="StripeProductInvoice", mappedBy="stripe_invoice")
     */
    public $products;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $charge_id;

    /**
     * @ORM\Column(type="datetime")
     */
    public $created_at;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

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
     * @return integer
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param integer $fee
     * @return StripeInvoice
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

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
     * @return StripeInvoice
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreateInvoice()
    {
        return $this->date_create_invoice;
    }

    /**
     * @param DateTime $date_create_invoice
     * @return StripeInvoice
     */
    public function setDateCreateInvoice($date_create_invoice)
    {
        $this->date_create_invoice = $date_create_invoice;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * @param string $invoice_id
     * @return StripeInvoice
     */
    public function setInvoiceId($invoice_id)
    {
        $this->invoice_id = $invoice_id;

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
     * @return StripeInvoice
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
     * @return StripeInvoice
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return string
     */
    public function getChargeId()
    {
        return $this->charge_id;
    }

    /**
     * @param string $charge_id
     * @return StripeInvoice
     */
    public function setChargeId($charge_id)
    {
        $this->charge_id = $charge_id;

        return $this;
    }

    /**
     * @param StripeProductInvoice $product
     * @return StripeInvoice
     */
    public function addProduct(StripeProductInvoice $product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setStripeInvoice($this);
        }
        return $this;
    }

    /**
     * @param StripeProductInvoice $product
     */
    public function removeProduct(StripeProductInvoice $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * @return Collection|StripeProductInvoice[]
     */
    public function getProduct()
    {
        return $this->products;
    }

}