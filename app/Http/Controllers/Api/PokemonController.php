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
     *     summary="Get pokemon list",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="return",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="skip",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=0, default=0)
     *     ),
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
            $limit = request('limit', 10);
            $offset = request('offset', 0);
            
            $response = Http::timeout(30)->get('https://pokeapi.co/api/v2/pokemon', [
                'limit' => $limit,
                'offset' => $offset
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

    /**
     * @OA\Get(
     *     path="/api/pokemon/search",
     *     tags={"Pokemon"},
     *     summary="Search pokemon by name",
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Pokemon name to search",
     *         required=true,
     *         @OA\Schema(type="string", example="pikachu")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="pikachu"),
     *             @OA\Property(property="type", type="string", example="electric"),
     *             @OA\Property(property="image", type="string", example="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pokemon not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se pudo obtener información")
     *         )
     *     ),
     *     @OA\Response(response=503, description="Service unavailable")
     * )
     */
    public function search()
    {
        try {
            $name = request('name');
            
            if (!$name) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No se pudo obtener información'
                ], 404);
            }

            $pokemonDetails = $this->searchPokemonByName($name);
            
            if (!$pokemonDetails) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No se pudo obtener información'
                ], 404);
            }

            return response()->json($pokemonDetails);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'No se pudo obtener información'
            ], 404);
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

    private function searchPokemonByName(string $name): ?array
    {
        try {
            $url = "https://pokeapi.co/api/v2/pokemon/" . strtolower($name);
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
}
