<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BasicExtended;
use App\Models\User\UserCustomDomain;
use Auth;
use Illuminate\Http\Request;
use Session;

class DomainController extends Controller
{
    public function domains() {
        $rcDomain = UserCustomDomain::where('status', '<>', 2)->where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->first();
        $data['rcDomain'] = $rcDomain;
        return view('user.domains', $data);
    }

    public function isValidDomain($domain_name) {
        //FILTER_VALIDATE_URL checks length but..why not? so we dont move forward with more expensive operations
        $domain_len = strlen($domain_name);
        if ($domain_len < 3 OR $domain_len > 253)
            return FALSE;

        //getting rid of HTTP/S just in case was passed.
        if(stripos($domain_name, 'http://') === 0)
            return FALSE;
        elseif(stripos($domain_name, 'https://') === 0)
            return FALSE;
        
        //we dont need the www either                 
        if(stripos($domain_name, 'www.') === 0)
            return FALSE;

        //Checking for a '.' at least, not in the beginning nor end, since http://.abcd. is reported valid
        if(strpos($domain_name, '.') === FALSE OR $domain_name[strlen($domain_name)-1]=='.' OR $domain_name[0]=='.')
            return FALSE;
                    
        //now we use the FILTER_VALIDATE_URL, concatenating http so we can use it, and return BOOL
        if(filter_var ('http://' . $domain_name, FILTER_VALIDATE_URL)===FALSE)
            return FALSE;

        //now we use the FILTER_VALIDATE_URL, concatenating http so we can use it, and return BOOL
        return TRUE;
    }

    public function domainrequest(Request $request) {
        $be = BasicExtended::select('domain_request_success_message', 'cname_record_section_title')->first();
 
        $rules = [
            'custom_domain' => [
                'required',
                function ($attribute, $value, $fail) use ($be) {
                    // if user request the current domain
                    if (getCdomain(Auth::user()) == $value) {
                        $fail('You cannot request your current domain.');
                    }
                    // check if domain is valid
                    if (!$this->isValidDomain($value)) {
                        $fail('Domain format is not valid');
                    } else {
                        // check if CNAME records are setup successfully
                        try {
                            $dns = dns_get_record($value, DNS_CNAME);
                            if (!empty($dns)) {
                                // $parsedUrl = parse_url("https://kreativdev.com/");
                                $parsedUrl = parse_url(url('/'));
                                if ($dns[0]['host'] != $value || $dns[0]['type'] != 'CNAME' || $dns[0]['target'] != $parsedUrl['host']) {
                                    $fail('You did not add CNAME record correctly in domain, please take the CNAME record datas from the below table.');
                                }
                            } else {
                                $fail('<strong>Either you have not added the CNAME record in your domain yet, OR the DNS propagation is not completed yet.</strong><br><ul><li>If you have not added the CNAME record yet, then please read the below section - <strong>"' . $be->cname_record_section_title . '"</strong>.</li><li>If you have added CNAME record in your domain, then please wait upto 72 hours (usually, it takes much less than 72 hours) for the DNS propagation to complete.<br>DNS propagation time is not in our control.</li></ul>');
                            }
                        } catch(\Exception $e) {
                            $fail($e->getMessage());
                        }
                    }
                }
            ]
        ];

        $request->validate($rules);

        $cdomain = new UserCustomDomain;
        $cdomain->user_id = Auth::user()->id;
        $cdomain->requested_domain = $request->custom_domain;
        $cdomain->current_domain = getCdomain(Auth::user());
        $cdomain->status = 0;
        $cdomain->save();

        $request->session()->flash('domain-success', $be->domain_request_success_message);
        return back();
    }
}
