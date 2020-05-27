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

    public function __construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate)
    {
        parent::__construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate);
        $getAdvanceThirdPartyBlacklist = SystemSetting::GetAdvanceThirdPartyBlacklist()->get()->first();
        $this->blackListCheckEnabled = $getAdvanceThirdPartyBlacklist->enabled;
        $this->api_name = $getAdvanceThirdPartyBlacklist->settings["api_name"];
        $this->id_type_mappings = $getAdvanceThirdPartyBlacklist->settings["id_type_mappings"];
    }

    public function provideBlacklistLogData(object $response): array
    {
        $score = 0;
        foreach ($response->data as $key => $value) {
            if ($value) {
                $score++;
            }
        }

        return [
            'source'          => 'advance_ai_blacklist',
            'source_response' => $response,
            'blacklisted'     => $response->data->hitIdNumber ?? $response->data->hitPhoneNumber ?? $response->data->hitNameAndBirthday,
            'score'           => $score,
            'mobile_number'   => $this->mobileNumber
        ];
    }

    public function process(): ?object
    {
        if (! ($this->advancedAIEnabled && $this->blackListCheckEnabled)) {
            return null;
        }
        $this->fillEmptyFields();
        $idType = $this->setIDType($this->identificationType);
        $result = $this->api->request($this->api_name, $this->setBlacklistRequestData($idType));

        return json_decode($result);
    }

    public function fillEmptyFields(): void
    {
        $this->name = (preg_match("/[a-z]/i", $this->name)) ? $this->name : "AAA";
        $this->idNumber = (! empty($this->identificationNumber)) ? $this->identificationNumber : "999999999";
    }

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

    public function setBlacklistRequestData(string $idType): array
    {
        return [
            'name' => strtoupper($this->name),
            'idType' => $idType,
            'idNumber' => $this->idNumber,
            'phoneNumber' => $this->mobileNumber,
            'md5PhoneNumber' => md5($this->mobileNumber),
            'birthDay' => $this->birthdate
        ];
    }
}
