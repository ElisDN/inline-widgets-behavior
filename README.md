Allows render widgets in page content in Yii Framework based projects
==========================

[README RUS](http://www.elisdn.ru/blog/13/vstraivaem-vidjeti-v-tekst-stranici-v-yii)

Installation
------------

Extract to `protected/components`.

Usage example
-------------

Add allowed widgets list to `config/main.php`:
~~~
[php]
return array(
    // ...
    'params'=>array(
         // ...
        'runtimeWidgets'=>array(
            'LastPosts',
            'Share',
        }
    }
}
~~~

Create widgets:
~~~
[php]
class LastPostsWidget extends CWidget
{
    public $tpl='default';
    public $limit=3;

    public function run()
    {
        $posts = Post::model()->published()->last($this->limit)->findAll();
        $this->render('LastPosts/' . $this->tpl, array(
            'posts'=>$posts,
        ));
    }
}
~~~

Include behavior in main controller:
~~~
[php]
class Controller extends CController
{
    public function behaviors()
    {
        return array(
            'InlineWidgetsBehavior'=>array(
                'class'=>'application.components.DInlineWidgetsBehavior',
                'location'=>'application.components.widgets',                
                'widgets'=>Yii::app()->params['runtimeWidgets'],
             ),
        );
    }
}
~~~

For rendering widgets in any View you must call Controller::decodeWidgets() method for page content:
~~~
[php]
$model->text = '
    <h2>Lorem ipsum</h2>
    <p>[*LastPosts*]</p>
    <p>[*LastPosts|limit=4*]</p>
    <p>[*LastPosts|limit=5;tpl=small*]</p>
    <p>[*LastPosts|limit=5;tpl=small|cache=300*]</p>
    <p>Dolor...</p>
';
echo $this->decodeWidgets($$model->text);
~~~

[More examples](http://www.elisdn.ru/blog/13/vstraivaem-vidjeti-v-tekst-stranici-v-yii)