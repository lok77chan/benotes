<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Console\Command;

class ThumbnailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnail:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing thumbnails with chrome';

    private $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new PostService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('What post would you like to "improve" ?');
        $post_id = $this->ask('Please specify a post id or type all');
        if ($post_id === 'all') {
            $this->info('This can take several minutes...');
            $posts = Post::whereNull('deleted_at')
                ->where('type', Post::POST_TYPE_LINK)
                ->whereNull('image_path');
            foreach ($posts as $post) {
                $this->createThumbnail($post);
            }
            return 0;
        } else {
            $post = Post::find(intval($post_id));
            $this->createThumbnail($post);
        }

        return 0;
    }

    private function createThumbnail(Post $post)
    {
        if ($post->type === Post::POST_TYPE_TEXT) {
            $this->error('Post is not a link and therefore has no thumbnail');
            return;
        }

        if (@get_headers($post->url) == false) {
            $this->error('Post has no existing link');
            return;
        }

        $filename = 'thumbnail_' . md5($post->url) . '_' . $post->id . '.jpg';
        $path = storage_path('app/public/thumbnails/' . $filename);
        $this->service->screenshot($filename, $path, $post->url, 400, 210);
        if (file_exists($path)) {
            $post->image_path = $filename;
            $post->save();
        } else {
            $this->error('Thumbnail could not be created');
        }
    }
}