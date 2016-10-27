<?php
namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem as Fs;

class ImageController extends Controller
{
    public static $portal = 'http://na.leagueoflegends.com/en/media/art/wallpaper';
    public static $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like 
        Gecko) Chrome/53.0.2785.116 Safari/537.36';
    public static $remote_server = 'http://na.leagueoflegends.com/';
    public static $img_storage = [];

    function download()
    {
        ob_start();
        $start = microtime(true);
        $pages = static::page_crawler_0(); // start from 0

        for ($i = 0; $i < $pages; $i ++) {
            static::page_crawler_1($i);
        }

        if (empty(static::$img_storage)) {
            echo('No image available!');
            exit;
        }

        echo 'ready to download...<br>';
        static::save_image();
        $end = microtime(true);

        echo '<h1>spend:' . ($end - $start) . 'second(s)</h1>';
    }

    public static function page_crawler_0()
    {
        $page_content = static::curl_get(static::$portal);

        $dom = new \DOMDocument();
        $dom->loadHTML($page_content);
        $xpath = new \DOMXPath($dom);

        $page = $xpath->query('//*[@class="pager"]/a[position() = (last() - 1)]');

        $page_no = $page[0]->nodeValue;

        return $page_no;
    }

    public function page_crawler_1($page_no)
    {
        $page_content = static::curl_get(static::$portal . '?page=' . $page_no);

        $dom = new \DOMDocument();
        $dom->loadHTML($page_content);
        $xpath = new \DOMXPath($dom);

        $img_nodes = $xpath->query('//*[@class="default-2-3"]//*[starts-with(@class, "view")]/div/div//img');
        $a_nodes = $xpath->query('//*[@class="default-2-3"]//*[starts-with(@class, "view")]/div/div//h4//a');

        $data = [];
        foreach ($a_nodes as $a) {
            $img_base_name = $a->nodeValue;
            $sub_page_url = $a->getAttribute('href');

            $data[] = [
                'name' => $img_base_name,
                'url' => static::$remote_server . $sub_page_url,
            ];
        }

        foreach ($img_nodes as $key => $img) {
            $src = $img->getAttribute('src');
            if (strpos($src,'http:') === 0) {
                $data[$key]['320x180'] = $src;
            } else {
                $data[$key]['320x180'] = static::$remote_server . $src;
            }
        }

        static::$img_storage = array_merge(static::$img_storage, $data);
    }

    public function save_image()
    {
        $images = static::$img_storage;

        foreach ($images as $key => $val) {
            $fs = new Fs();
            $wallpaper_path = public_path('images/leagueofledgends/wallpapers/' . $val['name']);
            if (! $fs->exists($wallpaper_path)) {
                $fs->makeDirectory($wallpaper_path);
            }

            $content = static::curl_get($val['320x180']);

            if ($fs->put($wallpaper_path . '/' . $val['name'] . '_320x180.jpg', $content)) {
                echo 'image saved at ' . $wallpaper_path, '<br>';
            } else {
                echo 'error occurred when saving ' . $val['name'], '<br>';
                ob_end_flush();
                // flush();
            }
        }
    }


    public static function curl_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, static::$ua);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}
