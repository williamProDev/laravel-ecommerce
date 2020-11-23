<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use App\SearchableProduct;
use Illuminate\Http\Request;
use MeiliSearch\Endpoints\Indexes;
use Gloudemans\Shoppingcart\Facades\Cart;

class ShopController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::all();
            
        if (request()->category) {
            $targetCategory = Category::where('slug', request()->category)->firstOrFail();
            $products = $targetCategory->products();
            $categoryName = $targetCategory->name;
        } else {
            $products = Product::Where('featured', TRUE);
            $categoryName = 'Featured';
        }

        if (request()->sort == 'high_low') {
            $products = $products->orderBy('price', 'desc');
        } else if (request()->sort == 'low_high') {
            $products = $products->orderBy('price');
        } else {
            $products = $products->inRandomOrder();
        }

        $products = $products->paginate(12);

        return view('shop', compact(['products', 'categories', 'categoryName']));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        $mightAlsoLike = Product::where('id', '!=', $id)->mightAlsoLike()->get();
        $wishlistInstance = Cart::instance('wishlist')->search(function($cartItem, $rowId) use ($id) {
            return $cartItem->id === $id;
        })->first();

        $wishlistRowId = $wishlistInstance ? $wishlistInstance->rowId : null;

        $productLevel = getProductLevel($product->quantity);

        return view('product', compact(['product', 'mightAlsoLike', 'wishlistRowId', 'productLevel']));
    }

    public function search(Request $request)
    {
        request()->validate([
            'query' => 'required|min:3'
        ]);
        
        $products = Product::search($request->input('query'))->where('quantity', '>', 0)->paginate(4);

        return view('search-results', compact('products'));
    }
}
