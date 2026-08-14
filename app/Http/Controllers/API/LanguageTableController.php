<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LanguageVersionDetail;
use App\Http\Resources\LanguageTableResource;
use App\Models\LanguageList;
use App\Models\SMSSetting;

class LanguageTableController extends Controller
{
    public function getList(Request $request)
    {
        $allowDeliverymanSetting = SettingData('allow_deliveryman', 'allow_deliveryman');
        $is_allow_deliveryman = $allowDeliverymanSetting === null || $allowDeliverymanSetting === '' || (int) $allowDeliverymanSetting === 1;
        $version_data = LanguageVersionDetail::where('version_no',request('version_no'))->first();

        if (isset($version_data) && !empty($version_data)) {
            return json_custom_response([ 'status' => false, 'data' => [] , 'theme_color' => appSettingcurrency('color')]);
        }

        $language_content = LanguageList::query()->where('status','1')->orderBy('id', 'asc')->get();
        $language_version = LanguageVersionDetail::find(1);
        $items = LanguageTableResource::collection($language_content);
        $twilloSms =  SMSSetting::where('type', 'twilio')->first();
        $is_twilio_active = optional($twilloSms)->status == 1;

        $response = [
            'status' => true,
            'version_code' => optional($language_version)->version_no ?? 1,
            'default_language_id' => optional($language_version)->default_language_id,
            'data' => $items,
            'allow_deliveryman' => $is_allow_deliveryman,
            'theme_color' => appSettingcurrency('color') ?? '#FE6F07',
            'twilio_sms' => $is_twilio_active
        ];

        return json_custom_response($response);
    }
}
