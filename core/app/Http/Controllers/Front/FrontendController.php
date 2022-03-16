<?php

namespace App\Http\Controllers\Front;

require_once 'core/vendor/Transliterator/Transliterator.php';
require_once 'core/vendor/vcard/VCard.php';
use App\Http\Controllers\Controller;
use App\Http\Helpers\MegaMailer;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\OfflineGateway;
use App\Models\Package;
use App\Models\Partner;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Models\Feature;
use App\Models\Language;
use App\Models\Subscriber;
use App\Models\User\Language as UserLanguage;
use App\Models\Testimonial;
use App\Models\BasicSetting as BS;
use App\Models\Bcategory;
use App\Models\Blog;
use App\Models\BasicExtended as BE;
use App\Models\BasicExtended;
use App\Models\BasicSetting;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Seo;
use App\Models\User\HomePageText;
use App\Models\User\UserCustomDomain;
use App\Models\User\UserCv;
use App\Models\User\UserVcard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Config;
use PHPMailer\PHPMailer\PHPMailer;
use JeroenDesloovere\VCard\VCard;
use Validator;

class FrontendController extends Controller
{
    public function __construct()
    {
        $bs = BS::first();
        $be = BE::first();

        Config::set('captcha.sitekey', $bs->google_recaptcha_site_key);
        Config::set('captcha.secret', $bs->google_recaptcha_secret_key);
        Config::set('mail.host', $be->smtp_host);
        Config::set('mail.port', $be->smtp_port);
        Config::set('mail.username', $be->smtp_username);
        Config::set('mail.password', $be->smtp_password);
        Config::set('mail.encryption', $be->encryption);

    }

    public function index()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;

        $data['processes'] = Process::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['features'] = Feature::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['featured_users'] = User::where([
            ['featured', 1],
            ['status', 1]
        ])
        ->whereHas('memberships', function($q){
            $q->where('status','=',1)
            ->where('start_date','<=', Carbon::now()->format('Y-m-d'))
            ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
        })->orderBy('feature_time', 'DESC')->get();
        $data['testimonials'] = Testimonial::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();
        $data['blogs'] = Blog::where('language_id', $lang_id)->orderBy('id', 'DESC')->take(2)->get();

        $data['partners'] = Partner::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();

