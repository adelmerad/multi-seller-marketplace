<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class ProductRecommendationController extends Controller
{
    private const RECOMMENDER_URL = 'http://127.0.0.1:5000';

    public function getRecommendations_content($productID)
    {
        return $this->recommendedProducts('/recommend_content/' . rawurlencode($productID));
    }

    public function getRecommendations_collaborative($UserID)
    {
        return $this->recommendedProducts('/recommend_users/' . rawurlencode($UserID));
    }

    public function getRecommendations_popularity()
    {
        return $this->recommendedProducts('/recommend_popular');
    }

    /**
     * Calls the recommender service and returns the products in the order it ranked them.
     * whereIn() alone would return them in database order and lose the ranking.
     */
    private function recommendedProducts(string $path)
    {
        $response = Http::get(self::RECOMMENDER_URL . $path);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to fetch recommendations'], 500);
        }

        $productIds = collect($response->json('recommendations') ?? [])->pluck('id')->values();
        $position = $productIds->flip(); // id => rank

        $products = Product::whereIn('id', $productIds)->get()
            ->sortBy(fn ($product) => $position[$product->id])
            ->values();

        return ProductResource::collection($products);
    }
}
