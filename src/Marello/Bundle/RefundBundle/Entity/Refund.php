<?php

namespace Marello\Bundle\RefundBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Marello\Bundle\CoreBundle\Model\LocaleTrait;
use Marello\Bundle\CoreBundle\Model\EntityCreatedUpdatedAtTrait;
use Marello\Bundle\OrderBundle\Entity\Customer;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Marello\Bundle\PricingBundle\Model\CurrencyAwareInterface;
use Marello\Bundle\RefundBundle\Model\ExtendRefund;
use Marello\Bundle\ReturnBundle\Entity\ReturnEntity;
use Marello\Bundle\ReturnBundle\Entity\ReturnItem;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation as Oro;
use Marello\Bundle\CoreBundle\DerivedProperty\DerivedPropertyAwareInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="marello_refund")
 * @ORM\HasLifecycleCallbacks
 *
 * @Oro\Config(
 *      routeView="marello_refund_view",
 *      routeName="marello_refund_index",
 *      routeCreate="marello_refund_create",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-eur"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "workflow"={
 *              "active_workflow"="marello_refund_workflow"
 *          },
 *          "ownership"={
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class Refund extends ExtendRefund implements DerivedPropertyAwareInterface, CurrencyAwareInterface
{
    use LocaleTrait;
    use EntityCreatedUpdatedAtTrait;
        
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     *
     * @var string
     */
    protected $refundNumber;

    /**
     * @ORM\Column(type="money")
     *
     * @var int
     */
    protected $refundAmount;

    /**
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\OrderBundle\Entity\Customer")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Customer
     */
    protected $customer;

    /**
     * @ORM\OneToMany(targetEntity="RefundItem", mappedBy="refund", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Collection|RefundItem[]
     */
    protected $items;

    /**
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Order
     */
    protected $order;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=10, nullable=true)
     */
    protected $currency;


    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", nullable=false)
     */
    protected $organization;

    /**
     * @var WorkflowItem
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\WorkflowBundle\Entity\WorkflowItem")
     * @ORM\JoinColumn(name="workflow_item_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $workflowItem;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WorkflowBundle\Entity\WorkflowStep")
     * @ORM\JoinColumn(name="workflow_step_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $workflowStep;

    /**
     * @param Order $order
     *
     * @return Refund
     */
    public static function fromOrder(Order $order)
    {
        $refund = new self();

        $refund
            ->setOrder($order)
            ->setCustomer($order->getCustomer())
            ->setOrganization($order->getOrganization())
            ->setCurrency($order->getCurrency())
            ->setLocale($order->getLocale())
        ;

        $order->getItems()->map(
            function (OrderItem $item) use ($refund) {
                $refund->addItem(RefundItem::fromOrderItem($item));
            }
        );

        return $refund;
    }

    /**
     * @param ReturnEntity $return
     *
     * @return Refund
     */
    public static function fromReturn(ReturnEntity $return)
    {
        $refund = new self();

        $refund
            ->setOrder($return->getOrder())
            ->setCustomer($return->getOrder()->getCustomer())
            ->setOrganization($return->getOrganization())
            ->setCurrency($return->getOrder()->getCurrency())
            ->setLocale($return->getOrder()->getLocale())
        ;

        $return->getReturnItems()->map(
            function (ReturnItem $item) use ($refund) {
                $refund->addItem(RefundItem::fromReturnItem($item));
            }
        );

        return $refund;
    }

    /**
     * Refund constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->items = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $sum = array_reduce($this->getItems()->toArray(), function ($carry, RefundItem $item) {
            return $carry + $item->getRefundAmount();
        }, 0);

        $this->setRefundAmount($sum);
    }

    /**
     * @param int $id
     */
    public function setDerivedProperty($id)
    {
        if (!$this->refundNumber) {
            $this->setRefundNumber(sprintf('%09d', $id));
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRefundNumber()
    {
        return $this->refundNumber;
    }

    /**
     * @param string $refundNumber
     *
     * @return Refund
     */
    public function setRefundNumber($refundNumber)
    {
        $this->refundNumber = $refundNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * @param int $refundAmount
     *
     * @return Refund
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return Refund
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection|RefundItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Collection|RefundItem[] $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param RefundItem $item
     *
     * @return $this
     */
    public function addItem(RefundItem $item)
    {
        $this->items->add($item->setRefund($this));

        return $this;
    }

    /**
     * @param RefundItem $item
     *
     * @return $this
     */
    public function removeItem(RefundItem $item)
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Refund
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return Refund
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return Opportunity
     */
    public function setWorkflowItem($workflowItem)
    {
        $this->workflowItem = $workflowItem;

        return $this;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }

    /**
     * @param WorkflowItem $workflowStep
     * @return Opportunity
     */
    public function setWorkflowStep($workflowStep)
    {
        $this->workflowStep = $workflowStep;

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getWorkflowStep()
    {
        return $this->workflowStep;
    }
}
