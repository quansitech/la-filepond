<?php
namespace Qs\La\Tests\Browser;

use App\Models\Post;
use Encore\Admin\Auth\Database\Administrator;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Qs\La\Tests\BrowserTestCase;

class FilepondTest extends BrowserTestCase{

    public function testCreate(){
        $images = '';
        $avatar = '';
        $this->browse(function ($browser) use(&$images, &$avatar){
            $browser->loginAs(Administrator::find(1), 'admin')
                ->visit('/admin/filepond/create')
                ->type('name', 'test')
                ->waitFor('.filepond--root', 10);

            $imagesElm = $browser->element('.images__')->findElement(WebDriverBy::cssSelector("input[type=file]"));

            $avatarElm = $browser->element('.avatar')->findElement(WebDriverBy::cssSelector("input[type=file]"));

            $browser->attach('#' . $imagesElm->getAttribute('id'), __DIR__ . '/../TestFiles/sample.jpeg')
            ->waitFor(".images__ .filepond--item:first-child .filepond--action-process-item[style*='opacity:1']", 10)
            ->assertSeeIn(".images__ .filepond--item:first-child legend", "sample.jpeg")
            ->press(".images__ .filepond--item:first-child .filepond--action-process-item")
            ->waitFor('.images__ .filepond--item:first-child .filepond--action-revert-item-processing', 10)
            ->assertSeeIn(".images__ .filepond--item:first-child .filepond--file-status-main", "Upload complete");

            $images = $browser->element("input[name='images[]']")->getAttribute("value");

            $this->assertIsString($images);

            $browser->attach('#' . $imagesElm->getAttribute('id'), __DIR__ . '/../TestFiles/sample2.jpg')
            ->waitForText("File is too large", 10)
            ->attach('#' . $avatarElm->getAttribute('id'), __DIR__ . '/../TestFiles/sample.jpeg')
            ->waitFor(".avatar .filepond--item:first-child .filepond--action-process-item[style*='opacity:1']", 10)
            ->press(".avatar .filepond--item:first-child .filepond--action-process-item")
            ->waitFor('.avatar .filepond--item:first-child .filepond--action-revert-item-processing', 10)
            ->assertSeeIn(".avatar .filepond--item:first-child .filepond--file-status-main", "Upload complete");

            $avatar = $browser->element("input[name=avatar]")->getAttribute("value");

            $browser->press('Submit')->waitFor('.table tbody tr:first-child', 10);
        });
        $this->assertDatabaseHas("posts", ['images' => DB::raw("json_array('{$images}')"), 'avatar' => $avatar]);
    }

    public function testUpdate(){
        File::makeDirectory(storage_path('app/public/files'));
        File::copy(__DIR__ . '/../TestFiles/sample.jpeg', storage_path('app/public/files/sample.jpeg'));
        File::copy(__DIR__ . '/../TestFiles/sample.jpeg', storage_path('app/public/files/sample1.jpeg'));

        $post = new Post();
        $post->name = 'test';
        $post->images = ['files/sample.jpeg'];
        $post->avatar = 'files/sample1.jpeg';
        $post->save();

        $this->browse(function ($browser){
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