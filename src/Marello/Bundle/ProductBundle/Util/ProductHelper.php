<?php

namespace Marello\Bundle\ProductBundle\Util;

use Doctrine\Common\Persistence\ObjectManager;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\SalesBundle\Entity\SalesChannel;

class ProductHelper
{
    /** @var ObjectManager $manager */
    protected $manager;

    /** @var LocaleSettings $localeSettings */
    protected $localeSettings;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns ids of all related sales channels for a product.
     *
     * @param Product $product
     *
     * @return array $ids
     */
    public function getSalesChannelsIds(Product $product)
    {
        $ids = [];
        $product
            ->getChannels()
            ->map(function (SalesChannel $channel) use (&$ids) {
                $ids[] = $channel->getId();
        });

        return $ids;
    }

    /**
     * Returns ids of all sales channels which are not in related to a product.
     *
     * @param Product $product
     *
     * @return array $ids
     */
    public function getExcludedSalesChannelsIds(Product $product)
    {
        $relatedIds = $this->getSalesChannelsIds($product);
        $excludedIds = [];

        $ids = $this->manager
            ->getRepository(SalesChannel::class)
            ->createQueryBuilder('sc')
            ->select('sc.id')
            ->where('sc.id NOT IN(:channels)')
            ->setParameter('channels', $relatedIds)
            ->getQuery()
            ->getArrayResult();

        foreach ($ids as $k => $v) {
            $excludedIds[] = $v['id'];
        }

        return $excludedIds;
    }
}
