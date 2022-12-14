<?php

namespace App\Commands;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use SimpleXMLElement;

class PostToMastodon extends Command
{
    private string $code = '';

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'post:status';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get the latest blog post from an RSS feed and post it to Mastodon if not done so already.';

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $url = env('MASTODON_URL');
        $content = file_get_contents(env('RSS_URL'));
        $readRss = new SimpleXMLElement($content);

        $latestPost = $readRss->channel->item;

        $posts = DB::table('posts')->orderBy('id', 'desc');

        if ($posts->first() === null || $latestPost->title != $posts->first()->name) {
            $posts->insert(
                ['name' => $latestPost->title],
            );

            if ($this->code === '') {
                $this->browse(function ($browser) {
                    $browser->visit('https://mastodon.social/oauth/authorize?client_id=' . env('MASTODON_KEY')
                        . '&scope=read+write&redirect_uri=urn:ietf:wg:oauth:2.0:oob&response_type=code')
                        ->type('user[email]', env('MASTODON_USERNAME'))
                        ->type('user[password]', env('MASTODON_PASSWORD'))
                        ->press('button');

                    $this->code = $browser->value('.oauth-code');
                });
            }

            $bearer = json_decode(Http::post($url . '/oauth/token', [
                'client_id' => env('MASTODON_KEY'),
                'client_secret' => env('MASTODON_SECRET'),
                'redirect_uri' => env('MASTODON_REDIRECT'),
                'grant_type' => 'authorization_code',
                'code' => $this->code,
                'scope' => 'read write',
            ])->body());

            Http::withHeaders([
                'Authorization' => 'Bearer ' . $bearer->access_token
            ])->post($url . '/api/v1/statuses', [
                'status' => $latestPost->title . ' #michaelbrooks #blog #blogger #writer ' . $latestPost->link,
            ]);

            $this->info('Latest blog post has been posted successfully!');
            exit;
        }

        $this->info('Latest blog post has already been posted!');
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
