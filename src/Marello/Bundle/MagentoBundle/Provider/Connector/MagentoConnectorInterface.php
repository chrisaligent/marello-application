<?php

namespace Marello\Bundle\MagentoBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Interface MagentoConnectorInterface
 *
 * @package Marello\Bundle\MagentoBundle\Provider
 * This interface should be implemented by magento related connectors
 * Contains just general constants
 */
interface MagentoConnectorInterface extends ConnectorInterface
{
    const STORE_TYPE                      = 'Marello\\Bundle\\MagentoBundle\\Entity\\Store';
    const WEBSITE_TYPE                    = 'Marello\\Bundle\\MagentoBundle\\Entity\\Website';

    const MAGENTO_PRODUCT_TYPE            = 'Marello\\Bundle\\MagentoBundle\\Entity\\Product';
    const MARELLO_PRODUCT_TYPE            = 'Marello\\Bundle\\ProductBundle\\Entity\\Product';
}
