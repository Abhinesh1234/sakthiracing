<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Helpers\MegaMailer;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\BasicExtended;
use App\Models\BasicSetting;
use App\Models\Membership;
use App\Models\OfflineGateway;
use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\UserPermission;
use Carbon\Carbon;
use Hash;
use Session;
use Validator;

class RegisterUserController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->term;

        $users = User::when($term, function($query, $term) {
            $query->where('username', 'like', '%' . $term . '%')->orWhere('email', 'like', '%' . $term . '%');
        })->orderBy('id', 'DESC')->paginate(10);

        $online = PaymentGateway::query()->where('status', 1)->get();
        $offline = OfflineGateway::where('status', 1)->get();
        $gateways = $online->merge($offline);
        $packages = Package::query()->where('status', '1')->get();

        return view('admin.register_user.index',compact('users', 'gateways', 'packages'));
    }

    public function view($id)
    {
        $user = User::findOrFail($id);
        return view('admin.register_user.details',compact('user'));

    }

    public function store(Request $request) {

        $rules = [
            'username' => 'required|alpha_num|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'package_id' => 'required',
            'payment_gateway' => 'required',
            'online_status' => 'required'
        ];

        $messages = [
            'package_id.required' => 'The package field is required',
            'online_status.required' => 'The publicly hidden field is required'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $user = User::where('username', $request['username']);
        if ($user->count() == 0) {
            $user = User::create([
                'email' => $request['email'],
                'username' => $request['username'],
                'password' => bcrypt($request['password']),
                'online_status' => $request["online_status"],
                'status' => 1,
                'email_verified' => 1,
            ]);
        }
        
        if ($user) {
            $deLang = Language::firstOrFail();
            $langCount = Language::where('user_id', $user->id)->where('is_default', 1)->count();
            if ($langCount == 0) {
                Language::create([
                    'name' => 'English',
                    'code' => 'en',
                    'is_default' => 1,
                    'rtl' => 0,
                    'user_id' => $user->id,
                    'keywords' => $deLang->keywords
                ]);
            }

            $package = Package::find($request['package_id']);
            $be = BasicExtended::first();
            $bs = BasicSetting::select('website_title')->first();
            $transaction_id = UserPermissionHelper::uniqidReal(8);

            $startDate = Carbon::today()->format('Y-m-d');
            if ($package->term === "monthly") {
                $endDate = Carbon::today()->addMonth()->format('Y-m-d');
            } elseif ($package->term === "yearly") {
                $endDate = Carbon::today()->addYear()->format('Y-m-d');
            } elseif ($package->term === "lifetime") {
                $endDate = Carbon::maxValue()->format('d-m-Y');
            }

            Membership::create([
                'price' => $package->price,
                'currency' => $be->base_currency_text ? $be->base_currency_text : "USD",
                'currency_symbol' => $be->base_currency_symbol ? $be->base_currency_symbol : $be->base_currency_text,
                'payment_method' => $request["payment_gateway"],
                'transaction_id' => $transaction_id ? $transaction_id : 0,
                'status' => 1,
                'is_trial' => 0,
                'trial_days' => 0,
                'receipt' => $request["receipt_name"] ? $request["receipt_name"] : null,
                'transaction_details' => null,
                'settings' => json_encode($be),
                'package_id' => $request['package_id'],
                'user_id' => $user->id,
                'start_date' => Carbon::parse($startDate),
                'expire_date' => Carbon::parse($endDate),
            ]);
            $package = Package::findOrFail($request['package_id']);
            $features = json_decode($package->features, true);
            $features[] = "Contact";
            UserPermission::create([
                'package_id' => $request['package_id'],
                'user_id' => $user->id,
                'permissions' => json_encode($features)
            ]);


            $requestData = [
                'start_date' => $startDate,
                'expire_date' => $endDate,
                'payment_method' => $request['payment_gateway']
            ];
            $file_name = $this->makeInvoice($requestData,"membership",$user,null,$package->price,$request['payment_gateway'],null,$be->base_currency_symbol_position,$be->base_currency_symbol,$be->base_currency_text,$transaction_id,$package->title);

            $mailer = new MegaMailer();
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);
            $data = [
                'toMail' => $user->email,
                'toName' => $user->fname,
                'username' => $user->username,
                'package_title' => $package->title,
                'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                'activation_date' => $startDate->toFormattedDateString(),
                'expire_date' => $endDate->toFormattedDateString(),
                'membership_invoice' => $file_name,
                'website_title' => $bs->website_title,
                'templateType' => 'registration_with_premium_package',
                'type' => 'registrationWithPremiumPackage'
            ];
            $mailer->mailFromAdmin($data);
        }

        Session::flash('success', 'User added successfully!');
        return "success";
    }

    public function userban(Request $request)
    {
        $user = User::where('id',$request->user_id)->first();
        $user->status = $request->status;
        $user->save();
        Session::flash('success', 'Status update successfully!');
        return back();

    }


    public function emailStatus(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->update([
            'email_verified' => $request->email_verified,
        ]);

        Session::flash('success', 'Email status updated for ' . $user->username);
        return back();
    }

    public function userFeatured(Request $request)
    {
        $user = User::where('id',$request->user_id)->first();
        $user->featured = $request->featured;
        $user->feature_time = now();
        $user->save();
        Session::flash('success', 'User featured update successfully!');
        return back();
    }


    public function changePass($id) {
        $data['user'] = User::findOrFail($id);
        return view('admin.register_user.password', $data);
    }


    public function updatePassword(Request $request)
    {

        $messages = [
            'npass.required' => 'New password is required',
            'cfpass.required' => 'Confirm password is required',
        ];

        $request->validate([
            'npass' => 'required',
            'cfpass' => 'required',
        ], $messages);


        $user = User::findOrFail($request->user_id);
        if ($request->npass == $request->cfpass) {
            $input['password'] = Hash::make($request->npass);
        } else {
            return back()->with('warning', __('Confirm password does not match.'));
        }

        $user->update($input);

        Session::flash('success', 'Password update for ' . $user->username);
        return back();
    }

    public function delete(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        if ($user->testimonials()->count() > 0) {
            $testimonials = $user->testimonials()->get();
            foreach ($testimonials as $key => $tstm) {
                @unlink('assets/front/img/user/testimonials/' . $tstm->image);
                $tstm->delete();
            }
        }

        if ($user->social_media()->count() > 0) {
            $user->social_media()->delete();
        }

        if ($user->skills()->count() > 0) {
            $user->skills()->delete();
        }

        if ($user->services()->count() > 0) {
            $services = $user->services()->get();
            foreach ($services as $key => $service) {
                @unlink('assets/front/img/user/services/' . $service->image);
                $service->delete();
            }
        }

        if ($user->seos()->count() > 0) {
            $user->seos()->delete();
        }

        if ($user->portfolios()->count() > 0) {
            $portfolios = $user->portfolios()->get();
            foreach ($portfolios as $key => $portfolio) {
                @unlink('assets/front/img/user/portfolios/' . $portfolio->image);
                if ($portfolio->portfolio_images()->count() > 0) {
                    foreach ($portfolio->portfolio_images as $key => $pi) {
                        @unlink('assets/front/img/user/portfolios/' . $pi->image);
                        $pi->delete();
                    }
                }
                $portfolio->delete();
            }
        }

        if ($user->portfolioCategories()->count() > 0) {
            $user->portfolioCategories()->delete();
        }

        if ($user->permission()->count() > 0) {
            $user->permission()->delete();
        }

        if ($user->languages()->count() > 0) {
            $user->languages()->delete();
        }

        if ($user->home_page_texts()->count() > 0) {
            $homeTexts = $user->home_page_texts()->get();
            foreach ($homeTexts as $key => $homeText) {
                @unlink('assets/front/img/user/home_settings/' . $homeText->hero_image);
                @unlink('assets/front/img/user/home_settings/' . $homeText->about_image);
                @unlink('assets/front/img/user/home_settings/' . $homeText->skills_image);
                @unlink('assets/front/img/user/home_settings/' . $homeText->achievement_image);
                $homeText->delete();
            }
        }

        if ($user->educations()->count() > 0) {
            $user->educations()->delete();
        }

        if ($user->blog_categories()->count() > 0) {
            $user->blog_categories()->delete();
        }

        if ($user->blogs()->count() > 0) {
            $blogs = $user->blogs()->get();
            foreach ($blogs as $key => $blog) {
                @unlink('assets/front/img/user/blogs/' . $blog->image);
                $blog->delete();
            }
        }

        if ($user->basic_setting()->count() > 0) {
            $bs = $user->basic_setting;
            @unlink('assets/front/img/user/' . $bs->logo);
            @unlink('assets/front/img/user/' . $bs->preloader);
            @unlink('assets/front/img/user/' . $bs->favicon);
            @unlink('assets/front/img/user/cv/' . $bs->cv);
            @unlink('assets/front/img/user/qr/' . $bs->qr_image);
            @unlink('assets/front/img/user/qr/' . $bs->qr_inserted_image);
            $bs->delete();
        }

        if ($user->achievements()->count() > 0) {
            $user->achievements()->delete();
        }

        if ($user->memberships()->count() > 0) {
            foreach($user->memberships as $key => $membership) {
                @unlink('assets/front/img/membership/receipt/' . $membership->receipt);
                $membership->delete();
            }
        }

        if ($user->job_experiences()->count() > 0) {
            $user->job_experiences()->delete();
        }

        if ($user->custom_domains()->count() > 0) {
            $user->custom_domains()->delete();
        }

        if ($user->cvs()->count() > 0) {
            $cvs = $user->cvs;
            foreach ($cvs as $key => $cv) {
                if ($cv->user_cv_sections()->count() > 0) {
                    $cv->user_cv_sections()->delete();
                }
                @unlink('assets/front/img/user/cv/' . $cv->image);
                $cv->delete();
            }
        }

        if ($user->qr_codes()->count() > 0) {
            $qrs = $user->qr_codes;
            foreach ($qrs as $key => $qr) {
                @unlink('assets/front/img/user/qr/' . $qr->image);
                $qr->delete();
            }
        }

        if ($user->vcards()->count() > 0) {
            $vcards = $user->vcards;
            foreach ($vcards as $key => $vcard) {
                @unlink('assets/front/img/user/vcard/' . $vcard->profile_image);
                @unlink('assets/front/img/user/vcard/' . $vcard->cover_image);
                $vcard->delete();
            }
        }

        @unlink('assets/front/img/user/' . $user->photo);
        $user->delete();

        Session::flash('success', 'User deleted successfully!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        foreach ($ids as $id) {
            $user = User::findOrFail($id);

            if ($user->testimonials()->count() > 0) {
                $testimonials = $user->testimonials()->get();
                foreach ($testimonials as $key => $tstm) {
                    @unlink('assets/front/img/user/testimonials/' . $tstm->image);
                    $tstm->delete();
                }
            }
    
            if ($user->social_media()->count() > 0) {
                $user->social_media()->delete();
            }
    
            if ($user->skills()->count() > 0) {
                $user->skills()->delete();
            }
    
            if ($user->services()->count() > 0) {
                $services = $user->services()->get();
                foreach ($services as $key => $service) {
                    @unlink('assets/front/img/user/services/' . $service->image);
                    $service->delete();
                }
            }
    
            if ($user->seos()->count() > 0) {
                $user->seos()->delete();
            }
    
            if ($user->portfolios()->count() > 0) {
                $portfolios = $user->portfolios()->get();
                foreach ($portfolios as $key => $portfolio) {
                    @unlink('assets/front/img/user/portfolios/' . $portfolio->image);
                    if ($portfolio->portfolio_images()->count() > 0) {
                        foreach ($portfolio->portfolio_images as $key => $pi) {
                            @unlink('assets/front/img/user/portfolios/' . $pi->image);
                            $pi->delete();
                        }
                    }
                    $portfolio->delete();
                }
            }
    
            if ($user->portfolioCategories()->count() > 0) {
                $user->portfolioCategories()->delete();
            }
    
            if ($user->permission()->count() > 0) {
                $user->permission()->delete();
            }
    
            if ($user->languages()->count() > 0) {
                $user->languages()->delete();
            }
    
            if ($user->home_page_texts()->count() > 0) {
                $homeTexts = $user->home_page_texts()->get();
                foreach ($homeTexts as $key => $homeText) {
                    @unlink('assets/front/img/user/home_settings/' . $homeText->hero_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->about_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->skills_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->achievement_image);
                    $homeText->delete();
                }
            }
    
            if ($user->educations()->count() > 0) {
                $user->educations()->delete();
            }
    
            if ($user->blog_categories()->count() > 0) {
                $user->blog_categories()->delete();
            }
    
            if ($user->blogs()->count() > 0) {
                $blogs = $user->blogs()->get();
                foreach ($blogs as $key => $blog) {
                    @unlink('assets/front/img/user/blogs/' . $blog->image);
                    $blog->delete();
                }
            }
    
            if ($user->basic_setting()->count() > 0) {
                $bs = $user->basic_setting;
                @unlink('assets/front/img/user/' . $bs->logo);
                @unlink('assets/front/img/user/' . $bs->preloader);
                @unlink('assets/front/img/user/' . $bs->favicon);
                @unlink('assets/front/img/user/cv/' . $bs->cv);
                @unlink('assets/front/img/user/qr/' . $bs->qr_image);
                @unlink('assets/front/img/user/qr/' . $bs->qr_inserted_image);
                $bs->delete();
            }
    
            if ($user->achievements()->count() > 0) {
                $user->achievements()->delete();
            }

            if ($user->memberships()->count() > 0) {
                foreach($user->memberships as $key => $membership) {
                    @unlink('assets/front/img/membership/receipt/' . $membership->receipt);
                    $membership->delete();
                }
            }
    
            if ($user->job_experiences()->count() > 0) {
                $user->job_experiences()->delete();
            }

            if ($user->custom_domains()->count() > 0) {
                $user->custom_domains()->delete();
            }
    
            if ($user->cvs()->count() > 0) {
                $cvs = $user->cvs;
                foreach ($cvs as $key => $cv) {
                    if ($cv->user_cv_sections()->count() > 0) {
                        $cv->user_cv_sections()->delete();
                    }
                    @unlink('assets/front/img/user/cv/' . $cv->image);
                    $cv->delete();
                }
            }
    
            if ($user->qr_codes()->count() > 0) {
                $qrs = $user->qr_codes;
                foreach ($qrs as $key => $qr) {
                    @unlink('assets/front/img/user/qr/' . $qr->image);
                    $qr->delete();
                }
            }
    
            if ($user->vcards()->count() > 0) {
                $vcards = $user->vcards;
                foreach ($vcards as $key => $vcard) {
                    @unlink('assets/front/img/user/vcard/' . $vcard->profile_image);
                    @unlink('assets/front/img/user/vcard/' . $vcard->cover_image);
                    $vcard->delete();
                }
            }
    
            @unlink('assets/front/img/user/' . $user->photo);
            $user->delete();
        }

        Session::flash('success', 'Users deleted successfully!');
        return "success";
    }
}
