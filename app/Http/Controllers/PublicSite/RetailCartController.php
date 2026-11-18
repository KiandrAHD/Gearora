<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\CartRequest;
use App\Models\ProductVariant;
use App\Services\SalesCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Keranjang retail (session key `retail_cart`), terpisah dari draft booking rental.
 */
class RetailCartController extends Controller
{
    public function __construct(private readonly SalesCartService $cart) {}

    public function index(): View
    {
        return view('retail.cart', [
            'lines' => $this->cart->lines(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function store(CartRequest $request): RedirectResponse
    {
        $variant = ProductVariant::query()->with('product')->findOrFail($request->integer('variant_id'));

        try {
            $this->cart->add($variant, $request->integer('quantity'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('retail.cart.index')->with('status', 'Ditambahkan ke keranjang.');
    }

    public function update(CartRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->load('product');

        try {
            $this->cart->update($variant, $request->integer('quantity'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('retail.cart.index')->with('status', 'Keranjang diperbarui.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant->getKey());

        return redirect()->route('retail.cart.index')->with('status', 'Item dihapus.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('retail.cart.index')->with('status', 'Keranjang dikosongkan.');
    }
}
