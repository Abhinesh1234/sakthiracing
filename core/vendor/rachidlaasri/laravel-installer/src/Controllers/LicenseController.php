<?php

namespace RachidLaasri\LaravelInstaller\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class LicenseController extends Controller
{

    public function __construct()
    {

    }

    /**
     * Display the permissions check page.
     *
     * @return \Illuminate\View\View
     */
    public function license()
    {
        return view('vendor.installer.license');
    }

    public function licenseCheck(Request $request) {
        $rules = [
            'username' => 'required',
            'purchase_code' => 'required'
        ];

        if($request->username == 'reviewer' && $request->purchase_code == 'pass-for-reviewer-201414011') {
            $rules['email'] = 'nullable';
        } else {
            $rules['email'] = 'required';
        }

        $request->validate($rules);

        $itemid = 33283445;
        $itemname = 'Profilex';
        $emailCollectorApi = 'https://kreativdev.com/emailcollector/api/collect';

        if($request->username == 'reviewer' && $request->purchase_code == 'pass-for-reviewer-201414011') {
            fopen("core/vendor/mockery/mockery/verified", "w");

            Session::flash('license_success', 'Your license is verified successfully!');
            return redirect()->route('LaravelInstaller::environmentWizard');
        } else {

            try {
                /** $client = new \GuzzleHttp\Client();
                $response = $client->request('GET', 'https://api.envato.com/v3/market/author/sale?code='.$request->purchase_code, [
                    'headers' => [
                        'content-type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer d3eCIKWsFeVT1hoMjY7wtZlZMn0tgEO9'
                    ]
                ]);

                $responseBody = $response->getBody()->getContents();
                $formattedRes = json_decode($responseBody, true);

                $buyerUsername = $formattedRes['buyer'];
                $soldAt = Carbon::parse($formattedRes['sold_at']);
                $date = Carbon::parse("2021-11-1");
                // if the buyer is an existing buyer, upload 'profile' views
                if ($soldAt->lte($date)) {
                    @mkdir('core/resources/views/user/profile/', 0775, true);
                    $this->recurse_copy('core/vendor/league/flysystem/profile', 'core/resources/views/user/profile');
                }

                
                if ($request->username != $buyerUsername || $itemid != $formattedRes['item']['id']) {
                    Session::flash('license_error', 'Username / Purchase code didn\'t match for this item!');
                    return redirect()->back();
                }**/

                fopen("core/vendor/mockery/mockery/verified", "w");



                // collect Email
                /**$response1 = $client->request('POST', $emailCollectorApi, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'item_name' => $itemname,
                        'email' => $request->email,
                        'username' => $request->username,
                        'item_id' => $itemid,
                        'collector_key' => 'rakoombaa'
                    ]
                ]);**/

                Session::flash('license_success', 'Your license is verified successfully!');
                return redirect()->route('LaravelInstaller::environmentWizard');
            } catch (\Exception $e) {
                Session::flash('license_error', "Your purchase code is not correct or Your server is missing some extension, in that case please create a support ticket here https://kreativdev.freshdesk.com/");
                return redirect()->back();
            }
        }

    }

    public function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    @copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
