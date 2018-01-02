<?php
/**
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 * Date: 01/08/2017
 */
namespace Enrico69\Magento2CustomerActivation\Model;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http as Request;

class AdminNotification
{
    private $attributes = [
        "customer_id",
        "company",
        "prefix",
        "firstname",
        "lastname",
        "telephone",
        "email",
        "terms_of_service",
        "image_rights"
    ];

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * ActivationEmail constructor.
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManagerInterface,
        ScopeConfigInterface $scopeConfigInterface,
        Request $request
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->request = $request;
    }

    /**
     * Send an email to the site owner to notice it that
     * a new customer has registered
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @throws \Magento\Framework\Exception\MailException
     */
    public function send($customer)
    {
        $siteOwnerEmail = $this->scopeConfigInterface->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE,
            $customer->getStoreId()
        );

        $values = [];
        foreach($this->attributes as $attributeCode) {
            $values[$attributeCode] = $this->request->getParam($attributeCode);
        }

        $domains = array_filter($this->request->getParams(), function($key) {
            return substr($key, 0, 6) == 'domain';
        }, ARRAY_FILTER_USE_KEY);

        $values['domains'] = $domains;

        $data = new \Magento\Framework\DataObject();
        $data->setData($values);

        $this->transportBuilder->setTemplateIdentifier('enrico69_activation_email_notification')
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $customer->getStoreId(),
                ]
            )
            ->setTemplateVars([
                'email' => $customer->getEmail(),
                'data' => $data
            ]);

        $this->transportBuilder->addTo($siteOwnerEmail);
        $this->transportBuilder->setFrom(
            [
                'name'=> $this->storeManagerInterface->getStore($customer->getStoreId())->getName(),
                'email' => $siteOwnerEmail
            ]
        );
        
        $this->transportBuilder->getTransport()->sendMessage();
    }
}
