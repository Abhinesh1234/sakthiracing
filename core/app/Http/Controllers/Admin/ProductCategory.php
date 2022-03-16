<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Pcategory;
use App\Language;
use App\Megamenu;
use Validator;
use Session;

class ProductCategory extends Controller
{
    public function index(Request $request)
    {
        $lang = Language::where('code', $request->language)->first();
        $lang_id = $lang->id;
        $data['pcategories'] = Pcategory::where('language_id', $lang_id)->orderBy('id', 'DESC')->paginate(10);

        $data['lang_id'] = $lang_id;
        return view('admin.product.category.index',$data);
    }


    public function store(Request $request)
    {
        $messages = [
            'language_id.required' => 'The language field is required'
        ];

        $rules = [
            'language_id' => 'required',
            'name' => 'required|max:255',
            'status' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }


        $data = new Pcategory;
        $input = $request->all();
        $input['slug'] =  make_slug($request->name);
        $data->create($input);

        Session::flash('success', 'Category added successfully!');
        return "success";
    }


    public function edit($id)
    {
        $data = Pcategory::findOrFail($id);
        return view('admin.product.category.edit',compact('data'));
    }

    public function update(Request $request)
    {
        $data = Pcategory::findOrFail($request->category_id);
        $input = $request->all();
        $input['slug'] =  make_slug($request->name);
        $data->update($input);

        Session::flash('success', 'Category Update successfully!');
        return "success";
    }

    public function deleteFromMegaMenu($category) {
        $megamenu = Megamenu::where('language_id', $category->language_id)->where('category', 1)->where('type', 'products');
        if ($megamenu->count() > 0) {
            $megamenu = $megamenu->first();
            $menus = json_decode($megamenu->menus, true);
            $catId = $category->id;
            if (is_array($menus) && array_key_exists("$catId", $menus)) {
                unset($menus["$catId"]);
                $megamenu->menus = json_encode($menus);
                $megamenu->save();
            }
        }
    }


    public function delete(Request $request)
    {
        $category = Pcategory::findOrFail($request->category_id);
        if ($category->products()->count() > 0) {
            Session::flash('warning', 'First, delete all the product under the selected categories!');
            return back();
        }

        $this->deleteFromMegaMenu($category);

        $category->delete();

        Session::flash('success', 'Category deleted successfully!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        foreach ($ids as $id) {
            $pcategory = Pcategory::findOrFail($id);
            if ($pcategory->products()->count() > 0) {
                Session::flash('warning', 'First, delete all the product under the selected categories!');
                return "success";
            }
        }

        foreach ($ids as $id) {
            $pcategory = Pcategory::findOrFail($id);

            $this->deleteFromMegaMenu($pcategory);

            $pcategory->delete();
        }

        Session::flash('success', 'product categories deleted successfully!');
        return "success";
    }

}
