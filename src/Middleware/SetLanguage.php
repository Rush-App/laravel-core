<?php

namespace RushApp\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RushApp\Core\Models\Language;

class SetLanguage
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $languageName = $request->header('Language');

        $cacheTTL = config('rushapp_core.default_cache_ttl');
        /** @var Collection|Language[] $languages */
        $languages = Cache::remember('languages', $cacheTTL, function () {
            return Language::all();
        });
        $language = $languages->where('name', $languageName)->first() ?: $languages->first();
        $currentLanguageName = $language ? $language->name : config('app.fallback_locale');

        app()->setLocale($currentLanguageName);
        $request->merge([
            "language" => $currentLanguageName,
            "language_id" => $language->id,
        ]);

        return $next($request);
    }
}
