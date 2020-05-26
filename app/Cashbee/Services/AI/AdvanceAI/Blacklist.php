<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Models\{ Customer, SystemSetting };
use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;

class Blacklist extends AdvancedGuardianAbstract
{
    protected $api_name;
    protected $id_type_mappings;
    protected $blackListCheckEnabled;
    private $name;
    private $idNumber;

    /**
     * Instantiate API name and ID type mappings
     */
    public function __construct(Customer $customer)
    {
        parent::__construct($customer);
        $getAdvanceThirdPartyBlacklist = SystemSetting::GetAdvanceThirdPartyBlacklist()->get()->first();
        $this->blackListCheckEnabled = $getAdvanceThirdPartyBlacklist->enabled;
        $this->api_name = $getAdvanceThirdPartyBlacklist->settings["api_name"];
        $this->id_type_mappings = $getAdvanceThirdPartyBlacklist->settings["id_type_mappings"];
    }

    /**
     * Create Blacklist Log Data to be save in database
     *
     * @param object $response
     * @return array
     */
    public function provideBlacklistLogData(object $response): array
    {
        $score = 0;
        foreach ($response->data as $key => $value) {
            if ($value) {
                $score++;
            }
        }

        return [
            'source'            => 'advance_ai_blacklist',
            'source_response'   => $response,
            'blacklisted'       => $response->data->hitIdNumber ?? $response->data->hitPhoneNumber ?? $response->data->hitNameAndBirthday,
            'score'             => $score,
            'customer_id'       => $this->customer->id
        ];
    }

    /**
     * Check in Advance AI if a customer is blacklisted
     *
     * @return object
     */
    public function process(): ?object
    {
        if (! ($this->advancedAIEnabled && $this->blackListCheckEnabled)) {
            return null;
        }

        $this->fillEmptyFields();
        $idType = $this->setIDType($this->customer->identification_type);
        $result = $this->api->request($this->api_name, $this->setBlacklistRequestData($idType));

        return json_decode($result);
    }

    /**
     * Fill customer's name and identification number by default values if empty
     *
     * @return void
     */
    public function fillEmptyFields(): void
    {
        $this->name = (preg_match("/[a-z]/i", $this->customer->name)) ? $this->customer->name : "AAA";
        $this->idNumber = (! empty($this->customer->identification_number)) ? $this->customer->identification_number : "999999999";
    }

    /**
     * Set Adnvance AI ID type
     *
     * @param string $identificationType
     * @return string
     */
    public function setIDType(string $identificationType): string
    {
        $type = 'OTHERS';
        foreach ($this->id_type_mappings as $obj) {
            foreach ($obj as $key => $value) {
                if (in_array($identificationType, $value)) {
                    $type = $key;
                    break;
                }
            }
        }

        return $type;
    }

    /**
     * Set request data before sending to Advance AI Blacklist Check
     *
     * @param string $idType
     * @return void
     */
    public function setBlacklistRequestData(string $idType): array
    {
        return [
            'name' => strtoupper($this->name),
            'idType' => $idType,
            'idNumber' => $this->idNumber,
            'phoneNumber' => $this->customer->account_phone,
            'md5PhoneNumber' => md5($this->customer->account_phone),
            'birthDay' => $this->customer->birthdate
        ];
    }
}
