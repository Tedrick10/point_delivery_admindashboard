<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    public function distanceMatrix(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origins' => 'required',
            'destinations' => 'required',
        ]);

        if ( $validator->fails() ) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            return json_custom_response($data,400);
        }

        $google_map_api_key = env('GOOGLE_MAP_API_KEY');
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$request->origins.'&destinations='.$request->destinations.'&key='.$google_map_api_key.'&mode=driving');

        return $response->json();
    }

    public function placeAutoComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
            'language' => 'required'
        ]);

        if ( $validator->fails() ) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            return json_custom_response($data,400);
        }

        $google_map_api_key = env('GOOGLE_MAP_API_KEY');

        $payload = ['input' => $request->input('search_text')];

        if ($request->has('language')) {
            $payload['languageCode'] = $request->input('language');
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $google_map_api_key,
            'Content-Type' => 'application/json'
        ])->post('https://places.googleapis.com/v1/places:autocomplete', $payload);
        return $response->json();
    }

    public function placeDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ( $validator->fails() ) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            return json_custom_response($data,400);
        }

        $google_map_api_key = env('GOOGLE_MAP_API_KEY');
        $placeId = $request->placeid;
        $apiUrl = "https://places.googleapis.com/v1/places/{$placeId}";

        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $google_map_api_key,
            'X-Goog-FieldMask' => 'id,displayName,formattedAddress,location'
        ];

        $response = Http::withHeaders($headers)->get($apiUrl);

        return $response->json();
    }

    public function directionsPolyline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required',
            'destination' => 'required',
        ]);

        if ($validator->fails()) {
            return json_custom_response([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $key = env('GOOGLE_MAP_API_KEY');

        $response = Http::get(
            'https://maps.googleapis.com/maps/api/directions/json',
            [
                'origin' => $request->origin,
                'destination' => $request->destination,
                'mode' => 'driving',
                'key' => $key,
            ]
        );

        $data = $response->json();

        return response()->json([ 'status' => true, 'polyline' => $data['routes'][0]['overview_polyline']['points'] ?? null ]);
    }
}
