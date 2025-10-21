<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

/**
 * @OA\Tag(
 *     name="Pokemon",
 *     description="Pokemon API"
 * )
 */
class PokemonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/pokemon",
     *     tags={"Pokemon"},
     *     summary="Get 10 pokemon",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="name", type="string", example="bulbasaur"),
     *                 @OA\Property(property="type", type="string", example="grass, poison"),
     *                 @OA\Property(property="image", type="string", example="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=503, description="Service unavailable")
     * )
     */
    public function index()
    {
        try {
            $response = Http::timeout(30)->get('https://pokeapi.co/api/v2/pokemon', [
                'limit' => 10,
                'offset' => 0
            ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Service unavailable'], 503);
            }

            $data = $response->json();
            $pokemonList = [];

            foreach ($data['results'] as $pokemon) {
                $pokemonDetails = $this->getPokemonDetails($pokemon['url']);
                if ($pokemonDetails) {
                    $pokemonList[] = $pokemonDetails;
                }
            }

            return response()->json($pokemonList);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    private function getPokemonDetails(string $url): ?array
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $pokemon = $response->json();

            return [
                'name' => $pokemon['name'],
                'type' => $this->extractTypes($pokemon['types']),
                'image' => $pokemon['sprites']['front_default'] ?? null
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractTypes(array $types): string
    {
        $typeNames = [];
        foreach ($types as $type) {
            $typeNames[] = $type['type']['name'];
        }
        return implode(', ', $typeNames);
    }
}
