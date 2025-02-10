<?php

namespace BillbeeBricklink\Repository;

use BillbeeBricklink\BricklinkApi\Bricklink;
use BillbeeBricklink\BricklinkApi\ResponseHelper;
use Billbee\CustomShopApi\Model\ShippingProfile;
use Billbee\CustomShopApi\Repository\ShippingProfileRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Exception;

class ShippingProfileRepository implements ShippingProfileRepositoryInterface
{
    private Bricklink $gateway;
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(Bricklink $gateway, LoggerInterface $logger)
    {
        $this->gateway = $gateway;
        $this->client = $this->gateway->client;
        $this->logger = $logger;
    }

    /** @inheritDoc */
    public function getShippingProfiles(): array {
        try {
            // Array to hold shipping profiles transformed into ShippingProfile model
            $my_shipping_profiles = [];

            // Fetch shipping methods data from the Bricklink API
            $bl_shipping_profiles = ResponseHelper::getData($this->client->get("settings/shipping_methods"));

            // Iterate through the fetched data and filter for available methods
            for ($i = 0; $i < count($bl_shipping_profiles); $i++) {
                if ($bl_shipping_profiles[$i]["is_available"]) {
                    // Map API response to the ShippingProfile model
                    $my_shipping_profiles[] = new ShippingProfile(
                        $bl_shipping_profiles[$i]["method_id"],
                        $bl_shipping_profiles[$i]["name"]
                    );
                }
            }

            // Return the list of available shipping profiles
            return $my_shipping_profiles;
        } catch (Exception| GuzzleException $e) {
            // Log the error and return an empty array in case of failure
            $this->logger->error("Failed to fetch shipping profiles: " . $e->getMessage());
            return [];
        }
    }
}
