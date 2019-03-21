<?php

namespace Qs\La\Tests\Browser;

use App\Models\Post;
use Encore\Admin\Auth\Database\Administrator;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Qs\La\Tests\BrowserTestCase;

class FilepondTest extends BrowserTestCase
{
    public function testCreate()
    {
        $images = [];
        $avatar = '';
        $files = [];
        $file = '';

        $this->browse(function ($browser) use (&$images, &$avatar, &$files, &$file) {
            $browser->loginAs(Administrator::find(1), 'admin')
                ->visit('/admin/filepond/create')
                ->type('name', 'test')
                ->waitFor('.filepond--root', 10);

            $imagesElm = $browser->element('.images__')->findElement(WebDriverBy::cssSelector('input[type=file]'));

            $avatarElm = $browser->element('.avatar')->findElement(WebDriverBy::cssSelector('input[type=file]'));

            $filesElm = $browser->element('.files__')->findElement(WebDriverBy::cssSelector('input[type=file]'));

            $fileElm = $browser->element('.file')->findElement(WebDriverBy::cssSelector('input[type=file]'));

//            $browser->attach('#'.$imagesElm->getAttribute('id'), __DIR__.'/../TestFiles/sample.jpeg')
//                ->attach('#'.$imagesElm->getAttribute('id'), __DIR__.'/../TestFiles/sample2.jpg')
//                ->waitFor(".images__ .filepond--item:nth-child(1) .filepond--action-process-item[style*='opacity:1']", 10)
//                ->screenshot("test2");

            $browser->attach('#'.$imagesElm->getAttribute('id'), __DIR__.'/../TestFiles/sample.jpeg')
                ->waitFor(".images__ .filepond--item:nth-child(1) .filepond--action-process-item[style*='opacity:1']", 10)
                ->assertSeeIn('.images__ .filepond--item:nth-child(1) legend', 'sample.jpeg')
                ->assertSourceHas('filepond--image-preview-wrapper')
                ->press('.images__ .filepond--item:nth-child(1) .filepond--action-process-item')
                ->waitFor('.images__ .filepond--item:nth-child(1) .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.images__ .filepond--item:nth-child(1) .filepond--file-status-main', 'Upload complete')
                ->attach('#'.$imagesElm->getAttribute('id'), __DIR__.'/../TestFiles/sample2.jpg')
                ->waitFor(".images__ .filepond--item:nth-child(1) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.images__ .filepond--item:nth-child(1) legend', 'sample2.jpg')
                ->press('.images__ .filepond--item:nth-child(1) .filepond--action-process-item')
                ->waitFor('.images__ .filepond--item:nth-child(1) .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.images__ .filepond--item:nth-child(1) .filepond--file-status-main', 'Upload complete')
                ->attach('#'.$imagesElm->getAttribute('id'), __DIR__.'/../TestFiles/filesTest.doc')
                ->waitFor(".images__ .filepond--item:nth-child(1) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.images__ .filepond--item:nth-child(1) .filepond--file-status-main', 'File is of invalid type');

            foreach($browser->elements("input[name='images[]']") as $ele) {
                $ele->getAttribute('value') && $images[] = $ele->getAttribute('value');
            }

            $this->assertEquals(2, count($images));

            $browser->attach('#'.$avatarElm->getAttribute('id'), __DIR__.'/../TestFiles/filesTest.doc')
                ->waitFor(".avatar .filepond--item:nth-child(1) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.avatar .filepond--item:nth-child(1) .filepond--file-status-main', 'File is of invalid type')
                ->attach('#'.$avatarElm->getAttribute('id'), __DIR__.'/../TestFiles/sample.jpeg')
                ->waitFor(".avatar .filepond--item:first-child .filepond--action-process-item[style*='opacity:1']", 10)
                ->press('.avatar .filepond--item:first-child .filepond--action-process-item')
                ->waitFor('.avatar .filepond--item:first-child .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.avatar .filepond--item:first-child .filepond--file-status-main', 'Upload complete');

            $avatar = $browser->element('input[name=avatar]')->getAttribute('value');

            $browser->attach('#'.$filesElm->getAttribute('id'), __DIR__.'/../TestFiles/sample.jpeg')
                ->waitFor(".files__ .filepond--item:nth-child(1) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.files__ .filepond--item:nth-child(1) .filepond--file-status-main', 'File is of invalid type')
                ->attach('#'.$filesElm->getAttribute('id'), __DIR__.'/../TestFiles/filesTest.doc')
                ->waitFor(".files__ .filepond--item:nth-child(2) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->press('.files__ .filepond--item:nth-child(1) .filepond--action-process-item')
                ->waitFor('.files__ .filepond--item:nth-child(1) .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.files__ .filepond--item:nth-child(1) .filepond--file-status-main', 'Upload complete')
                ->attach('#'.$filesElm->getAttribute('id'), __DIR__.'/../TestFiles/filesTest.pdf')
                ->waitFor(".files__ .filepond--item:nth-child(2) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.files__ .filepond--item:nth-child(1) legend', 'filesTest.pdf')
                ->press('.files__ .filepond--item:nth-child(1) .filepond--action-process-item')
                ->waitFor('.files__ .filepond--item:nth-child(1) .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.files__ .filepond--item:nth-child(1) .filepond--file-status-main', 'Upload complete');

            foreach($browser->elements("input[name='files[]']") as $ele) {
                $ele->getAttribute('value') && $files[] = $ele->getAttribute('value');
            }

            $browser->attach('#'.$fileElm->getAttribute('id'), __DIR__.'/../TestFiles/bigFile.pdf')
                ->waitFor(".file .filepond--item:nth-child(1) .filepond--action-remove-item[style*='opacity:1']", 10)
                ->assertSeeIn('.file .filepond--item:nth-child(1) .filepond--file-status-main', 'File is too large')
                ->attach('#'.$fileElm->getAttribute('id'), __DIR__.'/../TestFiles/sample.jpeg')
                ->waitFor(".file .filepond--item:first-child .filepond--action-process-item[style*='opacity:1']", 10)
                ->assertSourceMissing('filepond--image-preview-wrapper')
                ->press('.file .filepond--item:first-child .filepond--action-process-item')
                ->waitFor('.file .filepond--item:first-child .filepond--action-revert-item-processing', 10)
                ->assertSeeIn('.file .filepond--item:first-child .filepond--file-status-main', 'Upload complete');

            $file = $browser->element('input[name=avatar]')->getAttribute('value');

            $browser->press('Submit')->waitFor('.table tbody tr:first-child', 10);
        });
        $images = collect($images)->map(function($item){
            return "'{$item}'";
        })->implode(',');
        $files = collect($files)->map(function($item){
            return "'{$item}'";
        })->implode(',');
        $this->assertDatabaseHas('posts', ['images' => DB::raw("json_array({$images})"), 'avatar' => $avatar, 'files' => DB::raw("json_array({$files})") , 'file' => $file]);
    }

    public function testUpdate()
    {
        File::makeDirectory(storage_path('app/public/files'));
        File::copy(__DIR__.'/../TestFiles/sample.jpeg', storage_path('app/public/files/sample.jpeg'));
        File::copy(__DIR__.'/../TestFiles/sample.jpeg', storage_path('app/public/files/sample1.jpeg'));

        $post = new Post();
        $post->name = 'test';
        $post->images = ['files/sample.jpeg'];
        $post->avatar = 'files/sample1.jpeg';
        $post->save();

        $this->browse(function ($browser) {
            $browser->loginAs(Administrator::find(1), 'admin')
                ->visit('admin/filepond/1/edit')
                ->waitFor(".images__ .filepond--item .filepond--action-remove-item[style*='opacity:1']", 10)
                ->press('.images__ .filepond--action-remove-item')
                ->waitUntilMissing('.images__ .filepond--action-remove-item')
                ->press('button[type=submit]')->waitFor('.table tbody tr:first-child', 10);
        });

        $post = Post::find(1);
        $this->assertTrue(empty($post->images));
    }

}
