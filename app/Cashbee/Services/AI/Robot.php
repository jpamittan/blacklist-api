<?php

namespace Cashbee\Services\AI;

use Cashbee\Models\{ Order, Customer, OrderLog, AiLog, CustomerDevice, Blacklist, User, KeywordApp };

class Robot
{
    protected $order;
    protected $customer;
    protected $reloan;

    public function __construct(Order $order, Customer $customer)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->reloan = $customer->reloan;
    }

    /**
     * @return string response
     */
    public function evaluate(): bool
    {
        // Get first if Customer is Whitelist ...
        $whitelist = $this->customer->isWhitelist();

        // Check if Customer is in Third Party Blacklist ...
        if (! $this->_checkIfCustomerIsInThirdPartyBlacklist([
            'whitelist' => $whitelist
        ])) {
            return false;
        }

        // Check If User is FOR RELOAN ...
        // Reloans are the priority
        // Because they are proven to be payers
        if ($this->reloan) {
            $this->_getIfCustomerHasSameNameFromOtherCustomer($this->reloan);
            $this->_detectIfCustomerHasSameIdentificationDevice($this->reloan);

            // Update Order to ReEvaluate
            // Also Add Order Log ...
            // $this->_aiReEvaluateOrderLog('Customer User Level Greater than 1');
            return true;
        }

        // Check if Whitelist ...
        if ($whitelist) {
            if (! $this->_getIfCustomerHasSameNameFromOtherCustomer($this->reloan)) {
                // $this->customer->is_whitelist->delete();
                return false;
            }

            if ($this->_whitelistIsInOurBlacklist()) {
                return false;
            }

            // $this->setAILogStatusToReEvaluate('The Customer is in Whitelist: Type is: ' . $whitelist->type);
            return true;
        }

        if (! $this->_checkCustomerBirthdate()) {
            return false;
        }

        if (! $this->_checkCustomerToBlacklist()) {
            return false;
        }

        if (! $this->_getNumberOfBlacklistedEmergencyContacts()) {
            return false;
        }

        if (! $this->_getIfCustomerHasSameNameFromOtherCustomer($this->reloan)) {
            return false;
        }

        if (! $this->_getIfCustomerHasSameMiddleAndLastNameFromOtherCustomer()) {
            return false;
        }

        if (! $this->_detectIfCustomerHasSameIdentificationDevice($this->reloan)) {
            return false;
        }

        if (! $this->_checkCustomerPhoneContacts()) {
            return false;
        }

        if (! $this->_checkCustomerApps()) {
            return false;
        }

        // if (! $this->_getNumberOfPeopleHavingLoanInContacts()) {
        //     return false;
        // }

        // if (! $this->_getFrequencyOfCustomerEmergencyContactInOtherCustomer()) {
        //     return false;
        // }

        // if (! $this->_sameCompanyPhoneUsedByCustomerToday()) {
        //     return false;
        // }

        // End Conditions
        // Approve Loan ...
        AiLog::create([
            'customer_id' => $this->customer->id,
            'order_id' => $this->order->id,
            'robot_response' => 'approve',
            'reason' => 'Approved ...'
        ]);

        // Set Status to For Evaluation Distribution ...
        $this->_aiForEvaluationDistribution();

        return true;
    }

    protected function _checkIfCustomerIsInThirdPartyBlacklist($config = []): bool
    {
        $whitelist = isset($config['whitelist']) ? $config['whitelist'] : false;
        
        $blacklist = Blacklist::whereCustomerId($this->customer->id)
            ->first();

        if (! $blacklist) {
            $firstName  = strtolower($this->customer->first_name);
            $lastName   = strtolower($this->customer->last_name);

            $blacklist  = Blacklist::whereRaw("LOWER(name) LIKE '%{$firstName}%'")
                ->whereRaw("LOWER(name) LIKE '%{$lastName}%'")
                ->first();

            if (! $blacklist) {
                return true;
            }
        }

        // If Customer is Whitelist and blacklist type of ours only ...
        if ($whitelist && $blacklist->type == 'blacklist_one') {
            $blacklist->delete();
            return true;
        }

        // Last Checking before considering a blacklist ...
        $blacklistNameArray = explode(' ', strtolower($blacklist->name));
        $customerFirstName  = strtolower($this->customer->first_name);
        $customerLastName   = strtolower($this->customer->last_name);
        if (in_array($customerFirstName, $blacklistNameArray) && in_array($customerLastName, $blacklistNameArray)) {
            // Log 
            $this->setOrderStatusToReject('The Customer Is In OUR Third Party Blacklist ...');
            return false;
        }

        return true;
    }

    protected function _whitelistIsInOurBlacklist()
    {
        if (! $this->customer->isWhitelist()) {
            return false;
        }

        $whitelistMobilePhone = str_replace('+', '', $this->customer->account_phone);

        $blacklist = Blacklist::whereMobileNumber($whitelistMobilePhone)->first();

        if (! $blacklist) {
            $firstName = strtolower($this->customer->first_name);
            $lastName = strtolower($this->customer->last_name);

            $blacklist = Blacklist::whereRaw("LOWER(name) LIKE '%{$firstName}%'")
                ->whereRaw("LOWER(name) LIKE '%{$lastName}%'")
                ->first();
        }

        if ($blacklist && $blacklist->type != 'blacklist_one') {
            $this->setOrderStatusToReject('Customer is in Blacklist Third Party: ' . $blacklist->type);
            return true;
        } else if ($blacklist && $blacklist->type == 'blacklist_one') {
            $blacklist->customer_id = $this->customer->id;
            $blacklist->save();
            $blacklist->delete();
            return false;
        }

        // All Good
        return false;
    }

    protected function _checkCustomerApps()
    {
        // Get The Apps ...
        $loanApps   = KeywordApp::whereType('loan')->get()->pluck('name')->toArray();
        $socialApps = KeywordApp::whereType('social')->get()->pluck('name')->toArray();

        $customerDevice = $this->customer->devices()->latest()->first();

        // Must Remove this by Tomorrow Monday
        if ($customerDevice->apps == null && $customerDevice->created_at->lte('2020-02-23 09:00:51')) {
            $customerDevice->apps = [];
            $customerDevice->save();
            return true;
        }

        $customerLoanApps       = [];
        $customerSocialApps     = [];
        
        foreach ($customerDevice->apps as $customerApp) {
            foreach ($loanApps as $loanApp) {
                if (strstr($customerApp, $loanApp)) {
                    // $customerLoanAppsCount++;
                    if (! in_array($customerApp, $customerLoanApps)) {
                        $customerLoanApps[] = $customerApp;
                    }
                }
            }

            foreach ($socialApps as $socialApp) {
                if (strstr($customerApp, $socialApp)) {
                    // $customerSocialAppsCount++;
                    if (! in_array($customerApp, $customerSocialApps)) {
                        $customerSocialApps[] = $customerApp;
                    }
                }
            }
        }

        $customerLoanAppsCount      = count($customerLoanApps);
        $customerSocialAppsCount    = count($customerSocialApps);

        // Loan Apps Greater than 20 (20 REJECT)
        if ($customerLoanAppsCount > 35) {
            $this->setOrderStatusToReject('The Customer has more than 35 loan apps ...');
            return false;
        }

        // Social Apps Less Than 3 ( 2 is REJECT)
        if ($customerSocialAppsCount < 3) {
            $this->setOrderStatusToReject('The Customer has less than 3 Social Apps ...');
            return false;
        }

        return true;
    }

    protected function _checkCustomerPhoneContacts()
    { 
        $customerContacts           = $this->customer->contacts()->get();
        $realCustomerContactNumbers = [];

        foreach ($customerContacts as $contact) {
            if (strlen($contact->phone_number) > 10) {
                $realCustomerContactNumbers[] = $contact;
            }
        }

        $customerContactsCount  = count($realCustomerContactNumbers);

        if ($customerContactsCount < 15) {
            $this->setOrderStatusToReject('The Customer has less than 15 contacts ...');
            return false;
        }

        return true;
    }

    protected function _getIfCustomerHasSameNameFromOtherCustomer($reloan = false)
    {
        $firstName = strtolower($this->customer->first_name);
        $lastName = strtolower($this->customer->last_name);

        $customer = Customer::whereRaw("LOWER(first_name) = '{$firstName}'")
                        ->whereRaw("LOWER(last_name) = '{$lastName}'")
                        ->where('id', '!=', $this->customer->id)
                        ->first();

        if ($customer) {
            if ($reloan) {
                $this->setAILogStatusToReEvaluate('The Customer has same name to Other Customer');
                return true;
            }

            if ($customer->orders()->count() == 0) {
                $this->_logOnlyWarning('Warning: The Customer has same name to Other Customer: ' . $customer->id, 'same_name');
                return true;
            }

            if ($customer->latest_loan) {
                if ($customer->latest_loan->status->system_name == 'reject' && $customer->latest_loan->created_at->addDays(7)->lte(now())) {
                    $this->_logOnlyWarning('Warning: The Customer has same name to Other Customer: ' . $customer->id . ' And Previous Order ID: ' . $customer->latest_loan->id, 'same_name_with_rejected_order');
                    return true;
                }

                if ($customer->latest_loan->status->system_name == 'cancel') {
                    $this->_logOnlyWarning('Warning: The Customer has same name to Other Customer: ' . $customer->id . ' And Previous Order ID: ' . $customer->latest_loan->id, 'same_name_with_cancelled_order');
                    return true;
                }

                if ($customer->latest_loan->is_closed_status) {
                    $this->_logOnlyWarning('Warning: The Customer has same name to Other Customer: ' . $customer->id . ' And Previous Order ID: ' . $customer->latest_loan->id, 'same_name_with_closed_order');
                    return true;
                }
            }

            $this->setOrderStatusToReject('The Customer has same name to Other Customer');
            return false;
        }

        return true;
    }

    protected function _getIfCustomerHasSameMiddleAndLastNameFromOtherCustomer() 
    {
        $middleName = strtolower($this->customer->middle_name);
        $lastName = strtolower($this->customer->last_name);

        $otherCustomer = Customer::whereRaw("LOWER(middle_name) = '{$middleName}'")
            ->whereRaw("LOWER(last_name) = '{$lastName}'")
            ->where('id', '!=', $this->customer->id)
            ->whereHas('orders', function($query) {
                return $query->whereIn('status_id', [4, 5]);
            })
            ->first();

        if ($otherCustomer) {
            if ($otherCustomer->latest_loan->is_rejected) {
                $this->setOrderStatusToReject('The Customer has same Middle Name and Last Name to Other Customer who has been rejected or in blacklist');
                return false;    
            }
        }

        return true;
    }

    protected function _getQuantityOfOrdersOverdueFromEmergencyContact()
    {
        $productsOverdue = 0;
    }

    protected function _sameCompanyPhoneUsedByCustomerToday()
    {
        $dateToday = date('Y-m-d');

        $sameCompanyPhoneTodayByCustomer = Customer::whereDate('created_at', $dateToday)
            ->where('company_phone', $this->customer->company_phone)
            ->count();

        if ($sameCompanyPhoneTodayByCustomer > 4) {
            $this->setOrderStatusToReject('The Customer used same Company Phone Today Already for 5 TIMES!!! DATE: ' . $dateToday);
            return false;
        }

        return true;
    }

    protected function _getFrequencyOfCustomerEmergencyContactInOtherCustomer()
    {
        $otherEmergencyContactsCount = DB::table(DB::raw('emergency_contacts ec_one'))
            ->join(DB::raw('emergency_contacts ec_two'), DB::raw('ec_one.phone_number'), '=', DB::raw('ec_two.phone_number'))
            ->where(DB::raw('ec_one.customer_id'), '!=', DB::raw('ec_two.customer_id'))
            ->where(DB::raw('ec_one.customer_id'), $this->customer->id)
            ->count();

        $otherCompanyContactsCount = Customer::where('company_phone', $this->customer->company_phone)
            ->where('id', '!=', $this->customer->id)
            ->count();

        if ($otherEmergencyContactsCount > 1 && $otherCompanyContactsCount > 1) {
            $this->setOrderStatusToReject('The Customer has same emergency contacts to Other Customers and Same Company Phone to Other Customers');
            return false;
        }

        return true;
    }

    protected function _getNumberOfPeopleHavingLoanInContacts()
    {
        $otherLoanersCount = DB::table('customer_contacts')
            ->join('customers', 'customer_contacts.phone_number', '=', 'contacts.account_phone')
            ->where('customer_contacts.customer_id', $this->customer->id)
            ->where('customers.id', '!=', $this->customer->id)
            ->count();

        // Check if it is 5 or more ...
        if ($otherLoanersCount > 4) {
            $this->setOrderStatusToReject('The Customer has 5 or more Customers in His/Her Contacts');
            return false;
        }

        return true;
    }

    protected function _getNumberOfBlacklistedEmergencyContacts()
    {
        $emergencyContacts = $this->customer->emergencyContacts()->get();

        foreach ($emergencyContacts as $contact) {
            $mobileNumber = str_replace('+', '', $contact->phone_number);
            $blackListMobileNumber = Blacklist::where('mobile_number', 'LIKE', "'%{$mobileNumber}%'")
                ->orWhere('mobile_number', $mobileNumber)
                ->first();

            if ($blackListMobileNumber) {
                $this->setOrderStatusToReject('The Customer has a Blacklisted Emergency Contact');
                return false;
            }
        }

        return true;
    }

    protected function _checkCustomerToBlacklist()
    {
        $firstName = strtolower($this->customer->first_name);
        $lastName = strtolower($this->customer->last_name);

        $blackListName = Blacklist::whereRaw("LOWER(name) LIKE '%{$firstName}%'")
                            ->whereRaw("LOWER(name) LIKE '%{$lastName}%'")
                            ->first();

        if ($blackListName) {
            $this->setOrderStatusToReject('This Customer is on the blacklist by first and last name');
            return false;
        }

        $mobileNumber = str_replace('+', '', $this->customer->account_phone);

        $blackListMobileNumber = Blacklist::where('mobile_number', 'LIKE', "'%{$mobileNumber}%'")
            ->orWhere('mobile_number', $mobileNumber)
            ->first();

        if ($blackListMobileNumber) {
            $this->setOrderStatusToReject('This Customer is on the blacklist by Mobile Number');
            return false;
        }
        
        return true;
    }

    protected function _detectIfCustomerHasSameIdentificationDevice($reloan = false)
    {
        if (! $this->customer->latest_device) {
            return true;
        }

        $otherCustomersOnThisDevice = CustomerDevice::where('device_id', $this->customer->latest_device->device_id)
                                        ->count();

        if ($otherCustomersOnThisDevice > 1) {

            if ($reloan) {
                $this->setAILogStatusToReEvaluate('This Device is used by 2 or more other Customers');
                return true;
            }

            $this->setOrderStatusToReject('This Device is used by 2 or more other Customers');
            return false;
        }

        return true;
    }

    protected function _checkCustomerBirthdate()
    {
        // Condition 1 ... Birthdate
        if (empty($this->customer->birthdate)) {
            $this->setOrderStatusToReject('Birthdate is empty');
            return false;
        }

        $birthdate = new \DateTime($this->customer->birthdate);
        $now = new \DateTime();
        $interval = $now->diff($birthdate);

        if ($interval->y < 18 || $interval->y > 56) {
            $this->setOrderStatusToReject('Customer age is either younger than 18 or older than 55');
            return false;
        }

        return true;
    }

    protected function _logOnlyWarning($reason, $tagName = '')
    {
        AiLog::create([
            'customer_id' => $this->customer->id,
            'order_id' => $this->order->id,
            'robot_response' => 'customer_warning_' . $tagName,
            'reason' => $reason
        ]);
    }

    protected function setOrderStatusToReject($reason)
    {
        $aiRobotUser = User::whereEmail('ai-robot@cashjeep.ph')->first();

        $this->order->status_id = 5;
        $this->order->evaluator_id = $aiRobotUser ? $aiRobotUser->id : NULL;
        $this->order->save();

        AiLog::create([
            'customer_id' => $this->customer->id,
            'order_id' => $this->order->id,
            'robot_response' => 'reject',
            'reason' => $reason
        ]);

        OrderLog::create([
            'order_id' => $this->order->id,
            'previous_status_id' => 1,
            'current_status_id' => 5,
            'event_name' => 'ai_robot_rejected_loan',
            'remarks' => $reason,
            'operator_id' => $aiRobotUser ? $aiRobotUser->id : NULL
        ]);
    }

    protected function setAILogStatusToReEvaluate($reason)
    {
        AiLog::create([
            'customer_id' => $this->customer->id,
            'order_id' => $this->order->id,
            'robot_response' => 'reevaluate',
            'reason' => $reason
        ]);
    }

    protected function _aiReEvaluateOrderLog($remarks = '')
    {
        $aiRobotUser = User::whereEmail('ai-robot@cashjeep.ph')->first();

        $this->order->status_id = 8;
        $this->order->evaluator_id = $aiRobotUser ? $aiRobotUser->id : NULL;
        $this->order->save();

        OrderLog::create([
            'order_id' => $this->order->id,
            'previous_status_id' => 1,
            'current_status_id' => 8,
            'event_name' => 'ai_robot_reevaluate_loan',
            'remarks' => $remarks,
            'operator_id' => $aiRobotUser ? $aiRobotUser->id : NULL
        ]);
    }

    protected function _aiForEvaluationDistribution($remarks = '')
    {
        $aiRobotUser = User::whereEmail('ai-robot@cashjeep.ph')->first();

        $this->order->status_id = 2;
        $this->order->evaluator_id = $aiRobotUser ? $aiRobotUser->id : NULL;
        $this->order->save();

        OrderLog::create([
            'order_id' => $this->order->id,
            'previous_status_id' => 1,
            'current_status_id' => 2,
            'event_name' => 'ai_robot_for_evaluation_distribution_loan',
            'remarks' => $remarks,
            'operator_id' => $aiRobotUser ? $aiRobotUser->id : NULL
        ]);
    }
}