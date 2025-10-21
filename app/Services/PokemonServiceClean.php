<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PokemonService
{
    private const BASE_URL = 'https://pokeapi.co/api/v2';
    private const CACHE_TTL = 3600;

    public function getPokemonList(int $limit = 10, int $offset = 0): array
    {
        $cacheKey = "pokemon_list_{$limit}_{$offset}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit, $offset) {
            try {
                $response = Http::timeout(30)->get(self::BASE_URL . '/pokemon', [
                    'limit' => $limit,
                    'offset' => $offset
                ]);

                if (!$response->successful()) {
                    return [];
                }

                $data = $response->json();
                $pokemonList = [];

                foreach ($data['results'] as $pokemon) {
                    $pokemonDetails = $this->getPokemonDetails($pokemon['url']);
                    if ($pokemonDetails) {
                        $pokemonList[] = $pokemonDetails;
                    }
                }

                return $pokemonList;

            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function getPokemonDetails(string $url): ?array
    {
        $pokemonId = $this->extractPokemonIdFromUrl($url);
        $cacheKey = "pokemon_details_{$pokemonId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($url) {
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
        });
    }

    public function getPokemonById(int $id): ?array
    {
        $cacheKey = "pokemon_by_id_{$id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            try {
                $response = Http::timeout(30)->get(self::BASE_URL . "/pokemon/{$id}");

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
        });
    }

    private function extractTypes(array $types): string
    {
        return collect($types)
            ->pluck('type.name')
            ->implode(', ');
    }

    private function extractPokemonIdFromUrl(string $url): int
    {
        return (int) basename(rtrim($url, '/'));
    }
}
