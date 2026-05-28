<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\SocialPost;

class FetchTestTweets extends Command
{
    protected $signature = 'social:test-fetch';

    protected $description = 'Fetch test X posts';

    public function handle()
    {
        $keyword = 'Kolkata Airport';

        $url = 'https://nitter.poast.org/search?f=tweets&q='
            . urlencode($keyword);

$response = Http::withHeaders([
    'User-Agent' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
])
->timeout(30)
->withoutVerifying()
->get($url);

dump($response->status());

dump(substr($response->body(), 0, 3000));

        if (!$response->successful()) {

            $this->error('Failed to fetch data');

            return;
        }

        preg_match_all(
            '/<div class="tweet-content media-body"[^>]*>(.*?)<\/div>/si',
            $response->body(),
            $matches
        );

        if (empty($matches[1])) {

            $this->warn('No posts found');

            return;
        }

        foreach ($matches[1] as $content) {

            $cleanText = html_entity_decode(
                trim(strip_tags($content))
            );

            SocialPost::create([
                'content' => $cleanText,
                'keyword' => $keyword,
                'platform' => 'x',
            ]);
        }

        $this->info('Posts fetched successfully');
    }
}
