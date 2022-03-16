<?php

namespace App\Http\Controllers\User;

use App\Models\User\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Validator;
use Session;


class LanguageController extends Controller
{
    public function index($lang = false)
    {
        $data['languages'] = Language::where('user_id', Auth::id())->get();
        return view('user.language.index', $data);
    }


    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|max:255',
            'code' => ['required',
                function ($attribute, $value, $fail) {
                    $language = Language::where([
                        ['code', $value],
                        ['user_id', Auth::id()]
                    ])->get();
                    if ($language->count() > 0) {
                        $fail(':attribute already taken');
                    }
                },
            ],
            'direction' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $deLang = Language::first();

        $in['name'] = $request->name;
        $in['code'] = $request->code;
        $in['rtl'] = $request->direction;
        $in['keywords'] = $deLang->keywords;
        $in['user_id'] = Auth::id();
        if (Language::where([
                ['is_default', 1],
                ['user_id', Auth::id()]
            ])->count() > 0) {
            $in['is_default'] = 0;
        } else {
            $in['is_default'] = 1;
        }
        Language::create($in);
        Session::flash('success', 'Language added successfully!');
        return "success";
    }

    public function edit($id)
    {
        if ($id > 0) {
            $data['language'] = Language::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        }
        $data['id'] = $id;
        return view('user.language.edit', $data);
    }


    public function update(Request $request)
    {
        $language = Language::findOrFail($request->language_id);
        
        if ($language->user_id != Auth::user()->id) {
            return;
        }

        $rules = [
            'name' => 'required|max:255',
            'code' => [
                'required',
                'max:255',
                function ($attribute, $value, $fail) use ($language, $request) {
                    $langs = Language::where('user_id', Auth::user()->id)->where('id', '<>', $language->id)->get();
                    foreach ($langs as $key => $lang) {
                        if ($lang->code == $request->code) {
                            return $fail("Language code have to be unique.");
                        }
                    }
                }
            ],
            'direction' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $language->name = $request->name;
        $language->code = $request->code;
        $language->rtl = $request->direction;
        $language->user_id = Auth::id();
        $language->save();

        Session::flash('success', 'Language updated successfully!');
        return "success";
    }

    public function editKeyword($id)
    {

        $data['la'] = Language::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        $data['keywords'] = json_decode($data['la']->keywords, true);
        return view('user.language.edit-keyword', $data);

    }

    public function updateKeyword(Request $request, $id)
    {
        $lang = Language::findOrFail($id);
        if ($lang->user_id != Auth::user()->id) {
            return back();
        }
        $keywords = $request->except('_token');
        $lang->keywords = json_encode($keywords);
        $lang->save();
        return back()->with('success', 'Updated Successfully');
    }


    public function delete($id)
    {

        $la = Language::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        if ($la->is_default == 1) {
            return back()->with('warning', 'Default language cannot be deleted!');
        }
        if (session()->get('lang') == $la->code) {
            session()->forget('lang');
        }

        // deleting testimonials for corresponding language
        if (!empty($la->services)) {
            $services = $la->services;
            if (!empty($services)) {
                foreach ($services as $service) {
                    @unlink('assets/front/img/user/services/' . $service->image);
                    $service->delete();
                }
            }
        }

        // deleting testimonials for corresponding language
        if (!empty($la->testimonials)) {
            $testimonials = $la->testimonials;
            if (!empty($testimonials)) {
                foreach ($testimonials as $testimonial) {
                    @unlink('assets/front/img/user/testimonials/' . $testimonial->image);
                    $testimonial->delete();
                }
            }
        }

        // deleting blogs for corresponding language
        if (!empty($la->blogs)) {
            $blogs = $la->blogs;
            if (!empty($blogs)) {
                foreach ($blogs as $blog) {
                    @unlink('assets/front/img/user/blogs/' . $blog->image);
                    $blog->delete();
                }
            }
        }
        // deleting blog categories for corresponding language
        if (!empty($la->blog_categories)) {
            $blogCategories = $la->blog_categories;
            if (!empty($blogCategories)) {
                foreach ($blogCategories as $blogCategory) {
                    $blogCategory->delete();
                }
            }
        }

        // deleting skills for corresponding language
        if (!empty($la->skills)) {
            $skills = $la->skills;
            if (!empty($skills)) {
                foreach ($skills as $skill) {
                    @unlink('assets/front/img/user/skills/' . $skill->image);
                    $skill->delete();
                }
            }
        }

        // deleting portfolios for corresponding language
        if (!empty($la->portfolios)) {
            $portfolios = $la->portfolios;
            if (!empty($portfolios)) {
                foreach ($portfolios as $portfolio) {
                    $pis = $portfolio->portfolio_images;
                    if (!empty($pis)) {
                        foreach ($pis as $key => $pi) {
                            @unlink('assets/front/img/user/portfolios/' . $pi->image);
                            $pi->delete();
                        }
                    }
                    @unlink('assets/front/img/user/portfolios/' . $portfolio->image);
                    $portfolio->delete();
                }
            }
        }
        // deleting portfolio categories for corresponding language
        if (!empty($la->portfolio_categories)) {
            $portfolioCategories = $la->portfolio_categories;
            if (!empty($portfolioCategories)) {
                foreach ($portfolioCategories as $portfolioCategory) {
                    $portfolioCategory->delete();
                }
            }
        }

        // deleting job experience for corresponding language
        if (!empty($la->job_experiences)) {
            $job_experiences = $la->job_experiences;
            if (!empty($job_experiences)) {
                foreach ($job_experiences as $job_experience) {
                    $job_experience->delete();
                }
            }
        }

        // deleting educations for corresponding language
        if (!empty($la->educations)) {
            $educations = $la->educations;
            if (!empty($educations)) {
                foreach ($educations as $education) {
                    $education->delete();
                }
            }
        }

        // deleting seos for corresponding language
        if (!empty($la->seos)) {
            $seos = $la->seos;
            if (!empty($seos)) {
                foreach ($seos as $seo) {
                    $seo->delete();
                }
            }
        }

        // deleting home page texts for corresponding language
        if (!empty($la->home_page_texts)) {
            $home_page_texts = $la->home_page_texts;
            if (!empty($home_page_texts)) {
                foreach ($home_page_texts as $homeText) {
                    @unlink('assets/front/img/user/home_settings/' . $homeText->hero_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->about_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->skills_image);
                    @unlink('assets/front/img/user/home_settings/' . $homeText->achievement_image);
                    $homeText->delete();
                }
            }
        }

        // deleting seos for corresponding language
        if (!empty($la->achievements)) {
            $achievements = $la->achievements;
            if (!empty($achievements)) {
                foreach ($achievements as $achievement) {
                    $achievement->delete();
                }
            }
        }

        // if the the deletable language is the currently selected language in frontend then forget the selected language from session
        session()->forget('lang');
        $la->delete();
        return back()->with('success', 'Language Delete Successfully');
    }


    public function default(Request $request, $id)
    {
        Language::where('is_default', 1)->where('user_id', Auth::user()->id)->update(['is_default' => 0]);
        $lang = Language::find($id);
        $lang->is_default = 1;
        $lang->save();
        return back()->with('success', $lang->name . ' language is set as default.');
    }

    public function rtlcheck($langid)
    {
        if ($langid > 0) {
            $lang = Language::find($langid);
        } else {
            return 0;
        }
        return $lang->rtl;
    }
}