        $data['seo'] = Seo::where('language_id', $lang_id)->first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        } 
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        } 
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);

        return view('front.index', $data);
    }    
    
    public function subscribe(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:subscribers'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $subsc = new Subscriber;
        $subsc->email = $request->email;
        $subsc->save();

        return "success";
    }

    public function loginView()
    {

        return view('front.login');
    }

    public function checkUsername($username) {
        $count = User::where('username', $username)->count();
        $status = $count > 0 ? true : false;
        return response()->json($status);
    }

    public function step1($status, $id)
    {
        if (Auth::check()) {
            return redirect()->route('user.plan.extend.index');
        }
        $data['status'] = $status;
        $data['id'] = $id;
        $package = Package::findOrFail($id);
        $data['package'] = $package;

        $hasSubdomain = false;
        $features = [];
        if (!empty($package->features)) {
            $features = json_decode($package->features, true);
        }
        if (is_array($features) && in_array('Subdomain', $features)) {
            $hasSubdomain = true;
        }

        $data['hasSubdomain'] = $hasSubdomain;

        return view('front.step', $data);
    }

    public function step2(Request $request)
    {
        $data = $request->session()->get('data');
        return view('front.checkout', $data);
    }

    public function checkout(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|alpha_num|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $seo = Seo::where('language_id', $currentLang->id)->first();
        $be = $currentLang->basic_extended;
        $data['bex'] = $be;
        $data['username'] = $request->username;
        $data['email'] = $request->email;
        $data['password'] = $request->password;
        $data['status'] = $request->status;
        $data['id'] = $request->id;
        $online = PaymentGateway::query()->where('status', 1)->get();
        $offline = OfflineGateway::where('status', 1)->get();
        $data['offline'] = $offline;
        $data['payment_methods'] = $online->merge($offline);
        $data['package'] = Package::query()->findOrFail($request->id);
        $data['seo'] = $seo;
        $request->session()->put('data', $data);
        return redirect()->route('front.registration.step2');
    }


    // packages start
    public function pricing(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['bex'] = BE::first();
        $data['abs'] = BS::first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        } 
        if (Package::query()->where('status', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        } 
        if (Package::query()->where('status', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);

        return view('front.pricing', $data);
    }

    // blog section start
    public function blogs(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['currentLang'] = $currentLang;

        $lang_id = $currentLang->id;

        $category = $request->category;
        if (!empty($category)) {
            $data['category'] = Bcategory::findOrFail($category);
        }
        $term = $request->term;

        $data['bcats'] = Bcategory::where('language_id', $lang_id)->where('status', 1)->orderBy('serial_number', 'ASC')->get();


        $data['blogs'] = Blog::when($category, function ($query, $category) {
            return $query->where('bcategory_id', $category);
        })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'like', '%' . $term . '%');
            })
            ->when($currentLang, function ($query, $currentLang) {
                return $query->where('language_id', $currentLang->id);
            })->orderBy('serial_number', 'ASC')->paginate(3);
        return view('front.blogs', $data);
    }

    public function blogdetails($slug, $id)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $lang_id = $currentLang->id;


        $data['blog'] = Blog::findOrFail($id);
        $data['bcats'] = Bcategory::where('status', 1)->where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        return view('front.blog-details', $data);
    }

    public function contactView()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        return view('front.contact', $data);
    }

    public function faqs()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();
        
        $lang_id = $currentLang->id;
        $data['faqs'] = Faq::where('language_id', $lang_id)
            ->orderBy('serial_number', 'DESC')
            ->get();
        return view('front.faq', $data);
    }

    public function dynamicPage($slug)
    {
        $data['page'] = Page::where('slug', $slug)->firstOrFail();

        return view('front.dynamic', $data);
    }

    public function users(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $homeTexts = [];
        if (!empty($request->designation)) {
            $homeTexts = HomePageText::when($request->designation, function ($q) use ($request) {
                return $q->where('designation', 'like', '%' . $request->designation . '%');
            })->select('user_id')->get();
        }

        $userIds = [];

        foreach ($homeTexts as $key => $homeText) {
            if (!in_array($homeText->user_id, $userIds)) {
                $userIds[] = $homeText->user_id;
            }
        }

        $data['users'] = null;
        $users = User::where('online_status',1)
            ->whereHas('memberships', function($q){
                $q->where('status','=',1)
                ->where('start_date','<=', Carbon::now()->format('Y-m-d'))
                ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                        $query->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('username', 'like', '%' . $request->search . '%');
                    });
            })
            ->when($request->location, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->where('city', 'like', '%' . $request->location . '%')
                    ->orWhere('country', 'like', '%' . $request->location . '%');
                });
            })
            ->when($request->designation, function ($q) use ($userIds) {
                return $q->where(function ($query) use ($userIds) {
                    $query->whereIn('id', $userIds);
                });
            })
            // ->when($userIds, function ($q) use ($userIds) {
            //     return $q;
            // })
            ->orderBy('id', 'DESC')
            ->paginate(9);

        $data['users'] = $users;
        return view('front.users', $data);
    }

    public function userDetailView($domain)
    {
        $user = getUser();
        if (Auth::check() && Auth::user()->id != $user->id && $user->online_status != 1) {
            return redirect()->route('front.index');
        } elseif (!Auth::check() && $user->online_status != 1) {
            return redirect()->route('front.index');
        }

        $package = UserPermissionHelper::userPackage($user->id);
        if(is_null($package)){
            Session::flash('warning', 'User membership is expired');
            if(Auth::check()){
                return redirect()->route('user-dashboard')->with('error','User membership is expired');
            }else{
                return redirect()->route('front.index');
            }
        }

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();

            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id]
            ])->first();
        $data['portfolios'] = $user->portfolios()->where('language_id', $userCurrentLang->id)->where('featured', 1)->orderBy('serial_number','ASC')->get() ?? collect([]);
        $data['portfolio_categories'] = $user->portfolioCategories()
        ->whereHas('portfolios', function($q){
            $q->where('featured',1);
        })->where('language_id', $userCurrentLang->id)->where('status', 1)->orderBy('serial_number','ASC')->get() ?? collect([]);
        $data['skills'] = $user->skills()->where('language_id', $userCurrentLang->id)->orderBy('serial_number','ASC')->get() ?? collect([]);
        $data['achievements'] = $user->achievements()->where('language_id', $userCurrentLang->id)->orderBy('serial_number','ASC')->get() ?? collect([]);
        $data['services'] = $user->services()->where([
            ['lang_id', $userCurrentLang->id],
            ['featured',1]
        ])
        ->orderBy('serial_number','ASC')
        ->get() ?? collect([]);
        $data['testimonials'] = $user->testimonials()->where('lang_id', $userCurrentLang->id)->orderBy('serial_number','ASC')->get() ?? collect([]);
        $data['blogs'] = $user->blogs()
                ->where('language_id', $userCurrentLang->id)
                ->latest()
                ->take(3)
                ->get() ?? collect([]);

        $data['job_experiences'] = $user->job_experiences()
                ->where('lang_id', $userCurrentLang->id)
                ->orderBy('serial_number','ASC')
                ->get() ?? collect([]);
        $data['educations'] = $user->educations()
                ->where('lang_id', $userCurrentLang->id)
                ->orderBy('serial_number','ASC')
                ->get() ?? collect([]);


        $data['user'] = $user;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1) {
            return view('user.profile1.index', $data);
        } elseif ($ubs->theme == 2) {
            return view('user.profile1.index2', $data);
        } else {
            return view('user.profile.profile', $data);
        }
    }

    public function paymentInstruction(Request $request)
    {
        $offline = OfflineGateway::where('name', $request->name)
            ->select('short_description', 'instructions', 'is_receipt')
            ->first();
        return response()->json(['description' => $offline->short_description,
            'instructions' => $offline->instructions, 'is_receipt' => $offline->is_receipt]);
    }

    public function contactMessage($domain, Request $request)
    {
        $rules = [
            'fullname' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required'
        ];

    
        $request->validate($rules);

        $toUser = User::query()->findOrFail($request->id);
        $data['toMail'] = $toUser->email;
        $data['toName'] = $toUser->username;
        $data['subject'] = $request->subject;
        $data['body'] = "<div>$request->message</div><br>
                         <strong>For further contact with the enquirer please use the below information:</strong><br>
                         <strong>Enquirer Name:</strong> $request->fullname <br>
                         <strong>Enquirer Mail:</strong> $request->email <br>
                         ";
        $mailer = new MegaMailer();
        $mailer->mailContactMessage($data);
        Session::flash('success', 'Mail sent successfully');
        return back();

    }

    public function adminContactMessage(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required'
        ];
    
        $bs = BS::select('is_recaptcha')->first();
    
        if ($bs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ];
    
        $request->validate($rules, $messages);


        $be =  BE::firstOrFail();
        $from = $request->email;
        $to = $be->to_mail;
        $subject = $request->subject;
        $message = $request->message;

        try {

            $mail = new PHPMailer(true);
            $mail->setFrom($from, $request->name);
            $mail->addAddress($to);     // Add a recipient

            // Content
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
        } catch (\Exception $e) {
            Session::flash('success', $e->getMessage());
        }

        Session::flash('success', 'Message sent successfully');
        return back();
    }

    public function userServices($domain){
        $user = getUser();
        $id = $user->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();

        $data['services'] = User\UserService::query()
                ->where('user_id', $id)
                ->where('lang_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get();

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1) {
            return view('user.profile1.services', $data);
        } elseif ($ubs->theme == 2) {
            return view('user.profile1.services2', $data);
        } else {
            return view('user.profile.services', $data);
        }
    }

    public function userServiceDetail($domain, $slug, $id){
        $data['service'] = User\UserService::query()->findOrFail($id);
        $userId = $data['service']->user_id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } else {
            $data['folder'] = "profile";
        }

        return view('user.profile-common.service-details', $data);
    }

    public function userBlogs(Request $request, $domain){
        $user = getUser();
        $id = $user->id;
        $data['user'] = $user;
        $catid = $request->category;
        $term = $request->term;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();


        $data['blogs'] = User\Blog::query()
            ->when($catid, function ($query, $catid) {
                return $query->where('category_id', $catid);
            })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'LIKE', '%' . $term . '%');
            })
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number','ASC')
            ->paginate(6);


        $data['latestBlogs'] = User\Blog::query()
        ->where('user_id', $id)
        ->where('language_id', $userCurrentLang->id)
        ->orderBy('id','DESC')
        ->limit(3)->get();

        $data['blog_categories'] = User\BlogCategory::query()
        ->where('status',1)
        ->orderBy('serial_number','ASC')
        ->where('language_id', $userCurrentLang->id)
        ->where('user_id', $id)
        ->get();

        $data['allCount'] = User\Blog::query()
        ->where('user_id', $id)
        ->where('language_id', $userCurrentLang->id)
        ->count();
    
        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1) {
            return view('user.profile1.blogs', $data);
        } elseif ($ubs->theme == 2) {
            return view('user.profile1.blogs2', $data);
        } else {
            return view('user.profile.blogs', $data);
        }
    }

    public function userBlogDetail($domain, $slug, $id){
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['blog'] = User\Blog::query()->findOrFail($id);

        $data['latestBlogs'] = User\Blog::query()
        ->where('user_id', $userId)
        ->where('language_id', $userCurrentLang->id)
        ->orderBy('id','DESC')
        ->limit(3)->get();

        $data['blog_categories'] = User\BlogCategory::query()
        ->where('status',1)
        ->orderBy('serial_number','ASC')
        ->where('language_id', $userCurrentLang->id)
        ->where('user_id', $userId)
        ->get();

        $data['allCount'] = User\Blog::query()
        ->where('user_id', $userId)
        ->where('language_id', $userCurrentLang->id)
        ->count();

        $userId = $data['blog']->user_id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } else {
            $data['folder'] = "profile";
        }

        return view('user.profile-common.blog-details', $data);
    }

    public function userPortfolios(Request $request, $domain){
        $user = getUser();
        $id = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status',1)
            ->orderBy('serial_number','ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['catId'] = $request->category;

        $data['portfolios'] = User\Portfolio::query()
            ->where('user_id', $id)
            ->latest()
            ->orderBy('serial_number','ASC')
            ->where('language_id', $userCurrentLang->id)
            ->get();

    
        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1) {
            return view('user.profile1.portfolios', $data);
        } elseif ($ubs->theme == 2) {
            return view('user.profile1.portfolios2', $data);
        } else {
            return view('user.profile.portfolios', $data);
        }
    }

    public function userPortfolioDetail($domain, $slug, $id){
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $portfolio = User\Portfolio::query()->findOrFail($id);
        $catId = $portfolio->category_id;
        $data['relatedPortfolios'] = User\Portfolio::where('category_id', $catId)->where('id', '<>', $portfolio->id)->where('user_id', $userId)->orderBy('id', 'DESC')->limit(5);
        $data['portfolio'] = $portfolio;
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status',1)
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->orderBy('serial_number','ASC')
            ->get();
        $data['allCount'] = User\Portfolio::where('language_id', $userCurrentLang->id)->where('user_id', $userId)->count();

        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } else {
            $data['folder'] = "profile";
        }

        return view('user.profile-common.portfolio-details', $data);
    }
    public function changeLanguage($lang): \Illuminate\Http\RedirectResponse
    {
        session()->put('lang', $lang);
        app()->setLocale($lang);
        return redirect()->route('front.index');
    }
    public function changeUserLanguage(Request $request, $domain)
    {
        session()->put('user_lang', $request->code);
        return redirect()->route('front.user.detail.view', $domain);
    }

    public function vcard($domain, $id) {
        $vcard = UserVcard::findOrFail($id);

        
        $count = $vcard->user->memberships()->where('status','=',1)
                ->where('start_date','<=', Carbon::now()->format('Y-m-d'))
                ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))->count();
        // check if the vcard owner does not have membership
        if ($count == 0) {
            return view('errors.404');
        }


        $cFeatures = UserPermissionHelper::packagePermission($vcard->user_id);
        $cFeatures = json_decode($cFeatures, true);
        if (empty($cFeatures) || !is_array($cFeatures) || !in_array('vCard', $cFeatures)) {
            return view('errors.404');
        }
        
        $parsedUrl = parse_url(url()->current());
        
        $host =  $parsedUrl['host'];
        // if the current host contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            // if the current URL is subdomain
            if ($host != env('WEBSITE_HOST')) {
                if (!cPackageHasSubdomain($vcard->user)) {
                    return view('errors.404');
                }
            }
        } 
        // if the current host doesn't contain the website domain (meaning, custom domain)
        else {
            // if the current package doesn't have 'custom domain' feature || the custom domain is not connected
            $cdomain = UserCustomDomain::where('requested_domain', $host)->where('status', 1)->count();
            if (!cPackageHasCdomain($vcard->user) || $cdomain == 0) {
                return view('errors.404');
            }
        }

        $infos = json_decode($vcard->information, true);

        $prefs = [];
        if (!empty($vcard->preferences)) {
            $prefs = json_decode($vcard->preferences, true);
        }
        
        $keywords = json_decode($vcard->keywords, true);

        $data['vcard'] = $vcard;
        $data['infos'] = $infos;
        $data['prefs'] = $prefs;
        $data['keywords'] = $keywords;
        if ($vcard->template == 1) {
            return view('vcard.index1', $data);
        } elseif ($vcard->template == 2) {
            return view('vcard.index2', $data);
        } elseif ($vcard->template == 3) {
            return view('vcard.index3', $data);
        } elseif ($vcard->template == 4) {
            return view('vcard.index4', $data);
        }
    }

    public function vcardImport($domain, $id) {
        $vcard = UserVcard::findOrFail($id);

        // define vcard
        $vcardObj = new VCard();

        // add personal data
        if (!empty($vcard->name)) {
            $vcardObj->addName($vcard->name);
        }
        if (!empty($vcard->company)) {
            $vcardObj->addCompany($vcard->company);
        }
        if (!empty($vcard->occupation)) {
            $vcardObj->addJobtitle($vcard->occupation);
        }
        if (!empty($vcard->email)) {
            $vcardObj->addEmail($vcard->email);
        }
        if (!empty($vcard->phone)) {
            $vcardObj->addPhoneNumber($vcard->phone, 'WORK');
        }
        if (!empty($vcard->address)) {
            $vcardObj->addAddress($vcard->address);
            $vcardObj->addLabel($vcard->address);
        }
        if (!empty($vcard->website_url)) {
            $vcardObj->addURL($vcard->website_url);
        }

        $vcardObj->addPhoto('assets/front/img/user/vcard/' . $vcard->profile_image);
        
        return \Response::make(
            $vcardObj->getOutput(),
            200,
            $vcardObj->getHeaders(true)
        );
    }

    public function cv($domain, $id) {
        $cv = UserCv::findOrFail($id);

        
        $count = $cv->user->memberships()->where('status','=',1)
                ->where('start_date','<=', Carbon::now()->format('Y-m-d'))
                ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))->count();
        // check if the cv owner does not have membership
        if ($count == 0) {
            return view('errors.404');
        }


        $cFeatures = UserPermissionHelper::packagePermission($cv->user_id);
        $cFeatures = json_decode($cFeatures, true);
        if (empty($cFeatures) || !is_array($cFeatures) || !in_array('Online CV & Export', $cFeatures)) {
            return view('errors.404');
        }
        
        $parsedUrl = parse_url(url()->current());
        
        $host =  $parsedUrl['host'];
        // if the current host contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            // if the current URL is subdomain
            if ($host != env('WEBSITE_HOST')) {
                if (!cPackageHasSubdomain($cv->user)) {
                    return view('errors.404');
                }
            }
        } 
        // if the current host doesn't contain the website domain (meaning, custom domain)
        else {
            // if the current package doesn't have 'custom domain' feature || the custom domain is not connected
            $cdomain = UserCustomDomain::where('requested_domain', $host)->where('status', 1)->count();
            if (!cPackageHasCdomain($cv->user) || $cdomain == 0) {
                return view('errors.404');
            }
        }

        $infos = json_decode($cv->cv_information, true);
        
        $data['cv'] = $cv;
        $data['infos'] = $infos;




        if ($cv->template == 1) {
            $lsections = $cv->user_cv_sections()->where('column', 1);
            if ($lsections->count() > 0) {
                $lsections = $lsections->orderBy('order', 'ASC')->get();
            } else {
                $lsections = [];
            }
    
    
            $rsections = $cv->user_cv_sections()->where('column', 2);
            if ($rsections->count() > 0) {
                $rsections = $rsections->orderBy('order', 'ASC')->get();
            } else {
                $rsections = [];
            }

            $data['lsections'] = $lsections;
            $data['rsections'] = $rsections;
            return view('cv.index1', $data);
        } elseif ($cv->template == 2) {
            $sections = $cv->user_cv_sections();
            if ($sections->count() > 0) {
                $sections = $sections->orderBy('order', 'ASC')->get();
            } else {
                $sections = [];
            }
            $data['sections'] = $sections;
            return view('cv.index2', $data);
        }
    }
}
